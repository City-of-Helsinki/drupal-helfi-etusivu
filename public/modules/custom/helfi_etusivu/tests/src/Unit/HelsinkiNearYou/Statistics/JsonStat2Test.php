<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Unit\HelsinkiNearYou\Statistics;

use Drupal\Tests\UnitTestCase;
use Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\JsonStat2;

/**
 * @coversDefaultClass \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\JsonStat2
 * @group helfi_etusivu
 */
class JsonStat2Test extends UnitTestCase {

  /**
   * Tests reading values out of a cube.
   *
   * The same logical cube is read in both dimension orders: a reader that
   * assumed a fixed order would return a different cell for the transposed
   * one.
   *
   * @covers ::value
   */
  public function testValue() : void {
    $rowMajor = new JsonStat2($this->cube(['A', 'B'], [2, 3], [10, 11, 12, 20, 21, 22]));
    // B declared first, so B becomes the outer dimension.
    $transposed = new JsonStat2($this->cube(['B', 'A'], [3, 2], [10, 20, 11, 21, 12, 22]));

    $cases = [
      'origin' => [['A' => 0, 'B' => 0], 10.0],
      'inner dimension' => [['A' => 0, 'B' => 2], 12.0],
      'outer dimension' => [['A' => 1, 'B' => 0], 20.0],
      'far corner' => [['A' => 1, 'B' => 2], 22.0],
      'omitted dimensions default to zero' => [[], 10.0],
      'partially omitted' => [['A' => 1], 20.0],
      'out of range' => [['A' => 9, 'B' => 9], NULL],
    ];

    foreach ($cases as $name => [$coordinates, $expected]) {
      $this->assertSame($expected, $rowMajor->value($coordinates), "row major: $name");
      $this->assertSame($expected, $transposed->value($coordinates), "transposed: $name");
    }

    // A single cell dataset is what most queries return.
    $single = new JsonStat2([
      'id' => ['Alue', 'Vuosi', 'Tiedot'],
      'size' => [1, 1, 1],
      'value' => [7465],
    ]);
    $this->assertSame(7465.0, $single->value());
  }

  /**
   * Tests how cells are cast.
   *
   * PxWeb suppresses cells for small areas. Treating those as 0 would show a
   * wrong number instead of "no data".
   *
   * @covers ::value
   */
  public function testCellCasting() : void {
    $cases = [
      'null' => [NULL, NULL],
      'empty string' => ['', NULL],
      'dot' => ['.', NULL],
      'two dots' => ['..', NULL],
      'integer' => [7465, 7465.0],
      'float' => [66895.8, 66895.8],
      'numeric string' => ['66895.8', 66895.8],
      'zero' => [0, 0.0],
    ];

    foreach ($cases as $name => [$cell, $expected]) {
      $sut = new JsonStat2($this->cube(['A', 'B'], [2, 3], [$cell, 1, 1, 1, 1, 1]));
      $this->assertSame($expected, $sut->value(['A' => 0, 'B' => 0]), "cell: $name");
    }
  }

  /**
   * Tests reading categories and their labels.
   *
   * @covers ::categories
   * @covers ::label
   */
  public function testCategoriesAndLabels() : void {
    $data = $this->cube(['A', 'B'], [2, 3], [1, 2, 3, 4, 5, 6]);
    // Declared out of index order, with numeric keys that PHP turns into ints.
    $data['dimension']['A']['category'] = [
      'index' => ['2' => 1, 'ALL' => 0, '10' => 2],
      'label' => ['ALL' => 'Total', '2' => 'Two', '10' => 'Ten'],
    ];
    $sut = new JsonStat2($data);

    $categories = $sut->categories('A');
    $this->assertSame(['ALL', '2', '10'], $categories, 'categories follow the index, as strings');
    $this->assertContainsOnlyString($categories);

    $this->assertSame([], $sut->categories('Nope'), 'unknown dimension has no categories');

    $this->assertSame('Two', $sut->label('A', '2'));
    $this->assertSame('B two', $sut->label('B', 'b2'));
    $this->assertNull($sut->label('A', 'missing'), 'unknown category has no label');
    $this->assertNull($sut->label('Nope', 'a0'), 'unknown dimension has no label');
  }

  /**
   * Tests that an empty dataset does not error.
   *
   * A failed or empty response decodes to an empty array.
   *
   * @covers ::value
   * @covers ::categories
   * @covers ::label
   */
  public function testEmptyDataset() : void {
    $sut = new JsonStat2([]);

    $this->assertNull($sut->value());
    $this->assertNull($sut->value(['A' => 1]));
    $this->assertSame([], $sut->categories('A'));
    $this->assertNull($sut->label('A', 'a0'));
  }

  /**
   * Builds a cube where dimension A has 2 categories and B has 3.
   *
   * @param array $id
   *   The dimension order.
   * @param array $size
   *   The dimension lengths.
   * @param array $values
   *   The flat values.
   *
   * @return array
   *   The dataset.
   */
  private function cube(array $id, array $size, array $values) : array {
    return [
      'id' => $id,
      'size' => $size,
      'value' => $values,
      'dimension' => [
        'A' => [
          'category' => [
            'index' => ['a0' => 0, 'a1' => 1],
            'label' => ['a0' => 'A zero', 'a1' => 'A one'],
          ],
        ],
        'B' => [
          'category' => [
            'index' => ['b0' => 0, 'b1' => 1, 'b2' => 2],
            'label' => ['b0' => 'B zero', 'b1' => 'B one', 'b2' => 'B two'],
          ],
        ],
      ],
    ];
  }

}

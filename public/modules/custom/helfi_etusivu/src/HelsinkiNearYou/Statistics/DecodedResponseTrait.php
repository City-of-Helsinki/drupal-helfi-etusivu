<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\HelsinkiNearYou\Statistics;

use Drupal\helfi_api_base\ApiClient\ApiResponse;

/**
 * Reads ApiClient responses as nested arrays.
 *
 * ApiClient decodes JSON without the associative flag, so objects arrive as
 * \stdClass. Both APIs used here return deeply nested structures.
 */
trait DecodedResponseTrait {

  /**
   * Converts a response into a nested associative array.
   *
   * @param \Drupal\helfi_api_base\ApiClient\ApiResponse $response
   *   The response.
   *
   * @return array
   *   The response data.
   *
   * @throws \Drupal\helfi_etusivu\HelsinkiNearYou\Statistics\StatisticsException
   *   Thrown when the data cannot be re-encoded.
   */
  private function toArray(ApiResponse $response) : array {
    try {
      $data = json_decode(
        json_encode($response->data, flags: JSON_THROW_ON_ERROR),
        associative: TRUE,
        flags: JSON_THROW_ON_ERROR,
      );
    }
    catch (\JsonException $e) {
      throw new StatisticsException($e->getMessage(), previous: $e);
    }

    return is_array($data) ? $data : [];
  }

}

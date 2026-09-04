<?php

declare(strict_types=1);

namespace Drupal\Tests\helfi_etusivu\Kernel\Entity\Search;

use Drupal\Core\Access\AccessManagerInterface;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Routing\RouteBuilderInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\language\Entity\ConfigurableLanguage;
use Drupal\Tests\helfi_etusivu\Kernel\Entity\EntityKernelTestBase;
use Drupal\user\Entity\User;
use PHPUnit\Framework\Attributes\TestWith;

/**
 * Base class for the search entity tests.
 */
abstract class SearchEntityTestBase extends EntityKernelTestBase {

  /**
   * Permission that grants access to the entities.
   */
  protected const string ADMIN_PERMISSION = 'administer search content';

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'content_translation',
    'diff',
    'helfi_api_base',
    'helfi_etusivu',
    'language',
  ];

  /**
   * Creates and saves the entity under test.
   */
  abstract protected function createTestEntity(): ContentEntityInterface;

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();

    $this->installConfig(['language']);

    ConfigurableLanguage::create(['id' => 'fi', 'label' => 'Finnish'])->save();
    ConfigurableLanguage::create(['id' => 'sv', 'label' => 'Swedish'])->save();

    // Create a dummy user before tests to make sure our actual user is not
    // UID1 and getting all permissions automatically.
    $this->drupalCreateUser();
  }

  /**
   * Rebuilds the router.
   */
  protected function rebuildRouter(): void {
    $this->container->get(RouteBuilderInterface::class)->rebuild();
  }

  /**
   * Tests access permissions.
   *
   * @param array<string, bool> $expected
   *   The expected access, keyed by operation.
   * @param string[]|null $permissions
   *   Permissions or NULL for an anonymous account.
   */
  #[TestWith([['view' => FALSE, 'create' => FALSE, 'update' => FALSE, 'delete' => FALSE]], 'anonymous')]
  #[TestWith([['view' => FALSE, 'create' => FALSE, 'update' => FALSE, 'delete' => FALSE], []], 'authenticated')]
  #[TestWith([['view' => TRUE, 'create' => TRUE, 'update' => TRUE, 'delete' => TRUE], [self::ADMIN_PERMISSION]], 'admin')]
  public function testAccess(array $expected, ?array $permissions = NULL): void {
    $account = $permissions === NULL
      ? User::getAnonymousUser()
      : $this->drupalCreateUser($permissions);

    $this->rebuildRouter();
    $entity = $this->createTestEntity();

    $this->assertEntityAccess($entity, $expected, $account);

    foreach ($this->getAdminRoutes($entity) as $route => ['parameters' => $parameters, 'operation' => $operation]) {
      $this->assertRouteAccess($route, $parameters, $account, $expected[$operation]);
    }
  }

  /**
   * Tests access to the translation routes.
   *
   * @param bool $expected
   *   Whether the account should be able to translate the entity.
   * @param string[]|null $permissions
   *   The account's permissions, or NULL for an anonymous account.
   */
  #[TestWith([FALSE], 'anonymous')]
  #[TestWith([FALSE, []], 'authenticated')]
  #[TestWith([TRUE, [self::ADMIN_PERMISSION, 'translate any entity']], 'admin')]
  public function testTranslationRouteAccess(bool $expected, ?array $permissions = NULL): void {
    $this->rebuildRouter();

    $account = $permissions === NULL
      ? User::getAnonymousUser()
      : $this->drupalCreateUser($permissions);

    $entity = $this->createTestEntity();
    $entityTypeId = $entity->getEntityTypeId();
    $this->assertTrue($entity->hasTranslation('sv'));

    $parameters = [$entityTypeId => $entity->id()];

    $this->assertRouteAccess(
      sprintf('entity.%s.content_translation_overview', $entityTypeId),
      $parameters,
      $account,
      $expected,
    );

    $this->assertRouteAccess(
      sprintf('entity.%s.content_translation_add', $entityTypeId),
      $parameters + ['source' => 'en', 'target' => 'fi'],
      $account,
      $expected,
    );

    foreach (['content_translation_edit', 'content_translation_delete'] as $name) {
      $this->assertRouteAccess(
        sprintf('entity.%s.%s', $entityTypeId, $name),
        $parameters + ['language' => 'sv'],
        $account,
        FALSE,
      );
    }
  }

  /**
   * Asserts entity access for the given operations.
   *
   * @phpstan-param array<string, bool> $operations
   *   The expected access, keyed by operation.
   */
  protected function assertEntityAccess(ContentEntityInterface $entity, array $operations, AccountInterface $account): void {
    foreach ($operations as $operation => $expected) {
      $access = $entity->access($operation, $account, TRUE);
      $this->assertEquals($expected, $access->isAllowed());
    }
  }

  /**
   * Gets the admin routes generated from the entity type's link templates.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity under test.
   *
   * @return array<string, array{parameters: array<string, mixed>, operation: string}>
   *   The route parameters and the operation each route requires, keyed by
   *   route name.
   */
  protected function getAdminRoutes(ContentEntityInterface $entity): array {
    $entityTypeId = $entity->getEntityTypeId();
    $definition = $entity->getEntityType();

    $identifier = [$entityTypeId => $entity->id()];
    $templates = [
      'collection' => [[], 'view'],
      'add-form' => [[], 'create'],
      'canonical' => [$identifier, 'view'],
      'edit-form' => [$identifier, 'update'],
      'delete-form' => [$identifier, 'delete'],
    ];

    // Bundleable types have the bundle in the add form route.
    if ($bundleEntityType = $definition->getBundleEntityType()) {
      $templates['add-form'] = [[$bundleEntityType => $entity->bundle()], 'create'];
    }

    $routes = [];
    foreach ($templates as $template => [$parameters, $operation]) {
      if (!$definition->hasLinkTemplate($template)) {
        continue;
      }
      $route = sprintf('entity.%s.%s', $entityTypeId, str_replace('-', '_', $template));
      $routes[$route] = ['parameters' => $parameters, 'operation' => $operation];
    }

    // Every search entity has an admin UI that must be protected.
    $this->assertNotEmpty($routes);

    return $routes;
  }

  /**
   * Asserts access to the given route.
   *
   * @param string $route
   *   The route name.
   * @param array<string, mixed> $parameters
   *   The route parameters.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The account.
   * @param bool $expected
   *   Whether the account should have access.
   */
  protected function assertRouteAccess(string $route, array $parameters, AccountInterface $account, bool $expected): void {
    $this->setCurrentUser($account);

    $access = $this->container->get(AccessManagerInterface::class)
      ->checkNamedRoute($route, $parameters, $account, TRUE);

    $this->assertEquals($expected, $access->isAllowed());
  }

}

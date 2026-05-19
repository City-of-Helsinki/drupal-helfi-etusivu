<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Hook;

use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Drupal\helfi_etusivu\Search\PromotionLinkChecker;
use Symfony\Component\Routing\Exception\RouteNotFoundException;

/**
 * Hooks for the search promotion entity.
 */
final class PromotionHooks {

  use StringTranslationTrait;

  public function __construct(
    private readonly PromotionLinkChecker $linkChecker,
    private readonly MessengerInterface $messenger,
  ) {}

  /**
   * Implements hook_gin_content_form_routes().
   *
   * @return string[]
   *   Route names that should use Gin's content-form sidebar layout.
   */
  #[Hook(hook: 'gin_content_form_routes')]
  public function ginContentFormRoutes(): array {
    return [
      'entity.helfi_search_promotion.add_form',
      'entity.helfi_search_promotion.edit_form',
    ];
  }

  /**
   * Implements hook_cron().
   */
  #[Hook(hook: 'cron')]
  public function cron(): void {
    $this->linkChecker->checkLinks();
  }

  /**
   * Implements hook_user_login().
   *
   * Warns search-promotion admins when one or more promotions have a failing
   * automated link check, so they can act on it.
   */
  #[Hook(hook: 'user_login')]
  public function userLogin(AccountInterface $account): void {
    if (!$account->hasPermission('administer search promotions')) {
      return;
    }

    $count = $this->linkChecker->countFailingPromotions();
    if ($count === 0) {
      return;
    }

    try {
      $url = Url::fromRoute('view.search_promotion_broken_links.page_1')->toString();
    }
    catch (RouteNotFoundException) {
      // The broken-links view config may legitimately be absent
      // e.g. in kernel tests.
      $url = Url::fromRoute('entity.helfi_search_promotion.collection')->toString();
    }

    $this->messenger->addWarning($this->formatPlural(
      $count,
      '1 search promotion has a failing link check. <a href=":url">Review</a>.',
      '@count search promotions have failing link checks. <a href=":url">Review</a>.',
      [':url' => $url],
    ));
  }

}

<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Search;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\content_lock\ContentLock\ContentLockInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\helfi_etusivu\Entity\Search\Promotion;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

/**
 * Performs HTTP health checks on promoted search result links.
 */
final class PromotionLinkChecker implements LoggerAwareInterface {

  use LoggerAwareTrait;

  /**
   * Minimum time, in seconds, between consecutive checks on a translation.
   */
  public const int CHECK_INTERVAL = 86400;

  /**
   * Maximum number of promotion entities to inspect at a time.
   */
  public const int BATCH_SIZE = 25;

  public function __construct(
    private readonly ClientInterface $httpClient,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly TimeInterface $time,
    private readonly ContentLockInterface $contentLock,
  ) {}

  /**
   * Runs HTTP checks against every search promotion.
   */
  public function checkLinks(): void {
    $now = $this->time->getRequestTime();
    $threshold = $now - self::CHECK_INTERVAL;

    $storage = $this->entityTypeManager->getStorage('helfi_search_promotion');
    $ids = $storage->getQuery()
      ->accessCheck(FALSE)
      ->condition('status', 1)
      ->condition('last_checked', $threshold, '<')
      ->sort('last_checked', 'ASC')
      ->range(0, self::BATCH_SIZE)
      ->execute();

    if (!$ids) {
      return;
    }

    foreach ($storage->loadMultiple($ids) as $promotion) {
      assert($promotion instanceof Promotion);

      // Skip entities someone is currently editing so cron doesn't bump
      // `changed` under them and break their save.
      if ($this->contentLock->fetchLock($promotion) !== FALSE) {
        $this->logger?->info('Skipping promotion @id link check — entity is locked by content_lock.', [
          '@id' => $promotion->id(),
        ]);
        continue;
      }

      foreach ($promotion->getTranslationLanguages() as $language) {
        $translation = $promotion->getTranslation($language->getId());
        assert($translation instanceof Promotion);

        if (!$translation->isPublished()) {
          continue;
        }
        if ($translation->getLastChecked() > $threshold) {
          continue;
        }

        $url = $translation->getUrl();
        if ($url === NULL) {
          continue;
        }

        if ($this->check($url)) {
          $translation->resetFailedCheckCount();
        }
        else {
          $translation->incrementFailedCheckCount();
        }

        $translation
          ->setLastChecked($now)
          ->save();
      }
    }
  }

  /**
   * Counts promotions whose failed_check_count is at or above the threshold.
   */
  public function countFailingPromotions(int $threshold = 1): int {
    return (int) $this->entityTypeManager
      ->getStorage('helfi_search_promotion')
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition('failed_check_count', $threshold, '>=')
      ->count()
      ->execute();
  }

  /**
   * Checks whether the given link resolves to a 2xx HTTP response.
   *
   * @return bool
   *   TRUE on a 2xx response; FALSE on any non-2xx status, transport error,
   *   or unresolvable internal reference.
   */
  public function check(Url $url): bool {
    try {
      $absolute = $url->setAbsolute()->toString();
    }
    catch (\Throwable $e) {
      $this->logger?->info('Could not resolve promotion link @uri: @message', [
        '@uri' => $url->toUriString(),
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }

    try {
      $response = $this->httpClient->request('GET', $absolute, [
        RequestOptions::TIMEOUT => 10,
        RequestOptions::ALLOW_REDIRECTS => TRUE,
        RequestOptions::HTTP_ERRORS => FALSE,
      ]);
    }
    catch (GuzzleException $e) {
      $this->logger?->info('Promotion link @uri failed with transport error: @message', [
        '@uri' => $absolute,
        '@message' => $e->getMessage(),
      ]);
      return FALSE;
    }

    $status = $response->getStatusCode();
    if ($status < 200 || $status >= 300) {
      $this->logger?->info('Promotion link @uri responded with HTTP @status.', [
        '@uri' => $absolute,
        '@status' => $status,
      ]);
      return FALSE;
    }

    return TRUE;
  }

}

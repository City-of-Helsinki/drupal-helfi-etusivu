<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Event;

/**
 * Lists the six events dispatched by Scheduler for promotion entities.
 */
final class SchedulerHelfiSearchPromotionEvents {

  public const PUBLISH_IMMEDIATELY = 'scheduler.helfi_search_promotion_publish_immediately';

  public const PUBLISH = 'scheduler.helfi_search_promotion_publish';

  public const PRE_PUBLISH_IMMEDIATELY = 'scheduler.helfi_search_promotion_pre_publish_immediately';

  public const PRE_PUBLISH = 'scheduler.helfi_search_promotion_pre_publish';

  public const PRE_UNPUBLISH = 'scheduler.helfi_search_promotion_pre_unpublish';

  public const UNPUBLISH = 'scheduler.helfi_search_promotion_unpublish';

}

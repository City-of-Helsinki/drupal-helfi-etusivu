<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Hook;

use Drupal\Core\Hook\Attribute\RemoveHook;
use Drupal\radioactivity\Hook\RadioactivityCronHooks;

/**
 * Prevents radioactivity from running on every cron run.
 *
 * Previously, ultimate cron -contrib module was used to run radioactivity
 * once per 3 hours since it caused performance issues when the cron
 * invalidated the list view caches constantly. The functionality is now run
 * by custom cron.
 */
#[RemoveHook('cron', class: RadioactivityCronHooks::class, method: 'cron')]
final class CronHooks {
}

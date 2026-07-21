<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Plugin\Scheduler;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\scheduler\SchedulerPluginBase;

/**
 * Scheduler plugin for the helfi_search_promotion entity type.
 *
 * @SchedulerPlugin(
 *   id = "helfi_search_promotion_scheduler",
 *   label = @Translation("Helfi search promotion scheduler plugin"),
 *   description = @Translation("Provides support for scheduling Helfi search promotion entities"),
 *   entityType = "helfi_search_promotion",
 *   dependency = "helfi_etusivu",
 *   collectionRoute = "entity.helfi_search_promotion.collection",
 *   schedulerEventClass = "\Drupal\helfi_etusivu\Event\SchedulerHelfiSearchPromotionEvents",
 * )
 */
final class HelfiSearchPromotionScheduler extends SchedulerPluginBase implements ContainerFactoryPluginInterface {}

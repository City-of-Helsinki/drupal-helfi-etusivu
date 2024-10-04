<?php

declare(strict_types=1);

namespace Drupal\helfi_etusivu\Drush\Commands;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Attributes\Command;
use Drush\Commands\AutowireTrait;
use Drush\Commands\DrushCommands;

/**
 * A Drush command file.
 */
final class EtusivuCommands extends DrushCommands {

  use AutowireTrait;

  /**
   * Constructs a new instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   */
  public function __construct(
    private readonly EntityTypeManagerInterface $entityTypeManager,
  ) {
    parent::__construct();
  }

  /**
   * UHF-10745: Restore changed timestamps for news items and articles.
   *
   * @return int
   *   The exit code.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  #[Command(name: 'helfi:etusivu-fix-timestamps')]
  public function fixTimestamps() : int {
    // Database update overwrote changed timestamp that are shown in the UI.
    // There have been multiple updates to nodes. The known dates are 25.9.,
    // 15.8. and in May. This attempt to restore data that was easily available.
    //
    // This is Drush command because our infra kills long-running update hooks.
    //
    // The timestamp were restored from a backup taken at 2024.09.24T12:08:37Z.
    // The affected nodes were chosen with:
    //
    // phpcs:disable
    // ```php
    // \Drupal::entityQuery('node')
    //  ->accessCheck(FALSE)
    //  ->condition('type', ['news_item', 'news_article'], 'IN')
    //  ->condition('created', strtotime('2024-08-15T12:00:00Z'), '>')
    //  ->execute()
    // ```
    $changed = [
      6652 => 1723811626, 6655 => 1724064607, 6656 => 1724060529, 6658 => 1726476617,
      6657 => 1724238691, 6659 => 1724657506, 6660 => 1724930960, 6661 => 1724151201,
      6662 => 1724142634, 6663 => 1726477258, 6664 => 1724153664, 6665 => 1724138980,
      6666 => 1725017992, 6667 => 1727091724, 6608 => 1724657506, 6612 => 1724253911,
      6668 => 1724913886, 6669 => 1726564872, 6670 => 1725874002, 6671 => 1725017992,
      6673 => 1725016308, 6672 => 1724323565, 6675 => 1724342925, 6674 => 1724402393,
      6676 => 1725430048, 6677 => 1725020156, 6678 => 1724673862, 6679 => 1726052870,
      6680 => 1726477258, 6681 => 1726477258, 6682 => 1724829880, 6683 => 1725516403,
      6684 => 1727074659, 6686 => 1724843738, 6687 => 1724744594, 6688 => 1726729180,
      6689 => 1725517029, 6690 => 1726564872, 6691 => 1724908931, 6694 => 1724765192,
      6695 => 1724831701, 6696 => 1724844352, 6697 => 1724831096, 6698 => 1725307717,
      6699 => 1724841512, 6700 => 1724923071, 6701 => 1726729180, 6702 => 1724922456,
      6703 => 1725016367, 6704 => 1725264556, 6705 => 1726476617, 6706 => 1727091724,
      6708 => 1726210968, 6707 => 1725016435, 6709 => 1725263604, 6711 => 1725017379,
      6710 => 1725009420, 6712 => 1725263942, 6713 => 1725014935, 6714 => 1727163728,
      6715 => 1725445319, 6716 => 1726564872, 6717 => 1725344655, 6718 => 1725347917,
      6719 => 1725445319, 6720 => 1725282193, 6721 => 1725281574, 6722 => 1725368595,
      6723 => 1725368595, 6724 => 1726477258, 6725 => 1725362500, 6726 => 1725368595,
      6727 => 1725430048, 6728 => 1725875220, 6729 => 1725533398, 6730 => 1725436219,
      6731 => 1725603733, 6732 => 1725611185, 6733 => 1725603277, 6734 => 1725869135,
      6735 => 1726639082, 6736 => 1726577658, 6737 => 1726054106, 6740 => 1726141217,
      6738 => 1726477258, 6741 => 1725620893, 6739 => 1725957872, 6743 => 1725622721,
      6742 => 1726054714, 6744 => 1726230617, 6745 => 1726206939, 6746 => 1725861855,
      6559 => 1726477258, 6747 => 1725878257, 6748 => 1726063812, 6749 => 1726216534,
      6750 => 1726229401, 6751 => 1725961479, 6752 => 1726150956, 6753 => 1725898831,
      6754 => 1727091106, 6755 => 1725972785, 6756 => 1726133809, 6757 => 1726557552,
      6758 => 1726234900, 6759 => 1726041216, 6760 => 1726134423, 6761 => 1726492472,
      6762 => 1726055837, 6763 => 1726052251, 6764 => 1726052870, 6765 => 1726842744,
      6766 => 1726128323, 6767 => 1726210622, 6768 => 1726211048, 6769 => 1726477258,
      6770 => 1726476933, 6771 => 1726139976, 6772 => 1727091102, 6774 => 1726216083,
      6775 => 1727164098, 6776 => 1726840318, 6777 => 1726577221, 6778 => 1726564301,
      6779 => 1726472321, 6780 => 1726577658, 6781 => 1726842047, 6783 => 1726565506,
      6784 => 1726559397, 6785 => 1726578882, 6786 => 1726560614, 6787 => 1726842240,
      6788 => 1727091724, 6789 => 1727185585, 6790 => 1727185577, 6791 => 1726736830,
      6792 => 1727185561, 6793 => 1727185594, 6794 => 1727185570, 6795 => 1727076541,
      6796 => 1727164098, 6797 => 1726724311, 6798 => 1726842106, 6799 => 1726724227,
      6800 => 1727090628, 6801 => 1726743611, 6802 => 1726841990, 6803 => 1726810406,
      6804 => 1726837899, 6805 => 1726750411, 6806 => 1726818499, 6807 => 1726832946,
      6808 => 1727175655, 6809 => 1727082002, 6810 => 1727081394, 6811 => 1727154957,
      6812 => 1727094160, 6813 => 1727104472, 6814 => 1727161643, 6815 => 1727164098,
      6816 => 1727162264, 6817 => 1727164098, 6818 => 1727180558, 6819 => 1727179945,
    ];
    // phpcs:enable

    $incident = new DrupalDateTime('2024-09-25T15:15:00', new \DateTimeZone('Europe/Helsinki'));
    $storage = $this->entityTypeManager->getStorage('node');

    foreach ($changed as $id => $timestamp) {
      $entity = $storage->load($id);

      if (
        !$entity instanceof EntityChangedInterface ||
        !in_array($entity->bundle(), ['news_item', 'news_article'])
      ) {
        continue;
      }

      // Only modify if the entity has not been touched after the incident.
      if ($entity->getChangedTime() < $incident->getTimestamp()) {
        $entity->setNewRevision(FALSE);
        $entity->setChangedTime($timestamp);
        $entity->save();
      }
    }

    return DrushCommands::EXIT_SUCCESS;
  }

}

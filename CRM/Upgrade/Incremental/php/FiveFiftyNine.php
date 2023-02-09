<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Upgrade logic for the 5.59.x series.
 *
 * Each minor version in the series is handled by either a `5.59.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_59_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveFiftyNine extends CRM_Upgrade_Incremental_Base {

  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL): void {
    if ($rev === '5.59.alpha1') {
      if (empty(CRM_Core_Config::singleton()->userSystem->is_wordpress)) {
        $preUpgradeMessage .= '<p>' . ts('The handling of invalid smarty template code in mailings, reminders and other automated messages has changed. For details, see <a %1>upgrade notes</a>.',
            [1 => 'href="https://docs.civicrm.org/sysadmin/en/latest/upgrade/version-specific/#civicrm-559" target="_blank"']) . '</p>';
      }
    }
  }

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_59_alpha1(string $rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Drop column civicrm_custom_field.mask', 'dropColumn', 'civicrm_custom_field', 'mask');
  }

  public function upgrade_5_59_beta1(string $rev): void {
    CRM_Core_DAO::executeQuery('UPDATE civicrm_option_group SET title = %1 WHERE name = %2', [
      1 => [ts('File Type'), 'String'],
      2 => ['safe_file_extension', 'String'],
    ]);

    // Change name+title for "Safe File Extensions" to "File Types". Shift nav-item up.
    // I don't see a pretty way to do this...
    $matches = CRM_Core_DAO::executeQuery('SELECT id, weight, parent_id FROM civicrm_navigation WHERE name = %1', [
      1 => ['Safe File Extensions', 'String'],
    ])->fetchAll();

    foreach ($matches as $orig) {
      CRM_Core_DAO::executeQuery('UPDATE civicrm_navigation SET name = %1 WHERE id = %2', [
        1 => ['File Types', 'String'],
        2 => [$orig['id'], 'Positive'],
      ]);
      // At time of writing, "File Types" is probably not yet in the *.mo files. So we can only really switch English titles.
      CRM_Core_DAO::executeQuery('UPDATE civicrm_navigation SET label = %1 WHERE id = %2 AND label = %3', [
        1 => [ts('File Types'), 'String'],
        2 => [$orig['id'], 'Positive'],
        3 => ['Safe File Extensions', 'String'],
      ]);

      $shift = 8;
      if ($orig['weight'] > $shift) {
        $newWeight = $orig['weight'] - $shift;
        CRM_Core_DAO::executeQuery('UPDATE civicrm_navigation SET weight = weight + 1 WHERE parent_id = %1 AND weight >= %2 AND weight < %3', [
          1 => [$orig['parent_id'], 'Positive'],
          2 => [$newWeight, 'Integer'],
          3 => [$orig['weight'], 'Integer'],
        ]);
        CRM_Core_DAO::executeQuery('UPDATE civicrm_navigation SET weight = %1 WHERE id = %2', [
          1 => [$newWeight, 'Integer'],
          2 => [$orig['id'], 'Positive'],
        ]);
      }
    }
  }

}

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
 * Upgrade logic for the 5.76.x series.
 *
 * Each minor version in the series is handled by either a `5.76.x.mysql.tpl` file,
 * or a function in this class named `upgrade_5_76_x`.
 * If only a .tpl file exists for a version, it will be run automatically.
 * If the function exists, it must explicitly add the 'runSql' task if there is a corresponding .mysql.tpl.
 *
 * This class may also implement `setPreUpgradeMessage()` and `setPostUpgradeMessage()` functions.
 */
class CRM_Upgrade_Incremental_php_FiveSeventySix extends CRM_Upgrade_Incremental_Base {

  /**
   * Upgrade step; adds tasks including 'runSql'.
   *
   * @param string $rev
   *   The version number matching this function name
   */
  public function upgrade_5_76_alpha1($rev): void {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Install SiteToken entity', 'createEntityTable', '5.76.alpha1.SiteToken.entityType.php');
    $this->addTask('Create "message header" token', 'create_mesage_header_token');
  }

  public static function create_mesage_header_token() {
    $token = Civi\Api4\SiteToken::create(FALSE)
      ->addValue('name', 'message_header')
      ->addValue('label', 'Message Heaader')
      ->addValue('body_html', '<tr></tr>')
      ->addValue('body_text', '')
      ->addValue('is_reserved', TRUE)
      ->addValue('is_active', TRUE)
      ->execute()
      ->first();
    return (!empty($token['id']));
  }
}

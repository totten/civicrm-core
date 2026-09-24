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

namespace Civi\Search\SKEntity;

use Civi\Search\SKEntityGenerator;

class ViewBackend extends AbstractBackend {

  public function initialize(): void {
    [, $displayName] = explode('_', $this->skEntity, 2);
    $tableName = _getSearchKitDisplayTableName($displayName);
    $sql = (new SKEntityGenerator())->createQuery($this->realEntity, $this->realParams, $this->settings);
    $columnSpecs = array_column($this->settings['columns'], 'spec');
    $columns = implode(', ', array_column($columnSpecs, 'name'));
    $sql = "CREATE VIEW `$tableName` ($columns) AS " . $sql;
    \CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, FALSE, FALSE);
  }

  public function clearData(): void {
    // Views are dynamic, no materialized data to clear
  }

  public function fillData(): void {
    // Views are dynamic, no materialized data to fill
  }

  public function destroy(): void {
    [, $displayName] = explode('_', $this->skEntity, 2);
    $tableName = _getSearchKitDisplayTableName($displayName);
    \CRM_Core_DAO::executeQuery(sprintf('DROP VIEW IF EXISTS `%s`', $tableName));
  }

}

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
 * Upgrade logic for FiveTwentyEight */
class CRM_Upgrade_Incremental_php_FiveTwentyEight extends CRM_Upgrade_Incremental_Base {

  /**
   * Compute any messages which should be displayed beforeupgrade.
   *
   * Note: This function is called iteratively for each upcoming
   * revision to the database.
   *
   * @param string $preUpgradeMessage
   * @param string $rev
   *   a version number, e.g. '4.4.alpha1', '4.4.beta3', '4.4.0'.
   * @param null $currentVer
   */
  public function setPreUpgradeMessage(&$preUpgradeMessage, $rev, $currentVer = NULL) {
    // Example: Generate a pre-upgrade message.
    // if ($rev == '5.12.34') {
    //   $preUpgradeMessage .= '<p>' . ts('A new permission, "%1", has been added. This permission is now used to control access to the Manage Tags screen.', array(1 => ts('manage tags'))) . '</p>';
    // }
  }

  /**
   * Compute any messages which should be displayed after upgrade.
   *
   * @param string $postUpgradeMessage
   *   alterable.
   * @param string $rev
   *   an intermediate version; note that setPostUpgradeMessage is called repeatedly with different $revs.
   */
  public function setPostUpgradeMessage(&$postUpgradeMessage, $rev) {
    // Example: Generate a post-upgrade message.
    // if ($rev == '5.12.34') {
    //   $postUpgradeMessage .= '<br /><br />' . ts("By default, CiviCRM now disables the ability to import directly from SQL. To use this feature, you must explicitly grant permission 'import SQL datasource'.");
    // }
  }

  /*
   * Important! All upgrade functions MUST add a 'runSql' task.
   * Uncomment and use the following template for a new upgrade version
   * (change the x in the function name):
   */

  /**
   * Upgrade function.
   *
   * @param string $rev
   */
  public function upgrade_5_28_alpha1($rev) {
    $this->addTask(ts('Upgrade DB to %1: SQL', [1 => $rev]), 'runSql', $rev);
    $this->addTask('Populate missing Contact Type name fields', 'populateMissingContactTypeName');
    $this->addTask('Add icon column to civicrm_custom_group', 'addColumn',
      'civicrm_custom_group', 'icon', "varchar(255) COMMENT 'crm-i icon class' DEFAULT NULL");
    $this->addTask('Remove index on medium_id from civicrm_activity', 'dropIndex', 'civicrm_activity', 'index_medium_id');

    list($minId, $maxId) = CRM_Core_DAO::executeQuery("SELECT coalesce(min(id),0), coalesce(max(id),0)
      FROM civicrm_relationship ")->getDatabaseResult()->fetchRow();
    for ($startId = $minId; $startId <= $maxId; $startId += self::BATCH_SIZE) {
      $endId = $startId + self::BATCH_SIZE - 1;
      $title = ts("Upgrade DB to %1: Fill civicrm_relationship_vtx (%2 => %3)", [
        1 => $rev,
        2 => $startId,
        3 => $endId,
      ]);
      $this->addTask($title, 'populateRelationshipVortex', $startId, $endId);
    }
  }

  /**
   * @param \CRM_Queue_TaskContext $ctx
   * @param int $startId
   *   The lowest relationship ID that should be updated.
   * @param int $endId
   *   The highest relationship ID that should be updated.
   * @return bool
   *   TRUE on success
   */
  public static function populateRelationshipVortex(CRM_Queue_TaskContext $ctx, $startId, $endId) {
    $params = [
      1 => [$startId, 'Positive'],
      2 => [$endId, 'Positive'],
    ];

    CRM_Core_DAO::executeQuery('
      INSERT INTO civicrm_relationship_vtx (relationship_id, relationship_type_id, orientation, near_type, near_contact_id, far_type, far_contact_id)
      SELECT rel.id, rel.relationship_type_id, 0, reltype.name_a_b, rel.contact_id_a, reltype.name_b_a, rel.contact_id_b
      FROM civicrm_relationship rel
      INNER JOIN civicrm_relationship_type reltype ON rel.relationship_type_id = reltype.id
      WHERE rel.id >= %1 AND rel.id <= %2
    ', $params);

    CRM_Core_DAO::executeQuery('
      INSERT INTO civicrm_relationship_vtx (relationship_id, relationship_type_id, orientation, near_type, near_contact_id, far_type, far_contact_id)
      SELECT rel.id, rel.relationship_type_id, 1, reltype.name_b_a, rel.contact_id_b, reltype.name_a_b, rel.contact_id_a
      FROM civicrm_relationship rel
      INNER JOIN civicrm_relationship_type reltype ON rel.relationship_type_id = reltype.id
      WHERE rel.id >= %1 AND rel.id <= %2
    ', $params);

    return TRUE;
  }

  public static function populateMissingContactTypeName() {
    $contactTypes = \Civi\Api4\ContactType::get()
      ->setCheckPermissions(FALSE)
      ->execute();
    foreach ($contactTypes as $contactType) {
      if (empty($contactType['name'])) {
        \Civi\Api4\ContactType::update()
          ->addWhere('id', '=', $contactType['id'])
          ->addValue('name', ucfirst(CRM_Utils_String::munge($contactType['label'])))
          ->setCheckPermissions(FALSE)
          ->execute();
      }
    }
    return TRUE;
  }

}

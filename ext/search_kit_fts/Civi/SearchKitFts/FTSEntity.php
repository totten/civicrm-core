<?php

namespace Civi\SearchKitFts;

use Civi\Api4\Action\GetLinks;
use Civi\Api4\Generic\AbstractAction;

/**
 * Virtual API entities provided by SearchDisplays of type fts_*
 */
class FTSEntity {

  /**
   * @param string $displayName
   * @param bool $checkPermissions
   * @return \Civi\Api4\Generic\AbstractAction
   * @throws \CRM_Core_Exception
   */
  public static function get(string $displayName, bool $checkPermissions = TRUE): AbstractAction {
    $ftsBackend = \Civi::service('fts')->getByName($displayName);
    $action = $ftsBackend->createApi4Action('get');
    $action->setCheckPermissions($checkPermissions);
    return $action;
  }

  /**
   * @param string $displayName
   * @param bool $checkPermissions
   * @return \Civi\Api4\Generic\BasicGetFieldsAction
   */
  public static function getFields(string $displayName, bool $checkPermissions = TRUE) {
    return (new FTSGetFieldsAction('FTS_' . $displayName, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $displayName
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\GetActions
   */
  public static function getActions(string $displayName, bool $checkPermissions = TRUE) {
    return (new \Civi\Api4\Action\GetActions('FTS_' . $displayName, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $displayEntity
   * @param bool $checkPermissions
   * @return \Civi\Api4\Action\GetLinks
   */
  public static function getLinks(string $displayEntity, bool $checkPermissions = TRUE): GetLinks {
    // TODO: Is this right?
    return (new GetLinks('FTS_' . $displayEntity, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param string $entityName
   * @return array
   */
  public static function permissions($entityName): array {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['access CiviCRM'],
    ];
  }

}

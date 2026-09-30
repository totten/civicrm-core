<?php

namespace Civi\SearchKitFts;

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
    return (new \Civi\Api4\Generic\BasicGetFieldsAction('FTS_' . $displayName, __FUNCTION__, function($action) use ($displayName) {
      $fields = [
        [
          'name' => 'fts',
          'title' => 'Fulltext Search',
          'description' => 'Fulltext search query across indexed columns',
          'type' => 'Field',
          'data_type' => 'String',
          'operators' => ['CONTAINS'],
        ],
      ];

      $displays = \Civi\SearchKitFts\Event\Subscriber\FTSEntitySubscriber::getFtsDisplays();
      foreach ($displays as $d) {
        if ($d['name'] === $displayName) {
          foreach ($d['settings']['columns'] ?? [] as $col) {
            $colName = $col['spec']['name'] ?? $col['key'] ?? NULL;
            if ($colName) {
              $fields[] = [
                'name' => $colName,
                'title' => $col['spec']['label'] ?? $colName,
                'type' => 'Field',
                'data_type' => $col['spec']['data_type'] ?? 'String',
                'operators' => ['=', '!=', 'CONTAINS', 'LIKE'],
              ];
            }
          }
          break;
        }
      }

      return $fields;
    }))->setCheckPermissions($checkPermissions);
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

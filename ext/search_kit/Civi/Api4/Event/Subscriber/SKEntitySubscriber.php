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

namespace Civi\Api4\Event\Subscriber;

use Civi\Api4\Generic\Traits\SavedSearchInspectorTrait;
use Civi\Api4\Job;
use Civi\Api4\SKEntity;
use Civi\Api4\Utils\CoreUtil;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Event\PostEvent;
use Civi\Core\Event\PreEvent;
use Civi\Core\Service\AutoService;
use Civi\Search\Meta;
use Civi\Search\SKEntityGenerator;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Manages tables and API entities created from search displays of type "entity"
 * @service
 * @internal
 */
class SKEntitySubscriber extends AutoService implements EventSubscriberInterface {

  use SavedSearchInspectorTrait;

  /**
   * @return array
   */
  public static function getSubscribedEvents(): array {
    return [
      'civi.api4.entityTypes' => 'on_civi_api4_entityTypes',
      'hook_civicrm_pre::SearchDisplay' => 'onPreSaveDisplay',
      'hook_civicrm_post::SearchDisplay' => 'onPostSaveDisplay',
    ];
  }

  /**
   * Register SearchDisplays of type 'entity'
   *
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  public static function on_civi_api4_entityTypes(GenericHookEvent $event): void {
    // Can't use the API to fetch search displays because this hook is called when the API boots
    foreach (_getSearchKitEntityDisplays() as $display) {
      $event->entities[$display['entityName']] = [
        'name' => $display['entityName'],
        'title' => $display['label'],
        'title_plural' => $display['label'],
        'description' => $display['settings']['description'] ?? NULL,
        'type' => ['DAOEntity', 'SavedSearch'],
        'table_name' => $display['tableName'],
        'class_args' => [$display['name']],
        'label_field' => NULL,
        'searchable' => 'secondary',
        'class' => SKEntity::class,
        'icon' => 'fa-search-plus',
        'primary_key' => $display['settings']['primaryKey'] ?? [],
        'search_fields' => [],
      ];
      foreach ($display['settings']['columns'] as $column) {
        $event->entities[$display['entityName']]['search_fields'][] = $column['spec']['name'];
      }
    }
  }

  /**
   * @param \Civi\Core\Event\PreEvent $event
   */
  public function onPreSaveDisplay(PreEvent $event): void {
    if (!$this->applies($event)) {
      return;
    }
    $oldName = $event->id ? \CRM_Core_DAO::getFieldValue('CRM_Search_DAO_SearchDisplay', $event->id) : NULL;
    $newName = $event->params['name'] ?? $oldName;
    $newSettings = $event->params['settings'] ?? NULL;
    // No changes made, nothing to do
    if (!$newSettings && $oldName === $newName && $event->action !== 'delete') {
      return;
    }
    // Drop the old backend if it exists
    if ($oldName) {
      $oldDisplay = \Civi\Api4\SearchDisplay::get(FALSE)
        ->addWhere('id', '=', $event->id)
        ->execute()->single();
      $oldDataMode = $oldDisplay['settings']['data_mode'] ?? 'table';
      $oldDataModes = \Civi\Search\AbstractBackend::getDataModes();
      $oldBackendClass = $oldDataModes[$oldDataMode]['backend'] ?? NULL;

      if ($oldBackendClass) {
        $oldSavedSearch = \Civi\Api4\SavedSearch::get(FALSE)
          ->addWhere('id', '=', $oldDisplay['saved_search_id'])
          ->execute()->single();
        /** @var \Civi\Search\AbstractBackend $oldBackend */
        $oldBackend = new $oldBackendClass(
          'SK_' . $oldName,
          $oldSavedSearch['api_entity'],
          $oldSavedSearch['api_params'],
          $oldDisplay['settings']
        );
        $oldBackend->destroy();
      }
    }
    if ($event->action === 'delete') {
      // Delete scheduled jobs when deleting entity
      Job::delete(FALSE)
        ->addWhere('api_entity', '=', 'SK_' . $oldName)
        ->execute();
      return;
    }
    // Build the new table/view or other backend
    $savedSearchID = $event->params['saved_search_id'] ?? \CRM_Core_DAO::getFieldValue('CRM_Search_DAO_SearchDisplay', $event->id, 'saved_search_id');
    $this->loadSavedSearch($savedSearchID);

    // Use primary keys from original table, if available
    $primaryKeys = CoreUtil::getInfoItem($this->savedSearch['api_entity'], 'primary_key') ?? [];
    $newSettings['primaryKey'] = [];
    // Format columns and assign primary keys
    foreach ($newSettings['columns'] as &$column) {
      $expr = $this->getSelectExpression($column['key']);
      if (!$expr) {
        continue;
      }
      // If saving for the first time and `spec` exists, it's probably coming fully-formed from hook_civicrm_managed/.mgd.php
      // Skip recalculating it in that case to prevent load-order issues. dev/core#6708
      if ($event->id || empty($column['spec'])) {
        $column['spec'] = Meta::formatFieldSpec($column, $expr);
      }
      if (in_array($column['key'], $primaryKeys)) {
        $newSettings['primaryKey'][] = $column['spec']['name'];
      }
    }
    // Store new settings with added column spec
    $event->params['settings'] = $newSettings;

    // Initialize the new backend!
    $dataMode = $event->params['settings']['data_mode'] ?? 'table';
    $dataModes = \Civi\Search\AbstractBackend::getDataModes();
    $backendClass = $dataModes[$dataMode]['backend'] ?? NULL;

    if ($backendClass) {
      /** @var \Civi\Search\AbstractBackend $backend */
      $backend = new $backendClass(
        'SK_' . $newName,
        $this->savedSearch['api_entity'],
        $this->savedSearch['api_params'],
        $event->params['settings']
      );
      $backend->initialize();
    }
  }

  /**
   * @param \Civi\Core\Event\PostEvent $event
   */
  public function onPostSaveDisplay(PostEvent $event): void {
    if ($this->applies($event)) {
      \CRM_Core_DAO_AllCoreTables::flush();
      \Civi::cache('metadata')->clear();
    }
  }

  /**
   * Check if pre/post hook applies to a SearchDisplay type 'entity'
   *
   * @param \Civi\Core\Event\PreEvent|\Civi\Core\Event\PostEvent $event
   * @return bool
   */
  private function applies(GenericHookEvent $event): bool {
    if ($event->entity !== 'SearchDisplay') {
      return FALSE;
    }
    $type = $event->params['type'] ?? $event->object->type ?? \CRM_Core_DAO::getFieldValue('CRM_Search_DAO_SearchDisplay', $event->id, 'type');
    return $type === 'entity';
  }

}

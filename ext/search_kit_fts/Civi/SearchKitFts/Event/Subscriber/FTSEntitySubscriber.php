<?php

namespace Civi\SearchKitFts\Event\Subscriber;

use Civi\Api4\Event\GetFieldsEvent;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Event\PostEvent;
use Civi\Core\Event\PreEvent;
use Civi\Core\Service\AutoService;
use Civi\SearchKitFts\FTSEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service
 * @internal
 */
class FTSEntitySubscriber extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents(): array {
    return [
      'civi.api4.entityTypes' => 'on_civi_api4_entityTypes',
      'civi.api4.getFields' => 'on_civi_api4_getFields',
      'hook_civicrm_pre::SearchDisplay' => 'onPreSaveDisplay',
      'hook_civicrm_post::SearchDisplay' => 'onPostSaveDisplay',
    ];
  }

  /**
   * Register APIv4 entities for SearchDisplays of type fts
   */
  public static function on_civi_api4_entityTypes(GenericHookEvent $event): void {
    foreach (self::getFtsDisplays() as $display) {
      $event->entities[$display['entityName']] = [
        'name' => $display['entityName'],
        'title' => $display['label'],
        'title_plural' => $display['label'],
        'description' => $display['settings']['description'] ?? NULL,
        'type' => ['FTS', 'SavedSearch'],
        'class' => FTSEntity::class,
        'class_args' => [$display['name']],
        'icon' => 'fa-search',
        'searchable' => 'secondary',
        'search_fields' => [],
      ];
      foreach ($display['settings']['columns'] ?? [] as $column) {
        $event->entities[$display['entityName']]['search_fields'][] = $column['spec']['name'] ?? $column['key'];
      }
    }
  }

  /**
   * Provide fields for FTS_* entities
   */
  public static function on_civi_api4_getFields(GetFieldsEvent $event): void {
    if (!str_starts_with($event->getEntityName(), 'FTS_')) {
      return;
    }

    $displayName = substr($event->getEntityName(), 4);
    $displays = self::getFtsDisplays();

    $display = NULL;
    foreach ($displays as $d) {
      if ($d['name'] === $displayName) {
        $display = $d;
        break;
      }
    }

    if (!$display) {
      return;
    }

    // Add fulltext 'fts' field spec
    $event->fields[] = [
      'name' => 'fts',
      'title' => 'Fulltext Search',
      'description' => 'Fulltext search across indexed columns',
      'type' => 'Field',
      'data_type' => 'String',
      'operators' => ['CONTAINS'],
    ];

    // Add individual column specs
    foreach ($display['settings']['columns'] ?? [] as $col) {
      $colName = $col['spec']['name'] ?? $col['key'] ?? NULL;
      if (!$colName) {
        continue;
      }
      $event->fields[] = [
        'name' => $colName,
        'title' => $col['spec']['label'] ?? $colName,
        'type' => 'Field',
        'data_type' => $col['spec']['data_type'] ?? 'String',
        'operators' => ['=', '!=', 'CONTAINS', 'LIKE'],
      ];
    }
  }

  public function onPreSaveDisplay(PreEvent $event): void {
    if (!$this->applies($event)) {
      return;
    }

    if ($event->action === 'delete' && $event->id) {
      $display = \CRM_Core_DAO::executeQuery("SELECT name, type, settings, saved_search_id FROM civicrm_search_display WHERE id = %1", [
        1 => [$event->id, 'Integer'],
      ]);
      if ($display->fetch()) {
        try {
          $fts = \Civi::service('fts')->getByName($display->name);
          $fts->destroy();
        }
        catch (\Exception $e) {
          // Log or ignore during deletion
        }
      }
    }
  }

  public function onPostSaveDisplay(PostEvent $event): void {
    if (!$this->applies($event)) {
      return;
    }

    if ($event->action !== 'delete' && isset($event->object->id)) {
      $id = $event->object->id;
      $display = \CRM_Core_DAO::executeQuery("SELECT name, type, settings, saved_search_id FROM civicrm_search_display WHERE id = %1", [
        1 => [$id, 'Integer'],
      ]);
      if ($display->fetch()) {
        try {
          $fts = \Civi::service('fts')->getByName($display->name);
          $fts->initialize();
        }
        catch (\Exception $e) {
          // Log or ignore during save
        }
      }
    }

    \CRM_Core_DAO_AllCoreTables::flush();
    \Civi::cache('metadata')->clear();
  }

  private function applies(GenericHookEvent $event): bool {
    if ($event->entity !== 'SearchDisplay') {
      return FALSE;
    }
    $type = $event->params['type'] ?? $event->object->type ?? \CRM_Core_DAO::getFieldValue('CRM_Search_DAO_SearchDisplay', $event->id ?? NULL, 'type');
    return $type === 'fts';
  }

  /**
   * Helper to retrieve all SearchDisplays of type fts
   */
  public static function getFtsDisplays(): array {
    try {
      $displays = \CRM_Core_DAO::executeQuery("SELECT sd.id, sd.name, sd.label, sd.type, sd.settings, sd.saved_search_id FROM civicrm_search_display sd WHERE sd.type = 'fts'");
      $results = [];
      while ($displays->fetch()) {
        $results[] = [
          'id' => $displays->id,
          'name' => $displays->name,
          'entityName' => 'FTS_' . $displays->name,
          'label' => $displays->label ?: ('FTS ' . $displays->name),
          'type' => $displays->type,
          'settings' => json_decode($displays->settings, TRUE) ?: [],
          'saved_search_id' => $displays->saved_search_id,
        ];
      }
      return $results;
    }
    catch (\Exception $e) {
      return [];
    }
  }

}

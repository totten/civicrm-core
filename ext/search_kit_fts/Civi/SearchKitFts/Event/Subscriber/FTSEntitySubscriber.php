<?php

namespace Civi\SearchKitFts\Event\Subscriber;

use Civi\Core\Event\GenericHookEvent;
use Civi\Core\Event\PostEvent;
use Civi\Core\Event\PreEvent;
use Civi\Core\Service\AutoService;
use Civi\Search\Meta;
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
      'hook_civicrm_pre::SearchDisplay' => 'onPreSaveDisplay',
      'hook_civicrm_post::SearchDisplay' => 'onPostSaveDisplay',
    ];
  }

  /**
   * Register APIv4 entities for SearchDisplays of type fts
   */
  public static function on_civi_api4_entityTypes(GenericHookEvent $event): void {
    foreach (\_search_kit_fts_displays() as $display) {
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
        $event->entities[$display['entityName']]['search_fields'][] = $column['spec']['name'] ?? Meta::createSqlName($column['key'])[0];
      }
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

}

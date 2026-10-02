<?php
declare(strict_types = 1);

// phpcs:disable PSR1.Files.SideEffects
require_once 'search_kit_fts.civix.php';
// phpcs:enable

use CRM_SearchKitFts_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function search_kit_fts_civicrm_config(\CRM_Core_Config $config): void {
  _search_kit_fts_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function search_kit_fts_civicrm_install(): void {
  _search_kit_fts_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function search_kit_fts_civicrm_enable(): void {
  _search_kit_fts_civix_civicrm_enable();
}

function _search_kit_fts_displays(): array {
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

/**
 * Lookup the search-display for a particular FTS Entity.
 *
 * @param string $entityName
 *   Ex: 'FTS_MyData'
 * @return array|null
 */
function _search_kit_fts_display(string $entityName): ?array {
  // Feels like slight overkill since it's not a cached lookup... but this is just a refactor-commit...
  return array_find(\_search_kit_fts_displays(),
    fn($d) => $d['entityName'] === $entityName
  );
}

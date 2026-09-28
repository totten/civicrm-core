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

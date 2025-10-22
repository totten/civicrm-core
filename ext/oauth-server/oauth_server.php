<?php

require_once 'oauth_server.civix.php';

use CRM_OauthServer_ExtensionUtil as E;

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function oauth_server_civicrm_config(&$config): void {
  _oauth_server_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function oauth_server_civicrm_install(): void {
  _oauth_server_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function oauth_server_civicrm_enable(): void {
  _oauth_server_civix_civicrm_enable();
}

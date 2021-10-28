<?php

require_once 'queuebench.civix.php';
// phpcs:disable
use CRM_Queuebench_ExtensionUtil as E;
// phpcs:enable

if (!function_exists('queuebench_log_file')) {
  function queuebench_log_file() {
    return '/tmp/queuebench.txt';
  }
}

function queuebench_doSomething($ctx, $contactId) {
  // printf("<%.3f> [#%d] %s(...%s)\n", microtime(1), posix_getpid(), __FUNCTION__, $contactId);

  // Do some Civi stuff, just to make sure it's all working.
  CRM_Core_TokenSmarty::render(['html' => 'Hello {contact.display_name}!'], ['contactId' => $contactId]);

  // Create some record that we were executed.
  file_put_contents(
    queuebench_log_file(),
    $contactId . "\n",
    FILE_APPEND
  );
}

/**
 * Implements hook_civicrm_config().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_config/
 */
function queuebench_civicrm_config(&$config) {
  _queuebench_civix_civicrm_config($config);
}

/**
 * Implements hook_civicrm_xmlMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_xmlMenu
 */
function queuebench_civicrm_xmlMenu(&$files) {
  _queuebench_civix_civicrm_xmlMenu($files);
}

/**
 * Implements hook_civicrm_install().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
 */
function queuebench_civicrm_install() {
  _queuebench_civix_civicrm_install();
}

/**
 * Implements hook_civicrm_postInstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
 */
function queuebench_civicrm_postInstall() {
  _queuebench_civix_civicrm_postInstall();
}

/**
 * Implements hook_civicrm_uninstall().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_uninstall
 */
function queuebench_civicrm_uninstall() {
  _queuebench_civix_civicrm_uninstall();
}

/**
 * Implements hook_civicrm_enable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
 */
function queuebench_civicrm_enable() {
  _queuebench_civix_civicrm_enable();
}

/**
 * Implements hook_civicrm_disable().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
 */
function queuebench_civicrm_disable() {
  _queuebench_civix_civicrm_disable();
}

/**
 * Implements hook_civicrm_upgrade().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
 */
function queuebench_civicrm_upgrade($op, CRM_Queue_Queue $queue = NULL) {
  return _queuebench_civix_civicrm_upgrade($op, $queue);
}

/**
 * Implements hook_civicrm_managed().
 *
 * Generate a list of entities to create/deactivate/delete when this module
 * is installed, disabled, uninstalled.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_managed
 */
function queuebench_civicrm_managed(&$entities) {
  _queuebench_civix_civicrm_managed($entities);
}

/**
 * Implements hook_civicrm_caseTypes().
 *
 * Generate a list of case-types.
 *
 * Note: This hook only runs in CiviCRM 4.4+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_caseTypes
 */
function queuebench_civicrm_caseTypes(&$caseTypes) {
  _queuebench_civix_civicrm_caseTypes($caseTypes);
}

/**
 * Implements hook_civicrm_angularModules().
 *
 * Generate a list of Angular modules.
 *
 * Note: This hook only runs in CiviCRM 4.5+. It may
 * use features only available in v4.6+.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules
 */
function queuebench_civicrm_angularModules(&$angularModules) {
  _queuebench_civix_civicrm_angularModules($angularModules);
}

/**
 * Implements hook_civicrm_alterSettingsFolders().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterSettingsFolders
 */
function queuebench_civicrm_alterSettingsFolders(&$metaDataFolders = NULL) {
  _queuebench_civix_civicrm_alterSettingsFolders($metaDataFolders);
}

/**
 * Implements hook_civicrm_entityTypes().
 *
 * Declare entity types provided by this module.
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_entityTypes
 */
function queuebench_civicrm_entityTypes(&$entityTypes) {
  _queuebench_civix_civicrm_entityTypes($entityTypes);
}

/**
 * Implements hook_civicrm_themes().
 */
function queuebench_civicrm_themes(&$themes) {
  _queuebench_civix_civicrm_themes($themes);
}

// --- Functions below this ship commented out. Uncomment as required. ---

/**
 * Implements hook_civicrm_preProcess().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_preProcess
 */
//function queuebench_civicrm_preProcess($formName, &$form) {
//
//}

/**
 * Implements hook_civicrm_navigationMenu().
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_navigationMenu
 */
//function queuebench_civicrm_navigationMenu(&$menu) {
//  _queuebench_civix_insert_navigation_menu($menu, 'Mailings', array(
//    'label' => E::ts('New subliminal message'),
//    'name' => 'mailing_subliminal_message',
//    'url' => 'civicrm/mailing/subliminal',
//    'permission' => 'access CiviMail',
//    'operator' => 'OR',
//    'separator' => 0,
//  ));
//  _queuebench_civix_navigationMenu($menu);
//}

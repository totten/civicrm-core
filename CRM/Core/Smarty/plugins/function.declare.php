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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * Declare a variable and its type. In debug mode, assert that the variable matches the type.
 *
 * Examples:
 *
 * - {declare var="name" type="string|null"}
 * - {declare var="session" type="CRM_Core_Session"}
 * - {declare var="contact" api4-entity="Contact"}
 *
 * @param array $params
 *   Properties:
 *   - var: string, the name of the Smarty variable
 *   - type: string, PHP type expression.
 *   - api4-entity: string, the name of an APIv4 entity. The record should be a valid entity-record.
 *   - strict: bool, optional
 *   Ex: ['var' => 'session', 'type' => 'CRM_Core_Session']
 * @param CRM_Core_Smarty $smarty
 *   The Smarty object.
 *
 * @return void
 */
function smarty_function_declare($params, &$smarty): void {
  if (!Civi::settings()->get('debug_enabled')) {
    return;
  }

  $params['strict'] ??= TRUE;
  $value = $smarty->getTemplateVars($params['var']);
  if (!empty($params['api4-entity'])) {
    $params['type'] ??= 'array';
  }

  if (isset($params['type'])) {
    if (!CRM_Utils_Type::validatePhpType($value, $params['type'], $params['strict'])) {
      $actualType = is_object($value) ? get_class($value) : gettype($value);
      // FIXME: Is there a way to get the name of the tpl file?
      throw new CRM_Core_Exception(sprintf('Smarty variable "%s" should match type "%s". Found "%s".',
        $params['var'], $params['type'], $actualType));
    }
  }

  if (isset($params['api4-entity'])) {
    $fields = civicrm_api4($params['api4-entity'], 'getFields');
    $issues = [];
    $liveKeys = array_map(fn($k) => explode(':', $k)[0], array_keys($value));
    if ($params['strict'] && $unknownKeys = array_diff($liveKeys, $fields->column('name'))) {
      $issues[] = sprintf('Smarty variable "%s" (APIv4 %s) has unknown fields: %s',
        $params['var'], $params['api4-entity'], implode(', ', $unknownKeys));
    }
    if ($issues) {
      throw new CRM_Core_Exception(implode("\n", $issues));
    }
  }
}

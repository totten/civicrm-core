<?php

require_once 'civi_data_translate.civix.php';

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Strings;
use CRM_CiviDataTranslate_ExtensionUtil as E;

/**
 * Implements hook_civicrm_apiWrappers()
 *
 * @link https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_apiWrappers/
 *
 * @param array $wrappers
 * @param AbstractAction $apiRequest
 *
 * @throws \API_Exception
 */
function civi_data_translate_civicrm_apiWrappers(&$wrappers, $apiRequest) {
  // Only implement for apiv4 & not in a circular way.
  if ($apiRequest['entity'] === 'Strings'
    || $apiRequest['entity'] === 'Entity'
    || !$apiRequest instanceof AbstractAction
    || !in_array($apiRequest['action'], ['get', 'create', 'update', 'save'])
  ) {
    return;
  }

  try {
    $apiLanguage = $apiRequest->getLanguage();
    if (!$apiLanguage || $apiRequest->getLanguage() === Civi::settings()->get('lcMessages')) {
      return;
    }
  }
  catch (\API_Exception $e) {
    // I think that language would always be a property based on the generic action, but in case...
    return;
  }

  if (in_array($apiRequest['action'], ['create', 'update', 'save'], TRUE)) {
    $fieldsToTranslate = civi_data_translate_civicrm_fields_to_save_strings_for($apiRequest);
    if (!empty($fieldsToTranslate)) {
      $wrapper = civi_data_translate_get_api_wrapper_class($apiRequest['action'], $fieldsToTranslate);
      $wrappers[] = $wrapper;
    }

  }
  if ($apiRequest['action'] === 'get') {
    if (!isset(\Civi::$statics['cividatatranslate']['translate_fields'][$apiRequest['entity']][$apiRequest->getLanguage()])) {
      $fields = Strings::get()
        ->addWhere('entity_table', '=', CRM_Core_DAO_AllCoreTables::getTableForEntityName($apiRequest['entity']))
        ->addWhere('language', '=', $apiRequest->getLanguage())
        ->setCheckPermissions(FALSE)
        ->setSelect(['entity_field', 'entity_id', 'string'])
        ->execute();
      foreach ($fields as $field) {
        \Civi::$statics['cividatatranslate']['translate_fields'][$apiRequest['entity']][$apiRequest->getLanguage()][$field['entity_id']][$field['entity_field']] = $field['string'];
      }
    }
    if (!empty(\Civi::$statics['cividatatranslate']['translate_fields'][$apiRequest['entity']][$apiRequest->getLanguage()])) {
      $wrappers[] = new CRM_CiviDataTranslate_ApiGetWrapper(\Civi::$statics['cividatatranslate']['translate_fields'][$apiRequest['entity']][$apiRequest->getLanguage()]);
    }

  }
}

/**
 * Get the wrapper class appropriate to the action.
 *
 * @param string $action
 * @param array $fieldsToTranslate
 *
 * @return \CRM_CiviDataTranslate_ApiCreateWrapper|\CRM_CiviDataTranslate_ApiSaveWrapper|\CRM_CiviDataTranslate_ApiUpdateWrapper
 */
function civi_data_translate_get_api_wrapper_class(string $action, array $fieldsToTranslate) {
  switch ($action) {
    case 'create' :
      return new CRM_CiviDataTranslate_ApiCreateWrapper($fieldsToTranslate);

    case 'update':
      return new CRM_CiviDataTranslate_ApiUpdateWrapper($fieldsToTranslate);

    case 'save':
      return new CRM_CiviDataTranslate_ApiSaveWrapper($fieldsToTranslate);
  }

}

/**
 * Get the fields that language specific strings should be saved for.
 *
 * @param AbstractAction $apiRequest
 *
 * @return array
 */
function civi_data_translate_civicrm_fields_to_save_strings_for($apiRequest) {
  $fieldsToMap = array_fill_keys(civi_data_translate_get_strings_to_set($apiRequest['entity']), 1);
  if ($apiRequest->getActionName() === 'save') {
    return array_intersect_key(
      $apiRequest->getDefaults(),
      $fieldsToMap
    );
  }
  return array_intersect_key(
    $apiRequest->getValues(),
    $fieldsToMap
  );
}

/**
 * Get the strings to translate per entity.
 *
 * So far this is just a few strings for one entity. In the future we need
 * a more metadata approach.
 *
 * @param string $entity
 *
 * @return array
 */
function civi_data_translate_get_strings_to_set($entity) {
  $strings = ['MessageTemplate' => ['msg_html', 'msg_text', 'subject']];
  return $strings[$entity] ?? [];
}

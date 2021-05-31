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
  } catch (\API_Exception $e) {
    // I think that language would always be a property based on the generic action, but in case...
    return;
  }

  if (empty(CRM_Core_BAO_Strings::getTranslatedFields($apiRequest['entity']))) {
    // There won't be anything to translate.
    return;
  }

  $wrapper = civi_data_translate_get_api_wrapper_class($apiRequest);
  if ($wrapper !== NULL) {
    $wrappers[] = $wrapper;
  }

  if ($apiRequest['action'] === 'get') {
    if (!isset(\Civi::$statics['cividatatranslate']['translate_fields'][$apiRequest['entity']][$apiRequest->getLanguage()])) {
      $fields = Strings::get(0)
        ->addWhere('entity_table', '=', CRM_Core_DAO_AllCoreTables::getTableForEntityName($apiRequest['entity']))
        ->addWhere('language', '=', $apiRequest->getLanguage())
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
 * @return \CRM_CiviDataTranslate_ApiCreateWrapper|\CRM_CiviDataTranslate_ApiSaveWrapper|\CRM_CiviDataTranslate_ApiUpdateWrapper|NULL
 */
function civi_data_translate_get_api_wrapper_class(AbstractAction $apiRequest) {
  switch ($apiRequest['action']) {
    case 'create' :
    case 'update':
      $activeFields = $apiRequest->getValues();
      break;

    case 'save':
      $activeFields = array_unique(array_merge(
        CRM_Utils_Array::findColumns($apiRequest->getRecords()),
        array_keys($apiRequest->getDefaults())
      ));
      break;

    default:
      // Short circuit - not our problem
      return NULL;
  }

  $fieldsToMap = CRM_Core_BAO_Strings::getTranslatedFields($apiRequest['entity']);
  $fieldsToTranslate = array_intersect($activeFields, array_keys($fieldsToMap));
  if (empty($fieldsToTranslate)) {
    return NULL;
  }

  switch ($apiRequest['action']) {
    case 'create' :
      return new CRM_CiviDataTranslate_ApiCreateWrapper($fieldsToTranslate);

    case 'update':
      return new CRM_CiviDataTranslate_ApiUpdateWrapper($fieldsToTranslate);

    case 'save':
      return new CRM_CiviDataTranslate_ApiSaveWrapper($fieldsToTranslate);

  }

}

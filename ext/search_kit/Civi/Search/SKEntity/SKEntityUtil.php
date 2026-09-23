<?php

namespace Civi\Search\SKEntity;

class SKEntityUtil {

  /**
   * Get all registered data modes / backends.
   *
   * @return array
   */
  public static function getDataModes(): array {
    $dataModes = [];
    $dummy = NULL;
    \CRM_Utils_Hook::singleton()->invoke(
      ['dataModes'],
      $dataModes,
      $dummy, $dummy, $dummy, $dummy, $dummy,
      'civicrm_skDataModes'
    );
    return $dataModes;
  }

}

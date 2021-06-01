<?php
namespace Civi\Api4;

/**
 * Store supplemental translations for strings on DB entitiesi.
 *
 * @package Civi\Api4
 */
class Translation extends Generic\DAOEntity {

  public static function permissions() {
    return [
      // If we add support for DynamicFKAuthorization, then these might be relaxed. As is, 'translate CiviCRM' is interpreted to mean "translate anything and everything in CiviCRM".
      'meta' => ['access CiviCRM'],
      'default' => ['translate CiviCRM'],
    ];
  }

}

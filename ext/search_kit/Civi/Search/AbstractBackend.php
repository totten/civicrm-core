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

namespace Civi\Search;

abstract class AbstractBackend {

  public function __construct(
    protected string $skEntity,
    protected string $realEntity,
    protected array $realParams,
    protected array $settings
  ) {}

  // Similar to "CREATE TABLE"
  abstract public function initialize();

  // Similar to "TRUNCATE TABLE"
  abstract public function clearData();

  abstract public function fillData();

  // Similar to "DROP TABLE".
  abstract public function destroy();

  /**
   * Get all registered data modes / backends.
   *
   * @return array
   */
  public static function getDataModes(): array {
    $backends = [];
    $dummy = NULL;
    \CRM_Utils_Hook::singleton()->invoke(
      ['backends'],
      $backends,
      $dummy, $dummy, $dummy, $dummy, $dummy,
      'civicrm_skDataModes'
    );
    return $backends;
  }

}

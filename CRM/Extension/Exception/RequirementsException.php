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
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

/**
 * The requirements for an extension were not satisfied.
 */
class CRM_Extension_Exception_RequirementsException extends CRM_Extension_Exception {

  /**
   * @var array[]
   *   Each item is: [ext => string, source => string, type => string, message => $string]
   * @see \CRM_Extension_Requirements::$errors
   */
  protected $errors;

  /**
   * @param array $errors
   *   Each item is: [ext => string, source => string, type => string, message => $string]
   */
  public function __construct(array $errors) {
    $exts = array_unique(array_column($errors, 'ext'));
    parent::__construct('Requirements have not been satisfied for extension(s): ' . implode(' ', $exts), 'unment_req');
    $this->errors = $errors;
  }

  public function createVerboseMessage(): string {
    $buf = $this->getMessage() . ":\n";
    foreach ($this->errors as $error) {
      $buf .= sprintf("* (%1) %2\n", $error['ext'], $error['message']);
    }
    return $buf;
  }

}

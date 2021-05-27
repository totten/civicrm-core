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


namespace Civi\Api4\Generic;

use Civi\Api4\Event\ValidateValuesEvent;

/**
 * Base class for all `Create` api actions.
 *
 * @method $this setValues(array $values) Set all field values from an array of key => value pairs.
 * @method array getValues() Get field values.
 *
 * @package Civi\Api4\Generic
 */
abstract class AbstractCreateAction extends AbstractAction {

  use \Civi\Api4\Generic\Traits\CheckRequiredTrait;

  /**
   * Field values to set for the new $ENTITY.
   *
   * @var array
   */
  protected $values = [];

  /**
   * @param string $fieldName
   * @return mixed|null
   */
  public function getValue(string $fieldName) {
    return $this->values[$fieldName] ?? NULL;
  }

  /**
   * Add a field value.
   * @param string $fieldName
   * @param mixed $value
   * @return $this
   */
  public function addValue(string $fieldName, $value) {
    $this->values[$fieldName] = $value;
    return $this;
  }

  /**
   * @throws \API_Exception
   */
  protected function validateValues() {
    $this->checkRequired([$this->values]);
    $e = new ValidateValuesEvent($this, [$this->getValues()], new \CRM_Utils_LazyArray(function () {
      return [['old' => NULL, 'new' => $this->getValues()]];
    }));
    \Civi::dispatcher()->dispatch('civi.api4.validate', $e);
    if (!empty($e->errors)) {
      throw $e->toException();
    }
  }

}

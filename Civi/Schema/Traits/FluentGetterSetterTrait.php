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

namespace Civi\Schema\Traits;

/**
 * Automatically define getter/setter methods for public and protected fields.
 *
 * You may optionally implement more specialized getters/setters for specific properties.
 *
 * Fields which begin with `_` will be excluded.
 *
 * @package Civi\Schema\Traits
 */
trait FluentGetterSetterTrait {

  /**
   * Magic function to provide getters/setters.
   *
   * @param $name
   * @param $arguments
   * @return static|mixed
   * @throws \CRM_Core_Exception
   */
  public function __call($name, $arguments) {
    $param = lcfirst(substr($name, 3));
    if (!$param || $param[0] == '_') {
      throw new \CRM_Core_Exception('Unknown protected parameter: ' . $name);
    }
    $mode = substr($name, 0, 3);
    $props = self::getProtectedProperties();

    if (isset($props[$param])) {
      switch ($mode) {
        case 'get':
          return $this->$param;

        case 'set':
          $this->$param = $arguments[0];
          return $this;
      }
    }
    throw new \CRM_Core_Exception('Unknown parameter: ' . $name);
  }

  /**
   * @return array
   * @throws \ReflectionException
   */
  private static function getProtectedProperties(): array {
    if (!isset(\Civi::$statics['FluentGetterSetter'][static::CLASS])) {
      $clazz = new \ReflectionClass(static::CLASS);
      $fields = [];
      foreach ($clazz->getProperties(\ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PUBLIC) as $property) {
        if (!$property->isStatic()) {
          $fields[$property->getName()] = TRUE;
        }
      }
      unset($clazz);
      \Civi::$statics['FluentGetterSetter'][static::CLASS] = $fields;
    }
    return \Civi::$statics['FluentGetterSetter'][static::CLASS];
  }

}

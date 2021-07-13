<?php

namespace Civi\Schema\Traits;

use Civi\Api4\Utils\ReflectionUtils;
use Civi\Schema\MappedFieldSpec;

trait ArrayMappingTrait {

  /**
   * List of unrecognized/unmapped array values.
   *
   * @var array
   */
  protected $_extras = [];

  /**
   * @return MappedFieldSpec[]
   *   A list of field-specs that are used in the given format, keyed by their name in that format.
   *   If the implementation does not understand a specific format, return NULL.
   */
  public function getFields(): array {
    return ReflectionUtils::getFields(static::CLASS, \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PUBLIC, MappedFieldSpec::CLASS);
  }

  public function importArray(array $values) {
    $MISSING = new \stdClass();

    foreach ($this->getFields() as $field) {
      /** @var MappedFieldSpec $field */
      foreach ($field->getMapping() ?: [] as $path) {
        $value = \CRM_Utils_Array::pathGet($values, $path, $MISSING);
        if ($value !== $MISSING) {
          $setter = 'set' . ucfirst($field->getName());
          $this->$setter($value);
          \CRM_Utils_Array::pathUnset($values, $path, TRUE);
        }
      }
    }

    $methods = ReflectionUtils::findMethodPrefix(static::CLASS, 'importArrayExtra', \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED);
    foreach ($methods as $method) {
      $this->{$method->getName()}($values);
    }

    if (!empty($values)) {
      $this->_extras = array_merge($this->_extras ?? [], $values);
      $values = [];
    }

    return $this;
  }

  public function exportArray(): array {
    $values = $this->_extras ?: [];
    foreach ($this->getFields() as $key => $field) {
      /** @var MappedFieldSpec $field */
      foreach ($field->getMapping() ?: [] as $path) {
        $getter = 'get' . ucfirst($field->getName());
        \CRM_Utils_Array::pathSet($values, $path, $this->$getter());
      }
    }

    $methods = ReflectionUtils::findMethodPrefix(static::CLASS, 'exportArrayExtra', \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED);
    foreach ($methods as $method) {
      $this->{$method->getName()}(...[&$values]);
    }
    return $values;
  }

}

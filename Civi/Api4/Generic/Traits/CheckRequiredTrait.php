<?php
namespace Civi\Api4\Generic\Traits;

/**
 * The CheckRequiredTrait provides a helper, `checkRequired()`, to determine if required fields are specified.
 * This would be consulted when adding a new record.
 *
 * This should be moved to a subscriber ('civi.api4.validate') that reports issues via `addError()`. However, for the moment, it
 * relies on things which are in the action class (e.g. entityFields(), evaluateCondition(), $isNew<=>$idField), and it can't
 * be fully extracted until those are changed.
 *
 * @deprecated
 */
trait CheckRequiredTrait {

  abstract protected static function getEntityName();

  abstract protected static function getActionName();

  abstract protected function entityFields();

  abstract protected function evaluateCondition($expr, $vars);

  /**
   * When creating new records, ensure that the 'required' fields are present. Throws an exception if any fields are missing.
   *
   * @param array $records
   * @param callable|TRUE $isNew A function that can distinguish whether the record is new, or constant TRUE if all records are new.
   * @throws \API_Exception
   */
  protected function checkRequired($records, $isNew = TRUE) {
    $unmatched = [];

    foreach ($records as $record) {
      if ($isNew === TRUE || $isNew($record)) {
        $unmatched = array_unique(array_merge($unmatched, $this->checkRequiredFieldValues($record)));
      }
    }
    if ($unmatched) {
      throw new \API_Exception("Mandatory values missing from Api4 {$this->getEntityName()}::{$this->getActionName()}: " . implode(", ", $unmatched), "mandatory_missing", ["fields" => $unmatched]);
    }
  }

  /**
   * Validates required fields for actions which create a new object.
   *
   * @param $values
   * @return array
   * @throws \API_Exception
   */
  protected function checkRequiredFieldValues($values) {
    $unmatched = [];
    foreach ($this->entityFields() as $fieldName => $fieldInfo) {
      if (!isset($values[$fieldName]) || $values[$fieldName] === '') {
        if (!empty($fieldInfo['required']) && !isset($fieldInfo['default_value'])) {
          $unmatched[] = $fieldName;
        }
        elseif (!empty($fieldInfo['required_if'])) {
          if ($this->evaluateCondition($fieldInfo['required_if'], ['values' => $values])) {
            $unmatched[] = $fieldName;
          }
        }
      }
    }
    return $unmatched;
  }

}

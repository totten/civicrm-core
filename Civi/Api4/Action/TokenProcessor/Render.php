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

namespace Civi\Api4\Action\TokenProcessor;

use Civi\Api4\Event\ValidateValuesEvent;
use Civi\Token\TokenProcessor;

/**
 *
 *
 * @method $this setRows(array $rows) Set the list of records to be rendered.
 * @method array getRows()
 * @method $this setMessages(array $rows) Set of messages to be rendered.
 * @method array getMessages()
 * @method $this setContext(array $context) Array of defaults.
 * @method array getContext()
 */
class Render extends \Civi\Api4\Generic\AbstractAction {

  protected $rows = [];

  protected $messages = [];

  protected $context = [];

  public function _run(\Civi\Api4\Generic\Result $result) {
    $this->validateValues();
    $messages = $this->getMessages();

    $proc = new TokenProcessor(\Civi::dispatcher(), $this->getContext());
    foreach ($this->getRows() as $row) {
      $proc->addRow($row);
    }
    foreach ($messages as $message) {
      $defaultType = preg_match('/html/i', $message['name']) ? 'text/html' : 'text/plain';
      $proc->addMessage($message['name'], $message['content'], $message['type'] ?? $defaultType);
    }
    foreach ($proc->getRows() as $row) {
      /** @var \Civi\Token\TokenRow $row */
      $out = [];
      foreach ($messages as $message) {
        $out[$message['name']] = $row->render($message['name']);
      }
      $result[] = $out;
    }
  }

  /**
   * The token-processor supports a range of context parameters. We enforce different security rules for each kind of input.
   *
   * Broadly, this distinguishes between a few values:
   * - Autoloaded data (e.g. 'contactId', 'activityId'). We need to ensure that the specific records are visible and extant.
   * - Inputted data (e.g. 'contact'). We merely ensure that these are type-correct.
   * - Prohibited/extended options, e.g. 'smarty'
   */
  protected function validateValues() {
    $rows = $this->getEffectiveRows();
    $e = new ValidateValuesEvent($this, $rows, new \CRM_Utils_LazyArray(function () use ($rows) {
      return array_map(
        function ($row) {
          return ['old' => NULL, 'new' => $row];
        },
        $rows
      );
    }));
    $this->onValidateValues($e);
    \Civi::dispatcher()->dispatch('civi.api4.validate', $e);
    if (!empty($e->errors)) {
      throw $e->toException();
    }
  }

  protected function onValidateValues(ValidateValuesEvent $e) {
    $columns = \CRM_Utils_Array::asColumns($e->records, TRUE);
    $entityKeys = ['contactId' => 'Contact', 'contributionId' => 'Contribution', 'activityId' => 'Activity', 'caseId' => 'CiviCase'];
    $passthru = ['contact' => 'array', 'contribution' => 'array', 'activity' => 'array', 'case' => 'array'];
    $validKeys = array_merge(array_keys($entityKeys), array_keys($passthru));
    $resolvedIds = [];

    foreach ($entityKeys as $column => $entity) {
      if (isset($columns[$column])) {
        $resolvedIds[$column] = \civicrm_api4($entityKeys[$column], 'get', [
          'checkPermissions' => $this->getCheckPermissions(),
          'select' => 'id',
          'where' => [['id', 'IN', array_filter($columns[$column], 'is_numeric')]],
        ])->indexBy('id');
      }
    }

    $isType = function($value, $type) {
      return ($type === 'array' && is_array($value)) || (\CRM_Utils_Type::validate($value, $type, FALSE));
    };

    foreach ($e->records as $rowKey => $row) {
      foreach ($row as $column => $value) {
        if (isset($entityKeys[$column]) && !isset($resolvedIds[$column][$value])) {
          $e->addError($rowKey, $column, 'nonexistent_id', ts('Record does not exist or is not visible.'));
        }
        if (isset($passthru[$column]) && !$isType($value, $passthru[$column])) {
          $e->addError($rowKey, $column, 'wrong_type', ts('Field should have type %1.', [1 => $passthru[$column]]));
        }
        if (!in_array($column, $validKeys)) {
          $e->addError($rowKey, $column, 'unknown_field', ts('Templates may only be executed with supported fields.'));
        }
      }
    }
  }

  /**
   * Get a list of effective rows, based on combining the general context with each per-row context.
   *
   * @return array
   */
  protected function getEffectiveRows(): array {
    $context = $this->getContext();
    $allRows = [];
    foreach ($this->getRows() as $rowKey => $row) {
      $allRows[$rowKey] = array_merge($context, $row);
    }
    return $allRows;
  }

}

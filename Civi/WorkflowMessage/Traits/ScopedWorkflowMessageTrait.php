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

namespace Civi\WorkflowMessage\Traits;

/**
 * Basic implementation of WorkflowMessageInterface, in which all import()/export()
 * values are split across distinct scopes.
 */
trait ScopedWorkflowMessageTrait {

  /**
   * @see \Civi\WorkflowMessage\WorkflowMessageInterface::export()
   * @see \Civi\WorkflowMessage\Traits\ScopedFieldTrait::export()
   */
  abstract protected function exportScope(string $scope): array;

  /**
   * @see \Civi\WorkflowMessage\WorkflowMessageInterface::export()
   * @see \Civi\WorkflowMessage\Traits\ScopedFieldTrait::export()
   */
  abstract protected function importScope(string $scope, array $values);

  /**
   * @inheritDoc
   * @see \Civi\WorkflowMessage\WorkflowMessageInterface::export()
   */
  public function export(?string $scope = NULL): array {
    if ($scope !== NULL) {
      return $this->exportScope($scope);
    }

    $values = $this->exportScope('envelope');
    $values['tplParams'] = $this->exportScope('tplParams');
    $values['tokenContext'] = $this->exportScope('tokenContext');
    if (isset($values['tokenContext']['contactId'])) {
      // Top-level 'contactId' is alias for 'tokenContext.contactId'
      $values['contactId'] = $values['tokenContext']['contactId'];
    }
    return $values;
  }

  /**
   * @inheritDoc
   * @see \Civi\WorkflowMessage\WorkflowMessageInterface::import()
   */
  public function import(array $values) {
    if (isset($values['model'])) {
      if ($values['model'] !== $this) {
        throw new \CRM_Core_Exception(sprintf("%s: Cannot apply mismatched model", get_class($this)));
      }
      unset($values['model']);
    }

    // Top-level 'contactId' is alias for 'tokenContext.contactId'
    \CRM_Utils_Array::pathMove($values, ['contactId'], ['tokenContext', 'contactId']);

    foreach (['tplParams', 'tokenContext', 'modelProps', 'envelope'] as $scope) {
      if (isset($values[$scope])) {
        $this->importScope($scope, $values[$scope]);
        unset($values[$scope]);
      }
    }

    // All unrecognized keys are considered "envelope".
    $this->importScope('envelope', $values);
  }

  /**
   * Determine if the data for this workflow message is complete/well-formed.
   *
   * @return array
   *   A list of errors and warnings. Each record defines
   *   - severity: string, 'error' or 'warning'
   *   - fields: string[], list of fields implicated in the error
   *   - name: string, symbolic name of the error/warning
   *   - message: string, printable message describing the problem
   * @see \Civi\WorkflowMessage\WorkflowMessageInterface::validate()
   */
  public function validate(): array {
    // TODO
    return [];
  }

}

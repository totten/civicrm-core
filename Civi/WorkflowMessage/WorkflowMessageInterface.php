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

namespace Civi\WorkflowMessage;

interface WorkflowMessageInterface {

  /**
   * @return \Civi\WorkflowMessage\FieldSpec[]
   *   A list of field-specs that are used in the given format, keyed by their name in that format.
   *   If the implementation does not understand a specific format, return NULL.
   */
  public function getFields(): array;

  /**
   * @param string|null $scope
   *   If NULL, the format matches the sendTemplate/renderTemplate format.
   *   Otherwise, it is limited to a particular key.
   *   Ex: 'tplParams', 'tokenContext'
   *
   * @return array
   * @see \Civi\WorkflowMessage\Traits\ScopedWorkflowMessageTrait::export()
   */
  public function export(?string $scope = NULL): array;

  /**
   * Import values from some scope.
   *
   * Ex: $message->import(['tplParams' => ['sm_art_stuff' => 123]]);
   *
   * @param array $values
   *
   * @return $this
   * @see \Civi\WorkflowMessage\Traits\ScopedWorkflowMessageTrait::import()
   */
  public function import(array $values);

  /**
   * Determine if the data for this workflow message is complete/well-formed.
   *
   * @return array
   *   A list of errors and warnings. Each record defines
   *   - severity: string, 'error' or 'warning'
   *   - fields: string[], list of fields implicated in the error
   *   - name: string, symbolic name of the error/warning
   *   - message: string, printable message describing the problem
   */
  public function validate(): array;

}

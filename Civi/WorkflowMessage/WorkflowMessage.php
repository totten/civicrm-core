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

use Civi\Schema\Traits\ArrayMappingTrait;
use Civi\Schema\Traits\FluentGetterSetterTrait;
use Civi\WorkflowMessage\Traits\FinalHelperTrait;

/**
 * A WorkflowMessage describes the inputs to an automated email messages.
 *
 * Typical base-class for describing the inputs for a workflow email template.
 *
 * These classes may be instantiated by either class-name or workflow-name.
 *
 * Ex: $msgWf = new \CRM_Foo_WorkflowMessage_MyAlert(['tplParams' => [...tplValues...]]);
 * Ex: $msgWf = WorkflowMessage::create('my_alert_name', ['tplParams' => [...tplValues...]]);
 *
 * Instantiating by class-name will provide better hinting and inspection.
 * However, some workflows may not have specific classes at the time of writing.
 * Instantiating by workflow-name will work regardless of whether there is a specific class.
 */
class WorkflowMessage implements WorkflowMessageInterface {

  use ArrayMappingTrait;
  use FluentGetterSetterTrait;
  use FinalHelperTrait;

  /**
   * WorkflowMessage constructor.
   *
   * @param array $imports
   *   List of values to import.
   *   Ex: ['tplParams' => [...tplValues...], 'tokenContext' => [...tokenData...]]
   */
  public function __construct(array $imports = []) {
    $this->importArray($imports);
  }

  /**
   * Create a new instance of the workflow-message context.
   *
   * @param string $wfName
   *   Name of the workflow.
   *   Ex: 'case_activity'
   * @param array $imports
   *   List of data to use when populating the message.
   *
   *   The parameters may be given in a mix of formats. This mix reflects two modes of operation:
   *
   *   - (Informal/Generic) Traditionally, workflow-messages did not have formal parameters. Instead,
   *     they relied on a mix of un(der)documented/unverifiable inputs -- supplied as a mix of Smarty
   *     assignments, token-data, and sendTemplate() params.
   *   - (Formal) More recently, workflow-messages could be defined with a PHP class that lists the
   *     inputs explicitly.
   *
   *   You may supply inputs using these keys:
   *
   *   - `tplParams` (array): Smarty data. These values go to `$smarty->assign()`.
   *   - `tokenContext` (array): Token-processing data. These values go to `$tokenProcessor->context`.
   *   - `envelope` (array): Email delivery data. These values go to `sendTemplate(...)`
   *   - `modelProps` (array): Formal parameters defined by a class.
   *
   *   Informal workflow-messages ONLY support 'tplParams', 'tokenContext', and/or 'envelope'.
   *   Formal workflow-messages accept any format.
   *
   * @return \Civi\WorkflowMessage\WorkflowMessageInterface
   *   If there is a workflow-message class, then it will return an instance of that class.
   *   Otherwise, it will return an instance of `GenericWorkflowMessage`.
   */
  public static function create(string $wfName, array $imports = []) {
    $classMap = \CRM_Core_BAO_MessageTemplate::getWorkflowNameClassMap();
    $class = $classMap[$wfName] ?? 'Civi\WorkflowMessage\GenericWorkflowMessage';
    $imports['envelope']['valueName'] = $wfName;
    return new $class($imports);
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

  /**
   * Get a list of key-value pairs to add to the token-context.
   *
   * @param array $export
   *   Modifiable list of export-values.
   */
  protected function exportArrayExtraTokenContext(array &$export): void {
    $export['tokenContext']['controller'] = static::CLASS;
    $export['tokenContext']['smarty'] = TRUE;
    // Hmm ^^?
  }

  protected function exportArrayExtraEnvelope(array &$export): void {
    if ($wfName = \CRM_Utils_Constant::value(static::CLASS . '::WORKFLOW')) {
      $export['valueName'] = $wfName;
    }
    if ($wfGroup = \CRM_Utils_Constant::value(static::CLASS . '::GROUP')) {
      $export['groupName'] = $wfGroup;
    }
  }

  protected function importArrayExtraEnvelope(array &$values): void {
    if ($wfName = \CRM_Utils_Constant::value(static::CLASS . '::WORKFLOW')) {
      if (isset($values['valueName']) && $wfName === $values['valueName']) {
        unset($values['valueName']);
      }
    }
    if ($wfGroup = \CRM_Utils_Constant::value(static::CLASS . '::GROUP')) {
      if (isset($values['groupName']) && $wfGroup === $values['groupName']) {
        unset($values['groupName']);
      }
    }
  }

}

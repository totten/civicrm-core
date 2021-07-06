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

use Civi\Api4\Utils\ReflectionUtils;

/**
 * The ReflectiveWorkflowTrait makes it easier to define
 * workflow-messages using conventional PHP class-modeling. Thus:
 *
 * - As general rule, you should simply define public PHP properties.
 *   Callers will fill these in.
 * - Each property may use the `@scope` annotation to indicate if it is mapped
 *   into Smarty/Tpl context or TokenProcessor context.
 * - When handling workflow-message operations (e.g. getFields, import, export),
 *   these properties will be automatically used.
 * - If you need special behaviors (e.g. outputting derived data to the
 *   Smarty context automatically), then you may override certain methods
 *   (e.g. exportTpl(), importTpl()).
 */
trait ReflectiveWorkflowTrait {

  /**
   * The extras are an open-ended list of fields that will be passed-through to
   * tpl, tokenContext, etc. This is the storage of last-resort for imported
   * values that cannot be stored by other means.
   *
   * @var array
   *   Ex: ['tplParams' => ['assigned_value' => 'A', 'other_value' => 'B']]
   */
  protected $_extras = [];

  /**
   * @inheritDoc
   * @see \Civi\WorkflowMessage\WorkflowMessageInterface::getFields()
   */
  public function getFields(): array {
    return ReflectionUtils::getFields(static::CLASS, \ReflectionProperty::IS_PROTECTED | \ReflectionProperty::IS_PUBLIC, \Civi\WorkflowMessage\FieldSpec::CLASS);
  }

  protected function getFieldsByFormat($format): ?array {
    switch ($format) {
      case 'modelProps':
        return $this->getFields();

      case 'envelope':
      case 'tplParams':
      case 'tokenContext':
        $matches = [];
        foreach ($this->getFields() as $field) {
          /** @var \Civi\WorkflowMessage\FieldSpec $field */
          if (isset($field->getScope()[$format])) {
            $key = $field->getScope()[$format];
            $matches[$key] = $field;
          }
        }
        return $matches;

      default:
        return NULL;
    }
  }

  /**
   * @inheritDoc
   * @see \Civi\WorkflowMessage\WorkflowMessageInterface::export()
   */
  public function export(string $format = NULL): ?array {
    $format = FieldSpec::formatScopeName($format);
    switch ($format) {
      case 'modelProps':
      case 'envelope':
      case 'tokenContext':
      case 'tplParams':
        $values = $this->_extras[$format] ?? [];
        $fieldsByFormat = $this->getFieldsByFormat($format);
        foreach ($fieldsByFormat as $key => $field) {
          /** @var \Civi\WorkflowMessage\FieldSpec $field */
          $getter = 'get' . ucfirst($field->getName());
          \CRM_Utils_Array::pathSet($values, explode('.', $key), $this->$getter());
        }

        $methods = ReflectionUtils::findMethodPrefix(static::CLASS, 'exportExtra' . ucfirst($format), \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED);
        foreach ($methods as $method) {
          $this->{$method->getName()}(...[&$values]);
        }
        return $values;

      case 'stuffedEnvelope':
        // The "stuffedEnvelope" format is defined to match the traditional format of CRM_Core_BAO_MessageTemplate::sendTemplate().
        // At the top level, it is an "envelope", but it also has keys for other sections.
        $values = $this->export('envelope');
        $values['tplParams'] = $this->export('tplParams');
        $values['tokenContext'] = $this->export('tokenContext');
        if (isset($values['tokenContext']['contactId'])) {
          $values['contactId'] = $values['tokenContext']['contactId'];
        }
        return $values;

      default:
        return NULL;
    }
  }

  /**
   * @inheritDoc
   * @see \Civi\WorkflowMessage\WorkflowMessageInterface::import()
   */
  public function import(string $format, array $values) {
    $MISSING = new \stdClass();
    $format = FieldSpec::formatScopeName($format);

    switch ($format) {
      case 'modelProps':
      case 'envelope':
      case 'tokenContext':
      case 'tplParams':
        $fields = $this->getFieldsByFormat($format);
        foreach ($fields as $key => $field) {
          /** @var \Civi\WorkflowMessage\FieldSpec $field */
          $path = explode('.', $key);
          $value = \CRM_Utils_Array::pathGet($values, $path, $MISSING);
          if ($value !== $MISSING) {
            $setter = 'set' . ucfirst($field->getName());
            $this->$setter($value);
            \CRM_Utils_Array::pathUnset($values, $path, TRUE);
          }
        }

        $methods = ReflectionUtils::findMethodPrefix(static::CLASS, 'importExtra' . ucfirst($format), \ReflectionMethod::IS_PUBLIC | \ReflectionMethod::IS_PROTECTED);
        foreach ($methods as $method) {
          $this->{$method->getName()}($values);
        }

        if ($format !== 'modelProps' && !empty($values)) {
          $this->_extras[$format] = array_merge($this->_extras[$format] ?? [], $values);
          $values = [];
        }
        break;

      case 'stuffedEnvelope':
        // The "stuffedEnvelope" format is defined to match the traditional format of CRM_Core_BAO_MessageTemplate::sendTemplate().
        // At the top level, it is an "envelope", but it also has keys for other sections.
        if (isset($values['model'])) {
          if ($values['model'] !== $this) {
            throw new \CRM_Core_Exception(sprintf("%s: Cannot apply mismatched model", get_class($this)));
          }
          unset($values['model']);
        }
        \CRM_Utils_Array::pathMove($values, ['contactId'], ['tokenContext', 'contactId']);
        if (isset($values['tplParams'])) {
          $this->import('tplParams', $values['tplParams']);
          unset($values['tplParams']);
        }
        if (isset($values['tokenContext'])) {
          $this->import('tokenContext', $values['tokenContext']);
          unset($values['tokenContext']);
        }
        if (isset($values['modelProps'])) {
          $this->import('modelProps', $values['modelProps']);
          unset($values['modelProps']);
        }
        if (isset($values['envelope'])) {
          $this->import('envelope', $values['envelope']);
          unset($values['envelope']);
        }
        $this->import('envelope', $values);
        break;

    }

    return $this;
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

  // All of the methods below are empty placeholders. They may be overridden to customize behavior.

  /**
   * Get a list of key-value pairs to include the array-coded version of the class.
   *
   * @param array $export
   *   Modifiable list of export-values.
   */
  protected function exportExtraModelProps(array &$export): void {
  }

  /**
   * Get a list of key-value pairs to add to the token-context.
   *
   * @param array $export
   *   Modifiable list of export-values.
   */
  protected function exportExtraTokenContext(array &$export): void {
    $export['controller'] = static::CLASS;
    $export['smarty'] = TRUE;
    // Hmm ^^?
  }

  /**
   * Get a list of key-value pairs to include the Smarty template context.
   *
   * Values returned here will override any defaults.
   *
   * @param array $export
   *   Modifiable list of export-values.
   */
  protected function exportExtraTplParams(array &$export): void {
  }

  /**
   * Get a list of key-value pairs to include the Smarty template context.
   *
   * @param array $export
   *   Modifiable list of export-values.
   */
  protected function exportExtraEnvelope(array &$export): void {
    if ($wfName = \CRM_Utils_Constant::value(static::CLASS . '::WORKFLOW')) {
      $export['valueName'] = $wfName;
    }
    if ($wfGroup = \CRM_Utils_Constant::value(static::CLASS . '::GROUP')) {
      $export['groupName'] = $wfGroup;
    }
  }

  /**
   * Given an import-array (in the class-format), pull out any interesting values.
   *
   * @param array $values
   *   List of import-values. Optionally, unset values that you have handled or blocked.
   */
  protected function importExtraModelProps(array &$values): void {
  }

  /**
   * Given an import-array (in the token-context-format), pull out any interesting values.
   *
   * @param array $values
   *   List of import-values. Optionally, unset values that you have handled or blocked.
   */
  protected function importExtraTokenContext(array &$values): void {
  }

  /**
   * Given an import-array (in the tpl-format), pull out any interesting values.
   *
   * @param array $values
   *   List of import-values. Optionally, unset values that you have handled or blocked.
   */
  protected function importExtraTplParams(array &$values): void {
  }

  /**
   * Given an import-array (in the envelope-format), pull out any interesting values.
   *
   * @param array $values
   *   List of import-values. Optionally, unset values that you have handled or blocked.
   */
  protected function importExtraEnvelope(array &$values): void {
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

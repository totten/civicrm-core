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

use Civi\Schema\Traits\ArrayFormatTrait;
use Civi\Schema\Traits\BasicSpecTrait;
use Civi\Schema\Traits\PhpDataTypeSpecTrait;
use Civi\Schema\Traits\OptionsSpecTrait;

class FieldSpec {

  // BasicSpecTrait: name, title, description
  use BasicSpecTrait;

  // PhpDataTypeSpecTrait: type, dataType, serialize, fkEntity
  use PhpDataTypeSpecTrait;

  // OptionsSpecTrait: options, optionsCallback
  use OptionsSpecTrait;

  // ArrayFormatTrait: toArray():array, loadArray($array)
  use ArrayFormatTrait;

  /**
   * @var bool|null
   */
  public $required;

  /**
   * Allow this property to be used in alternative scopes, such as Smarty and TokenProcessor.
   *
   * @var array|null
   *   Ex: ['Smarty' => 'smarty_name']
   */
  public $scope;

  /**
   * @return bool
   */
  public function isRequired(): ?bool {
    return $this->required;
  }

  /**
   * @param bool|null $required
   * @return $this
   */
  public function setRequired(?bool $required) {
    $this->required = $required;
    return $this;
  }

  /**
   * @return array|NULL
   */
  public function getScope(): ?array {
    return $this->scope;
  }

  /**
   * Enable export/import in alternative scopes.
   *
   * @param string|array|NULL $scope
   *   Ex: 'tplParams'
   *   Ex: 'tplParams as foo_bar'
   *   Ex: 'tplParams as contact_id, TokenProcessor as contactId'
   *   Ex: ['tplParams' => 'foo_bar']
   * @return $this
   */
  public function setScope($scope) {
    if (!is_string($scope)) {
      $this->scope = \CRM_Utils_Array::rekey($scope, [__CLASS__, 'formatScopeName']);
    }
    else {
      $parts = explode(',', $scope);
      $this->scope = [];
      foreach ($parts as $part) {
        if (preg_match('/^\s*(\S+) as (\S+)\s*$/', $part, $m)) {
          $this->scope[self::formatScopeName(trim($m[1]))] = trim($m[2]);
        }
        else {
          $this->scope[self::formatScopeName(trim($part))] = $this->getName();
        }
      }
    }
    return $this;
  }

  /**
   * Translate a format-name to canonical format.
   *
   * This should arguably be removed, but for the moment it makes it easier to pivot.
   *
   * @param string $format
   * @return string
   * @internal
   */
  public static function formatScopeName($format) {
    $aliases = ['tpl' => 'tplParams', 'Smarty' => 'tplParams', 'tokenParams' => 'tokenContext', 'TokenProcessor' => 'tokenContext'];
    return $aliases[$format] ?? $format;
  }

}
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

namespace Civi\Schema;

use Civi\Schema\Traits\ArrayFormatTrait;
use Civi\Schema\Traits\BasicSpecTrait;
use Civi\Schema\Traits\PhpDataTypeSpecTrait;
use Civi\Schema\Traits\OptionsSpecTrait;

class MappedFieldSpec {

  // BasicSpecTrait: name, title, description
  use BasicSpecTrait;

  // PhpDataTypeSpecTrait: type, dataType, serialize, fkEntity
  use PhpDataTypeSpecTrait;

  // OptionsSpecTrait: options, optionsCallback
  use OptionsSpecTrait;

  // ArrayFormatTrait: toArray():array, loadArray($array)
  use ArrayFormatTrait;

  /**
   * @var bool
   */
  public $required;

  /**
   * Allow this property to be used in alternative scopes, such as Smarty and TokenProcessor.
   *
   * @var array|null
   *   Ex: [['tplParams', 'foo_bar']]
   */
  public $mapping;

  /**
   * @return bool
   */
  public function isRequired(): bool {
    return $this->required;
  }

  /**
   * @param bool $required
   * @return $this
   */
  public function setRequired(bool $required) {
    $this->required = $required;
    return $this;
  }

  /**
   * @return array|NULL
   */
  public function getMapping(): ?array {
    return $this->mapping;
  }

  /**
   * Enable export/import in alternative scopes.
   *
   * @param string|array|NULL $scope
   *   Ex: 'tplParams.foo_bar'
   *   Ex: [['tplParams', 'foo_bar']]
   *   Ex: 'tplParams.contact_id, tokenContext.contactId'
   *   Ex: [['tplParams', 'foo_bar'], ['tokenContext', 'contactId']]
   * @return $this
   */
  public function setMapping($scope) {
    if (!is_string($scope)) {
      $this->mapping = $scope;
    }
    else {
      $parts = explode(',', $scope);
      $this->mapping = [];
      foreach ($parts as $part) {
        $mappedPath = explode('.', trim($part));
        $this->mapping[] = $mappedPath;
      }
    }
    return $this;
  }

}

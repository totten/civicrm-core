<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */
namespace Civi\API;

/**
 * Container for api results.
 */
class Result extends \ArrayObject {
  /**
   * @var string
   */
  public $entity;
  /**
   * @var string
   */
  public $action;
  /**
   * Original input params
   * @var array
   */
  public $params;
  /**
   * Api version
   * @var int
   */
  public $version = 4;
  /**
   * List of fields for this entity/action
   * @var array
   */
  public $fields;
  /**
   * Any deprecation notices will be supplied here
   * @var array
   */
  public $deprecated = array();

  /**
   * Return first result.
   * @return array|null
   */
  public function first() {
    foreach ($this as $values) {
      return $values;
    }
    return NULL;
  }

  /**
   * Re-index the results array (which by default is non-associative)
   *
   * Drops any item from the results that does not contain the specified key
   *
   * @param string $key
   */
  public function indexBy($key) {
    $newResults = array();
    foreach ($this as $values) {
      if (isset($values[$key])) {
        $newResults[$values[$key]] = $values;
      }
    }
    $this->exchangeArray($newResults);
  }

}

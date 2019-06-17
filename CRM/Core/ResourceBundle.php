<?php
/*
+--------------------------------------------------------------------+
| CiviCRM version 5                                                  |
+--------------------------------------------------------------------+
| Copyright CiviCRM LLC (c) 2004-2019                                |
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

/**
 * A ResourceBundle is a collection of related resources (script files, style
 * files, settings). You may load (or not load) all resources of a bundle.
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 * $Id$
 *
 */
class CRM_Core_ResourceBundle {

  protected $name;

  /**
   * @var array
   *   Array (string $funcName => array $listOfFunctionCalls).
   *   This contains a list of steps for adding resources, indexed
   *   by the name of the function.
   */
  protected $queue = [];

  /**
   * @param string $name
   * @return static
   */
  public static function create($name) {
    $bundle = new static();
    $bundle->setName($name);
    return $bundle;
  }

  /**
   * @return mixed
   */
  public function getName() {
    return $this->name;
  }

  /**
   * @param mixed $name
   */
  public function setName($name) {
    $this->name = $name;
  }

  /**
   * @param CRM_Core_Resources|CRM_Core_ResourceBundle $resources
   * @return $this
   */
  public function applyTo($resources) {
    ksort($this->queue);
    foreach ($this->queue as $func => $paramsLists) {
      foreach ($paramsLists as $paramsList) {
        call_user_func_array([$resources, $func], $paramsList);
      }
    }
    return $this;
  }

  // --------------------------------------------------------------------

  /**
   * @param string|CRM_Core_ResourceBundle $nameOrObject
   * @return $this
   */
  public function addBundle($nameOrObject) {
    $this->queue[__FUNCTION__][] = [$nameOrObject];
    return $this;
  }

  /**
   * @return $this
   * @see CRM_Core_Resources::addPermissions()
   */
  public function addPermissions($permNames) {
    $this->queue[__FUNCTION__][] = [$permNames];
    return $this;
  }

  /**
   * @return $this
   * @see CRM_Core_Resources::addScriptFile()
   */
  public function addScriptFile($ext, $file, $weight = CRM_Core_Resources::DEFAULT_WEIGHT, $region = CRM_Core_Resources::DEFAULT_REGION, $translate = TRUE) {
    $this->queue[__FUNCTION__][] = [$ext, $file, $weight, $region, $translate];
    return $this;
  }

  /**
   * @return $this
   * @see CRM_Core_Resources::addScriptUrl()
   */
  public function addScriptUrl($url, $weight = CRM_Core_Resources::DEFAULT_WEIGHT, $region = CRM_Core_Resources::DEFAULT_REGION) {
    $this->queue[__FUNCTION__][] = [$url, $weight, $region];
    return $this;
  }

  /**
   * @return $this
   * @see CRM_Core_Resources::addScript()
   */
  public function addScript($code, $weight = CRM_Core_Resources::DEFAULT_WEIGHT, $region = CRM_Core_Resources::DEFAULT_REGION) {
    $this->queue[__FUNCTION__][] = [$code, $weight];
    return $this;
  }

  /**
   * @return $this
   * @see CRM_Core_Resources::addVars()
   */
  public function addVars($nameSpace, $vars) {
    $this->queue[__FUNCTION__][] = [$nameSpace, $vars];
    return $this;
  }

  /**
   * @return $this
   * @see CRM_Core_Resources::addString()
   */
  public function addString($text, $domain = 'civicrm') {
    $this->queue[__FUNCTION__][] = [$text, $domain];
    return $this;
  }

  /**
   * @return $this
   * @see CRM_Core_Resources::addStyleFile()
   */
  public function addStyleFile($ext, $file, $weight = CRM_Core_Resources::DEFAULT_WEIGHT, $region = CRM_Core_Resources::DEFAULT_REGION) {
    $this->queue[__FUNCTION__][] = [$ext, $file, $weight, $region];
    return $this;
  }

  /**
   * @return $this
   * @see CRM_Core_Resources::addStyleUrl()
   */
  public function addStyleUrl($url, $weight = CRM_Core_Resources::DEFAULT_WEIGHT, $region = CRM_Core_Resources::DEFAULT_REGION) {
    $this->queue[__FUNCTION__][] = [$url, $weight, $region];
    return $this;
  }

  /**
   * @return $this
   * @see CRM_Core_Resources::addStyle()
   */
  public function addStyle($code, $weight = CRM_Core_Resources::DEFAULT_WEIGHT, $region = CRM_Core_Resources::DEFAULT_REGION) {
    $this->queue[__FUNCTION__][] = [$code, $weight, $region];
    return $this;
  }

}

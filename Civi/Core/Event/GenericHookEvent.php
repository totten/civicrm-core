<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
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

namespace Civi\Core\Event;

/**
 * Class GenericHookEvent
 * @package Civi\API\Event
 *
 * The GenericHookEvent is used to expose all traditional hooks to the
 * Symfony EventDispatcher.
 *
 * The traditional notation for a hook is based on a function signature:
 *
 *   function hook_civicrm_foo($bar, &$whiz, &$bang);
 *
 * Symfony Events are based on a class with properties and methods. This
 * requires some kind of mapping.
 *
 * Implementing new event classes for every hook would produce a large
 * amount of boilerplate. Symfony Events have an interesting solution to
 * that problem: use `GenericEvent` instead of custom event classes.
 * This class (`GenericHookEvent`) is conceptually similar to `GenericEvent`,
 * but it adds support for (a) altering fields and (b) mapping to hook
 * notation.
 */
class GenericHookEvent extends \Symfony\Component\EventDispatcher\Event {

  /**
   * @var array
   *   Ex: array('contactID' => &$contactID, 'contentPlacement' => &$contentPlacement).
   */
  private $hookParams;

  /**
   * @var array
   *   Ex: array(0 => 'contactID', 1 => 'contentPlacement').
   */
  private $hookParamOrder;

  /**
   * Some legacy hooks expect listener-functions to return a value.
   * OOP listeners may set the $returnValue.
   *
   * @var mixed
   * @deprecated
   */
  private $returnValue = array();

  /**
   * GenericHookEvent constructor.
   *
   * @param array $hookParams
   *   Ex: array('contactID' => &$contactID, 'contentPlacement' => &$contentPlacement).
   * @param array|NULL $hookParamOrder
   *   Ex: array(0 => 'contactID', 1 => 'contentPlacement').
   *   If NULL, autodetect order from $hookParams.
   */
  public function __construct($hookParams, $hookParamOrder = NULL) {
    $this->hookParams = $hookParams;
    $this->hookParamOrder = $hookParamOrder;
  }

  /**
   * @return array
   */
  public function getHookParams() {
    return $this->hookParams;
  }

  /**
   * @param array $hookParams
   * @return GenericHookEvent
   */
  public function setHookParams($hookParams) {
    $this->hookParams = $hookParams;
    return $this;
  }

  /**
   * @return array
   */
  public function getHookParamOrder() {
    return $this->hookParamOrder === NULL
      ? array_keys($this->getHookParams())
      : $this->hookParamOrder;
  }

  /**
   * @param array $hookParamOrder
   * @return GenericHookEvent
   */
  public function setHookParamOrder($hookParamOrder) {
    $this->hookParamOrder = $hookParamOrder;
    return $this;
  }

  /**
   * @return mixed
   * @deprecated
   */
  public function getReturnValue() {
    return empty($this->returnValue) ? TRUE : $this->returnValue;
  }

  /**
   * @param mixed $fResult
   * @return GenericHookEvent
   * @deprecated
   */
  public function addReturnValue($fResult) {
    if (!empty($fResult) && is_array($fResult)) {
      $this->returnValue = array_merge($this->returnValue, $fResult);
    }

    return $this;
  }

  /**
   * @inheritDoc
   */
  public function &__get($name) {
    return $this->hookParams[$name];
  }

  /**
   * @inheritDoc
   */
  public function __set($name, $value) {
    $this->hookParams[$name] = $value;
  }

  /**
   * @inheritDoc
   */
  public function __unset($name) {
    unset($this->hookParams[$name]);
  }

}

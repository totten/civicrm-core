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
namespace Civi\API\V4;
use Civi\API\Event\AuthorizeEvent;
use Civi\API\Result;

/**
 * Base class for all api actions.
 *
 * @method $this addChain(Action $apiCall)
 */
abstract class Action {


  /**
   * Api version number; cannot be changed.
   *
   * @var int
   */
  protected $version = 4;

  /**
   * A list of api actions to execute on the results.
   *
   * @var array
   */
  protected $chain = array();

  /**
   * Whether to enforce acl permissions based on the current user.
   *
   * In PHP, this defaults to false.
   * In REST/javascript this defaults to true and cannot be disabled.
   *
   * @var bool|string|int
   */
  protected $checkPermissions = FALSE;

  /* @var string */
  private $entity;

  /* @var \ReflectionClass */
  private $thisReflection;

  /* @var array */
  private $thisParamInfo;

  /* @var \Symfony\Component\EventDispatcher\EventDispatcher */
  private $thisDispatcher;

  /**
   * Action constructor.
   * @param string $entity
   */
  public function __construct($entity) {
    $this->entity = $entity;
    $this->thisReflection = new \ReflectionClass($this);
  }

  /**
   * Strictly enforce api parameters
   * @param $name
   * @param $value
   * @throws \Exception
   */
  public function __set($name, $value) {
    throw new \API_Exception('Unknown api parameter');
  }

  /**
   * @throws \API_Exception
   */
  public function setVersion() {
    throw new \API_Exception('Cannot modify api version');
  }

  /**
   * Magic function to provide addFoo, getFoo and setFoo for public properties.
   *
   * @param $name
   * @param $arguments
   * @return $this|mixed
   * @throws \API_Exception
   */
  public function __call($name, $arguments) {
    $param = lcfirst(substr($name, 3));
    if ($this->paramExists($param)) {
      switch (substr($name, 0, 3)) {
        case 'get':
          return $this->$param;

        case 'set':
          if (is_array($this->$param)) {
            // Don't overwrite any defaults
            $this->$param = $arguments[0] + $this->$param;
          }
          else {
            $this->$param = $arguments[0];
          }
          return $this;

        case 'add':
          if (!is_array($this->$param)) {
            throw new \API_Exception('Cannot add to non-array param');
          }
          if (array_key_exists(1, $arguments)) {
            $this->{$param}[$arguments[0]] = $arguments[1];
          }
          else {
            $this->{$param}[] = $arguments[0];
          }
          return $this;
      }
    }
    throw new \API_Exception('Unknown api parameter');
  }

  /**
   * Invoke api call.
   *
   * At this point all the params have been sent in and we initiate the api call & return the result.
   * This is basically the outer wrapper for api v4.
   *
   * @return Result
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  final public function execute() {
    $entity = $this->getEntity();
    $action = $this->getAction();
    $params = $this->getParams();

    /** @var AuthorizeEvent $event */
//    $event = $this->thisDispatcher->dispatch(Events::AUTHORIZE, new AuthorizeEvent($apiProvider, $apiRequest, $this));
//    if (!$event->isAuthorized()) {
//      throw new \Civi\API\Exception\UnauthorizedException("Authorization failed");
//    }

    $result = new Result();
    $result->entity = $entity;
    $result->action = $action;
    $this->run($result);

    // TODO: hand off some post processing tasks to api kernel like executing chains?
    return $result;
  }

  /**
   * @param \Civi\API\Result $result
   * @return \Civi\API\Result
   */
  abstract protected function run(Result &$result);

  /**
   * Serialize this object's params into an array
   * @return array
   */
  public function getParams() {
    $params = array();
    foreach ($this->thisReflection->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
      $name = $property->getName();
      $params[$name] = $this->$name;
    }
    return $params;
  }

  /**
   * Get documentation for one or all params
   *
   * @param string $param
   * @return array
   */
  public function getParamInfo($param = NULL) {
    if (!isset($this->thisParamInfo)) {
      $defaults = $this->getParamDefaults();
      foreach ($this->thisReflection->getProperties(\ReflectionProperty::IS_PROTECTED) as $property) {
        $name = $property->getName();
        $this->thisParamInfo[$name] = Utils::parseDocBlock($property->getDocComment());
        $this->thisParamInfo[$name]['default'] = $defaults[$name];
      }
    }
    return $param ? $this->thisParamInfo[$param] : $this->thisParamInfo;
  }

  /**
   * @return string
   */
  public function getEntity() {
    return $this->entity;
  }

  /**
   *
   * @return string
   */
  public function getAction() {
    $name = get_class($this);
    return lcfirst(substr($name, strrpos($name, '\\') + 1));
  }

  /**
   * @param string $param
   * @return bool
   */
  protected function paramExists($param) {
    return array_key_exists($param, $this->getParams());
  }

  /**
   * @return array
   */
  protected function getParamDefaults() {
    return array_intersect_key($this->thisReflection->getDefaultProperties(), $this->getParams());
  }

  /**
   * @return null|string
   */
  protected function getBaoName() {
    require_once 'api/v3/utils.php';
    return \_civicrm_api3_get_BAO($this->getEntity());
  }

}

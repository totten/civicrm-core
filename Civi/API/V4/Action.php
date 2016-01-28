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
use Civi;

/**
 * Base class for all api actions.
 */
abstract class Action {

  protected $entity;
  protected $action;
  public $chain = array();

  /* @var bool */
  public $check_permissions = FALSE;

  public function __construct($entity) {
    $this->entity = $this->stripNamespace($entity);
    $this->action = lcfirst($this->stripNamespace(get_class($this)));
  }

  /**
   * Strictly enforce api parameters
   * @param $name
   * @param $value
   * @throws \Exception
   */
  public function __set($name, $value) {
    throw new \Exception('Unknown api parameter');
  }

  /**
   * Invoke api call.
   *
   * At this point all the params have been sent in and we initiate the api call & return the result.
   * This is basically the outer wrapper for api v4.
   *
   * @return \Civi\API\Result
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  final public function execute() {
    // Check api permissions.
    if (!Civi::service('civi_api_kernel')->runAuthorize($this->entity, $this->action, $this->getParams())) {
      throw new Civi\API\Exception\UnauthorizedException("Authorization failed");
    }
    // TODO: hand off some pre-flight tasks api kernel like getting fields?

    return $this->run();

    // TODO: hand off some post processing tasks to api kernel like executing chains?
  }

  /**
   * @return \Civi\API\Result
   */
  abstract protected function run();

  /**
   * Serialize this object into an array of parameters
   * @return array
   */
  protected function getParams() {
    return array('version' => 4) + json_decode(json_encode($this), true);
  }

  /**
   * Remove namespace prefix from className
   * @param $name
   * @return string
   */
  protected function stripNamespace($name) {
    $pos = strrpos($name, '\\');
    return ($pos === FALSE) ? $name : substr($name, $pos + 1);
  }

}

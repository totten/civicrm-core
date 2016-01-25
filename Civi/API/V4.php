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
use Civi\API\Result;

/**
 * Base class for all api entities.
 *
 * Annotations like this make entities easy to discover in your IDE
 * @method Result Participant
 * But maintaining this list here would be a pain. Alternatives??
 * One crazy idea would be to have this list of annotations be all there is for most entities.
 * In other words, most api files are nothing but boilerplate, so why have them at all?
 * Our api wrapper could read these annotations and automatically call the generic method.
 */
class V4 {

  /**
   * @param string $entity
   * @param array $apiRequest
   * @return Result
   * @throws \Exception
   */
  public function __call($entity, $apiRequest) {
    $className = "\\Civi\\API\\V4\\Entity\\$entity";
    if (class_exists($className)) {
      // Instead of passing $entity $action and $params around internally like v3
      // We start by constructing an empty Results object, loading it up with metadata
      // And pass that to the appropriate api function to get populated.
      $result = new Result();
      $result->entity = $entity;
      $result->action = $action = (string) $apiRequest[0];
      $result->params = isset($apiRequest[1]) ? $apiRequest[1] : array();
      $result->fields = $this->getFields($entity, $action);

      $entityClass = new $className();
      if (is_callable(array($entityClass, $action))) {
        // TODO Check permissions
        return $entityClass->$action($result);
      }
    }
    throw new \Exception('Unknown api');
  }

  /**
   * @param string $entity
   * @param string $action
   * @return array
   */
  private function getFields($entity, $action) {
    //todo
    return array();
  }

}

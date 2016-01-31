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
namespace Civi\API\V4\Action;
use Civi\API\Exception\NotImplementedException;
use Civi\API\Result;
use Civi\API\V4\Action;

/**
 * Get actions for an entity with a list of accepted params
 */
class GetActions extends Action {

  protected function run(Result &$result) {
    $entity = $this->getEntity();
    $includePaths = explode(PATH_SEPARATOR, get_include_path());
    // First search entity-specific actions (including those provided by extensions
    foreach ($includePaths as $path) {
      $dir = \CRM_Utils_File::addTrailingSlash($path) . 'Civi/API/V4/Entity/' . $entity;
      $this->scanDir($dir, $entity, $result);
    }
    // Scan all generic actions
    foreach ($includePaths as $path) {
      $dir = \CRM_Utils_File::addTrailingSlash($path) . 'Civi/API/V4/Action';
      $this->scanDir($dir, $entity, $result);
    }
  }

  private function scanDir($dir, $entity, &$result) {
    if (file_exists($dir)) {
      foreach (glob("$dir/*.php") as $file) {
        $matches = array();
        preg_match('/(\w*).php/', $file, $matches);
        $actionName = array_pop($matches);
        try {
          if (!isset($result[$actionName])) {
            $result[$actionName] = call_user_func(array('\\Civi\\Api4\\' . $entity, $actionName))->getParamInfo();
          }
        }
        catch (NotImplementedException $e) {}
      }
    }
  }

}

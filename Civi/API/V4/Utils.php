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

/**
 * Just another place to put static functions...
 */
class Utils {

  /**
   * @param string $comment
   * @return array
   */
  public static function parseDocBlock($comment) {
    $info = array();
    foreach (preg_split("/((\r?\n)|(\r\n?))/", $comment) as $num => $line) {
      if (!$num || strpos($line, '*/') !== FALSE) {
        continue;
      }
      $line = ltrim(trim($line), '* ');
      if (!$line) {
        continue;
      }
      if ($num == 1) {
        $info['description'] = $line;
      }
      elseif (strpos($line, '@') !== 0) {
        $info['comment'] = isset($info['comment']) ? "{$info['comment']}\n$line" : $line;
      }
      else {
        $words = explode(' ', $line);
        $key = substr($words[0], 1);
        if ($key == 'var') {
          $info['type'] = explode('|', $words[1]);
        }
        else {
          // Don't know what this is but we'll duly add it to the info array
          $val = implode(' ', array_slice($words, 1));
          $info[$key] = strlen($val) ? $val : TRUE;
        }
      }
    }
    return $info;
  }
}

<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 */
class CRM_Core_Page_AJAX_KCFinder {

  public static function browse() {
    self::bootKcfinder();
    $browser = new \kcfinder\browser();
    $browser->action();
  }

  public static function upload() {
    self::bootKcfinder();
    $uploader = new \kcfinder\uploader();
    $uploader->upload();
  }

  private static function bootKcfinder() {
    $kcfinder = Civi::paths()->getPath('[civicrm.packages]/kcfinder');

    $ip = get_include_path();
    set_include_path($kcfinder . PATH_SEPARATOR . $ip);

    // upstream's autoloader has incorrect "file_exists()" test

    spl_autoload_register(function ($path) use ($kcfinder) {
      $path = explode("\\", $path);

      if (count($path) == 1) {
        return;
      }

      list($ns, $class) = $path;

      if ($ns == "kcfinder") {
        if ($class == "uploader") {
          require "core/class/uploader.php";
        }
        elseif ($class == "browser") {
          require "core/class/browser.php";
        }
        elseif ($class == "minifier") {
          require "core/class/minifier.php";
        }
        elseif (file_exists("$kcfinder/core/types/$class.php")) {
          require "core/types/$class.php";
        }
        elseif (file_exists("$kcfinder/lib/class_$class.php")) {
          require "lib/class_$class.php";
        }
        elseif (file_exists("$kcfinder/lib/helper_$class.php")) {
          require "lib/helper_$class.php";
        }
      }
    });

    require "core/bootstrap.php";
  }

}

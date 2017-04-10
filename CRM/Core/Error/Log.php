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

/**
 * Class CRM_Core_Error_Log
 *
 * A PSR-3 wrapper for CRM_Core_Error.
 */
class CRM_Core_Error_Log extends \Psr\Log\AbstractLogger {

  /**
   * Get a mapping between PSR log-levels and PEAR log-levels.
   *
   * @return array
   *   Array(scalar $psrLevel => scalar $pearLevel).
   *   Ex: $result['info'] = 6.
   */
  public static function getLevelMap() {
    static $levelMap = NULL;
    if ($levelMap === NULL) {
      $levelMap = array(
        \Psr\Log\LogLevel::DEBUG => PEAR_LOG_DEBUG,
        \Psr\Log\LogLevel::INFO => PEAR_LOG_INFO,
        \Psr\Log\LogLevel::NOTICE => PEAR_LOG_NOTICE,
        \Psr\Log\LogLevel::WARNING => PEAR_LOG_WARNING,
        \Psr\Log\LogLevel::ERROR => PEAR_LOG_ERR,
        \Psr\Log\LogLevel::CRITICAL => PEAR_LOG_CRIT,
        \Psr\Log\LogLevel::ALERT => PEAR_LOG_ALERT,
        \Psr\Log\LogLevel::EMERGENCY => PEAR_LOG_EMERG,
      );
    }
    return $levelMap;
  }

  /**
   * Logs with an arbitrary level.
   *
   * @param mixed $level
   * @param string $message
   * @param array $context
   */
  public function log($level, $message, array $context = array()) {
    // FIXME: This flattens a $context a bit prematurely. When integrating
    // with external/CMS logs, we should pass through $context.
    if (!empty($context)) {
      if (isset($context['exception'])) {
        $context['exception'] = CRM_Core_Error::formatTextException($context['exception']);
      }
      $message .= "\n" . print_r($context, 1);
    }

    $config = CRM_Core_Config::singleton();

    $map = self::getLevelMap();
    $file_log = CRM_Core_Error::createDebugLogger(CRM_Utils_Array::value('civi.comp', $context, ''));
    $file_log->log("$message\n", $map[$level]);

    $str = '<p/><code>' . htmlspecialchars($message) . '</code>';
    if (CRM_Utils_Array::value('civi.out', $context, FALSE) && CRM_Core_Permission::check('view debug output')) {
      echo $str;
    }
    $file_log->close();

    if (!isset(\Civi::$statics[__CLASS__]['userFrameworkLogging'])) {
      // Set it to FALSE first & then try to set it. This is to prevent a loop as calling
      // $config->userFrameworkLogging can trigger DB queries & under log mode this
      // then gets called again.
      \Civi::$statics[__CLASS__]['userFrameworkLogging'] = FALSE;
      \Civi::$statics[__CLASS__]['userFrameworkLogging'] = $config->userFrameworkLogging;
    }

    if (!empty(\Civi::$statics[__CLASS__]['userFrameworkLogging'])) {
      // should call $config->userSystem->logger($message) here - but I got a situation where userSystem was not an object - not sure why
      if ($config->userSystem->is_drupal and function_exists('watchdog')) {
        watchdog('civicrm', '%message', array('%message' => $message), WATCHDOG_DEBUG);
      }
    }
  }

}

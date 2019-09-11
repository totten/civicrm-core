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
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2019
 */
class CRM_Utils_Redactor {

  /**
   * @param string $str
   *   A printable message.
   * @param string[] $stringRules
   *   A list of case-insensitive string replacements.
   *   Ex: ['Voldemort' => 'He Who Shall Not Be Named']
   *
   * @return string
   *   Redacted message.
   */
  public function redact($str, $stringRules) {
    // redact the strings
    if (!empty($stringRules)) {
      foreach ($stringRules as $match => $replace) {
        $str = str_ireplace($match, $replace, $str);
      }
    }

    return $str;
  }

  /**
   * Determine the string replacements for redaction.
   * on the basis of the regular expressions
   *
   * @param string $str
   *   Input string.
   * @param array $regexRules
   *   Regular expression to be matched w/ replacements.
   *   Ex: ['/\n\n\n-\n\n-\n\n\n\n/' => '[SSN]']
   *   Ex: ['/\n\n\n[ \-\.]\n\n\n[ \-\.]\n\n\n/' => '[PHONE]']
   *
   * @return array
   *   array of strings w/ corresponding redacted outputs
   *   Ex: ['987-65-4321' => '[SSN]nW9z1']
   */
  public function regex($str, $regexRules) {
    // redact the regular expressions
    if (!empty($regexRules) && isset($str)) {
      static $matches, $totalMatches, $match = [];
      foreach ($regexRules as $pattern => $replacement) {
        preg_match_all($pattern, $str, $matches);
        if (!empty($matches[0])) {
          if (empty($totalMatches)) {
            $totalMatches = $matches[0];
          }
          else {
            $totalMatches = array_merge($totalMatches, $matches[0]);
          }
          $match = array_flip($totalMatches);
        }
      }
    }

    if (!empty($match)) {
      foreach ($match as $matchKey => & $dontCare) {
        foreach ($regexRules as $pattern => $replacement) {
          if (preg_match($pattern, $matchKey)) {
            $dontCare = $replacement . substr(md5($matchKey), 0, 5);
            break;
          }
        }
      }
      return $match;
    }
    return [];
  }

  /**
   * This function will mask part of the the user portion of an email address (everything before the @)
   *
   * @param string $email
   *   The email address to be masked.
   * @param string $maskChar
   *   The character used for masking.
   * @param int $percent
   *   The percentage of the user portion to be masked.
   *
   * @return string
   *   Returns the masked email address
   */
  public function maskEmail($email, $maskChar = '*', $percent = 50) {
    list($user, $domain) = preg_split("/@/", $email);
    $len = strlen($user);
    $maskCount = floor($len * $percent / 100);
    $offset = floor(($len - $maskCount) / 2);

    $masked = substr($user, 0, $offset)
      . str_repeat($maskChar, $maskCount)
      . substr($user, $maskCount + $offset);

    return ($masked . '@' . $domain);
  }

}

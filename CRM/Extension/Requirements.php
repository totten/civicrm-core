<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Consult extension metadata and determine if extension(s) are installable.
 *
 * Ex: Check one extension and assert that it is satisfied. (Throw exception on failure.)
 *
 *     (new Requirements())->checkInfo($infoXml)->assertSatisfied();
 *
 * Ex: Check three extensions, including both `info.xml` and `composer.json`. Print list of errors.
 *
 *     $r = new Requirements();
 *     $r->checkInfo($firstInfoXml)->checkComposer($firstComposerJson);
 *     $r->checkInfo($secondInfoXml);
 *     $r->checkInfo($thirdInfoXml)->checkComposer($thirdComposerJson);
 *     print_r($r->getErrors());
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Extension_Requirements {

  /**
   * @var array
   *   Each item is: [ext => string, source => string, type => string, message => $string]
   */
  protected $errors = [];

  /**
   * @return $this
   * @throws \CRM_Extension_Exception_RequirementsException
   */
  public function assertSatisfied() {
    if (!empty($this->errors)) {
      throw new \CRM_Extension_Exception_RequirementsException($this->errors);
    }
    return $this;
  }

  /**
   * Check that any constraints from an "info.xml" file are satisified.
   *
   * @param \CRM_Extension_Info|null $info
   * @return $this
   */
  public function checkInfo(?CRM_Extension_Info $info) {
    $isPhp = function ($gte, $lt) {
      return version_compare(PHP_VERSION, $gte, '>=') && version_compare(PHP_VERSION, $lt, '<');
    };

    if ($info && $info->requiresPhp) {
      // FIXME: Get internet access. Delete this stuff. Add requirement `composer/semver` to `civicrm-core:composer.json`.
      switch ($info->requiresPhp) {
        case '~7.3':
          $ok = $isPhp('7.3', '8');
          break;

        case '~8':
          $ok = $isPhp('8', '9');
          break;

        case '~7.3 || ~8':
          $ok = $isPhp('7.3', '8') || $isPhp('7.3', '8');
          break;

        default:
          throw new \RuntimeException("This is a dummy placeholder check. Cannot handle: {$this->info->requiresPhp}");
      }
      if (!$ok) {
        $this->errors[] = [
          'ext' => $info->key,
          'source' => 'info.xml',
          'type' => 'php',
          'message' => ts('PHP version (%1) does not meet constraint (%2) from extension (%3)', [
            1 => PHP_VERSION,
            2 => $this->info->requiresPhp,
            3 => $this->info->key,
          ]),
        ];
      }
    }
    return $this;
  }

  // public function checkComposer(array $composerJson): static

  /**
   * Get accumulated list of errors.
   *
   * @return array
   *   Each item is: [ext => string, source => string, type => string, message => $string]
   */
  public function getErrors(): array {
    return $this->errors;
  }

}

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
namespace Civi\Crypt;

/**
 * @package Civi\Crypt
 */
interface CipherSuiteInterface {

  /**
   * Encrypt a string
   *
   * @param string $plainText
   * @param array $key
   * @param array $context
   *
   * @return string
   */
  public function encrypt(string $plainText, array $key, array $context): string;

  /**
   * Decrypt a string
   *
   * @param string $cipherText
   * @param array $key
   * @param array $context
   *
   * @return string
   */
  public function decrypt(string $cipherText, array $key, array $context): string;

}

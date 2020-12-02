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

namespace Civi\Crypto;

use Civi\Crypto\Exception\CryptoException;

/**
 * The CryptoService tracks a list of available keys and cipher implementations.
 * It provides an encrypt() and decrypt() method which can use any of the listed keys.
 *
 * Note: The design is primarily intended for *storage* applications, not for
 * *transmission* or *messaging* applications.
 *
 * Encrypted data includes a header
 *
 * aes-cbc: AES (256-bit key, 128-bit block) + CBC. Prepend random IV (128-bit).
 * aes-ctr: AES (256-bit key, 128-bit block) + CTR. Prepend random IV (128-bit).
 * aes-cbc-hs: AES (256-bit key, 128-bit block) + CBS. Prepend HMAC-SHA256(first 128-bits) and random IV (128-bit). Keys derived via digest.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CryptoService {

  const LAST_WEIGHT = 32768;

  const DEFAULT_SUITE = 'aes-cbc';

  const DEFAULT_KDF = 'hkdf-sha256';

  protected $delim;

  /**
   * List of available keys.
   *
   * @var array[]
   */
  protected $keys = [];

  /**
   * List of key-derivation functions. Used when loading keys.
   *
   * @var array
   */
  protected $kdfs = [];

  protected $cipherSuites = [];

  public function __construct() {
    $this->delim = chr(0);

    $this->cipherSuites['plain'] = TRUE;
    $this->keys['plain'] = [
      'key' => '',
      'suite' => 'plain',
      'tags' => [],
      'id' => 'plain',
      'weight' => self::LAST_WEIGHT,
    ];

    // Base64 - Useful for precise control. Relatively quick decode. Please bring your own entropy.
    $this->kdfs['b64'] = 'base64_decode';

    // HKDF - Forgiving about diverse inputs. Relatively quick decode. Please bring your own entropy.
    $this->kdfs['hkdf-sha256'] = function($v) {
      // NOTE: 256-bit output by default. Useful for pairing with AES-256.
      return hash_hkdf('sha256', $v);
    };

    // Possible future options: Read from PEM file. Run PBKDF2 on a passphrase.
  }

  /**
   * @param string|array $options
   *   Additional options:
   *     - key: string, a representation of the key as binary
   *     - suite: string, ex: 'aes-cbc'
   *     - tags: string[]
   *     - weight: int, default 0
   *     - id: string, a unique identifier for this key. (default: fingerprint the key+suite)
   *
   * @return array
   *   The key record with properties:
   *     - key: string, binary
   *     - suite: string, ex: 'aes-cbc'
   *     - id: string, unique identifier
   *     - tags: string[]
   * @throws \Civi\Crypto\Exception\CryptoException
   */
  public function addSymmetricKey($options) {
    $defaults = [
      'suite' => self::DEFAULT_SUITE,
      'weight' => 0,
    ];
    $options = array_merge($defaults, $options);

    if (!isset($options['key'])) {
      throw new CryptoException("Missing crypto key");
    }

    if (!isset($options['id'])) {
      $options['id'] = base64_encode(sha1($options['suite'] . $this->delim . $options['key']));
    }

    $this->keys[$options['id']] = $options;
    return $options;
  }

  /**
   * Enable plain-text encoding.
   *
   * @param array $options
   *   Array with options:
   *   - tags: string[]
   * @return array
   */
  public function addPlainText($options) {
    if (!isset($this->keys['plain'])) {
    }
    if (isset($options['tags'])) {
      $this->keys['plain']['tags'] = array_merge(
        $options['tags']
      );
    }
    return $this->keys['plain'];
  }

  /**
   * @param CipherSuiteInterface $cipherSuite
   *   The encryption/decryption callback/handler
   * @param string[]|NULL $names
   *   Symbolic names. Ex: 'aes-cbc'
   *   If NULL, probe $cipherSuite->getNames()
   */
  public function addCipherSuite(CipherSuiteInterface $cipherSuite, $names = NULL) {
    $names = $names ?: $cipherSuite->getSuites();
    foreach ($names as $name) {
      $this->cipherSuites[$name] = $cipherSuite;
    }
  }

  public function getKeys() {
    return $this->keys;
  }

  /**
   * Locate a key in the list of available keys.
   *
   * @param string|string[] $keyIds
   *   List of IDs or tags. The first match in the list is returned.
   *   If multiple keys match the same tag, then the one with lowest 'weight' is returned.
   * @return array
   * @throws \Civi\Crypto\Exception\CryptoException
   */
  public function findKey($keyIds) {
    $keyIds = (array) $keyIds;
    foreach ($keyIds as $keyIdOrTag) {
      if (isset($this->keys[$keyIdOrTag])) {
        return $this->keys[$keyIdOrTag];
      }

      $matchKeyId = NULL;
      $matchWeight = self::LAST_WEIGHT;
      foreach ($this->keys as $key) {
        if (in_array($keyIdOrTag, $key['tags']) && $key['weight'] <= $matchWeight) {
          $matchKeyId = $key['id'];
          $matchWeight = $key['weight'];
        }
      }
      if ($matchKeyId !== NULL) {
        return $this->keys[$matchKeyId];
      }
    }

    throw new CryptoException("Failed to find key by ID or tag (" . implode(' ', $keyIds) . ")");
  }

  /**
   * Create an encrypted token (given the plaintext).
   *
   * @param string $plainText
   *   The secret value to encode (e.g. plain-text password).
   * @param string|string[] $keyIdOrTag
   *   List of key IDs or key tags to check. First available match wins.
   * @return string
   *   A token
   * @throws \Civi\Crypto\Exception\CryptoException
   */
  public function encrypt($plainText, $keyIdOrTag) {
    $key = $this->findKey($keyIdOrTag);
    if ($key['suite'] === 'plain') {
      if ($plainText[0] === $this->delim) {
        throw new CryptoException("Cannot use plaintext encoding for data with reserved delimiter (0).");
      }
      return $plainText;
    }

    /** @var \Civi\Crypto\CipherSuiteInterface $cipherSuite */
    if (!isset($this->cipherSuites[$key['suite']])) {
      throw new CryptoException('Cannot encrypt token. Unknown cipher suite ' . $key['suite']);
    }
    $cipherSuite = $this->cipherSuites[$key['suite']];
    $cipherText = $cipherSuite->encrypt($plainText, $key);
    return $this->delim . $key['id'] . $this->delim . base64_encode($cipherText) . $this->delim;
  }

  /**
   * Get the plaintext (given an encrypted token).
   *
   * @param string $token
   * @param string|string[] $keyIdOrTag
   *   Whitelist of acceptable keys. Wildcard '*' will allow decryption via
   *   any available key.
   * @return string
   * @throws \Civi\Crypto\Exception\CryptoException
   */
  public function decrypt($token, $keyIdOrTag = '*') {
    $keyIdOrTag = (array) $keyIdOrTag;

    if ($token[0] !== $this->delim) {
      if (in_array('*', $keyIdOrTag) || in_array('plain', $keyIdOrTag)) {
        return $token;
      }
      else {
        throw new CryptoException("Cannot decrypt token. Unexpected key: plain");
      }
    }

    $parts = explode($this->delim, $token);
    $keyId = $parts[1];
    $cipherText = base64_decode($parts[2]);

    if (!isset($this->keys[$keyId])) {
      throw new CryptoException("Cannot decrypt token. Unknown key: " . $keyId);
    }
    $key = $this->keys[$keyId];
    if (!in_array('*', $keyIdOrTag) && !in_array($keyId, $keyIdOrTag) && empty(array_intersect($keyIdOrTag, $key['tags']))) {
      throw new CryptoException("Cannot decrypt token. Unexpected key: $keyId");
    }

    /** @var \Civi\Crypto\CipherSuiteInterface $cipherSuite */
    if (!isset($this->cipherSuites[$key['suite']])) {
      throw new CryptoException('Cannot decrypt token. Unknown cipher suite ' . $key['suite'] ?? '(empty)');
    }
    $cipherSuite = $this->cipherSuites[$key['suite']];

    $plainText = $cipherSuite->decrypt($cipherText, $key);
    return $plainText;
  }

  /**
   * @param string $keyExpr
   *   String in the form "<suite>:<key-encoding>:<key-value>".
   *
   *   'aes-cbc:b64:cGxlYXNlIHVzZSAzMiBieXRlcyBmb3IgYWVzLTI1NiE='
   *   'aes-cbc:hkdf-sha256:ABCD1234ABCD1234ABCD1234ABCD1234'
   *   '::ABCD1234ABCD1234ABCD1234ABCD1234'
   *
   * @return array
   *   Properties:
   *    - key: string, binary representation
   *    - suite: string, ex: 'aes-cbc'
   * @throws CryptoException
   */
  public function parseKey($keyExpr) {
    list($suite, $keyFunc, $keyVal) = explode(':', $keyExpr);
    if ($suite === '') {
      $suite = self::DEFAULT_SUITE;
    }
    if ($keyFunc === '') {
      $keyFunc = self::DEFAULT_KDF;
    }
    if (isset($this->kdfs[$keyFunc])) {
      return [
        'suite' => $suite,
        'key' => call_user_func($this->kdfs[$keyFunc], $keyVal),
      ];
    }
    else {
      throw new CryptoException("Crypto key has unrecognized type");
    }
  }

}

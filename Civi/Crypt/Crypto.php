<?php

namespace Civi\Crypt;

class Crypto {

  protected $delim;

  protected $keys = [];

  protected $cipherSuites = [];

  public function __construct() {
    $this->delim = chr(0);
  }

  /**
   * @param string $key
   *   Binary string
   * @param string $cipherSuite
   *   Ex: aes256-cbc
   * @param array $options
   *   Additional options:
   *     - aliases: string[]
   *
   * @return static
   */
  public function addSymmetricKey($key, $cipherSuite, $options = []) {
    $fingerprint = base64_encode(sha1($cipherSuite . $this->delim . $key));
    $options['key'] = $key;
    $options['suite'] = $cipherSuite;
    $options['fingerprint'] = $fingerprint;
    $this->keys[$fingerprint] = $options;
    return $this;
  }

  /**
   * @param string $name
   *   Symbolic name. Ex: 'aes256-cbc'
   * @param CipherSuiteInterface $cipherSuite
   *   The encryption/decryption callback/handler
   * @return $this
   */
  public function addCipherSuite($name, CipherSuiteInterface $cipherSuite) {
    $this->cipherSuites[$name] = $cipherSuite;
    return $this;
  }

  public function getKeys() {
    return $this->keys;
  }

  public function findKey($keyId, $context = NULL) {
    if ($keyId === NULL) {
      // TODO Choose a default. This may depend on $context and/or $keys.
    }
    if (isset($this->keys[$keyId])) {
      return $this->keys[$keyId];
    }
    foreach ($this->keys as $key) {
      if (in_array($keyId, $key['aliases'])) {
        return $key;
      }
    }
    throw new CryptoException("Failed to find key ($keyId)");
  }

  /**
   * Create an encrypted token (given the plaintext).
   *
   * @param string $plainText
   *   The secret value to encode (e.g. plain-text password).
   * @param array $context
   *   Identify the context for which we are encrypting.
   *   Ex: ['entity' => 'MailSettings', 'field' => 'password']
   * @param string $keyId
   *   Identify the cryptosystem to use for this token.
   *
   * @return string
   *   A token
   */
  public function encrypt($plainText, $context = [], $keyId = NULL) {
    $key = $this->findKey($keyId, $context);
    $cipherSuite = $this->cipherSuites[$key['suite']] ?? fatal();
    $cipherText = $cipherSuite->encrypt($plainText, $key, $context);
    return $this->delim . $keyId . $this->delim . $cipherText . $this->delim;
  }

  /**
   * Get the plaintext (given an encrypted token).
   *
   * @param string $token
   * @param array $context
   *   Identify the context for which we are decrypting.
   *   Ex: ['entity' => 'MailSettings', 'field' => 'password']
   *
   * @return null
   */
  public function decrypt($token, $context = []) {
    $parts = explode($this->delim, $token);
    $keyId = $parts[1];
    $cipherText = $parts[2];

    $key = $this->keys[$keyId] ?? fatal();
    $cipherSuite = $this->cipherSuites[$key['suite']] ?? fatal();

    $plainText = $cipherSuite->decrypt($cipherText, $key, $context);
    return $plainText;
  }

}

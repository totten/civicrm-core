<?php

namespace Civi\Crypt;

use Civi\Core\Event\GenericHookEvent;

class Credential {

  protected $delim;

  public function __construct() {
    $this->delim = chr(0);
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
    if ($keyId === NULL) {
      // TODO Choose a default. This may depend on $context.
    }
    $cipherText = NULL;
    $e = GenericHookEvent::create([
      'keyId' => $keyId,
      'context' => &$context,
      'plainText' => $plainText,
      'cipherText' => &$cipherText,
    ]);
    // NOTE: using hook seems OK as long as we only expect a handful during runtime.
    \Civi::dispatcher()->dispatch('hook_civicrm_encryptToken', $e);
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
    $plainText = NULL;
    $e = GenericHookEvent::create([
      'keyId' => $parts[1],
      'context' => &$context,
      'cipherText' => $parts[2],
      'plainText' => &$plainText,
    ]);
    // NOTE: using hook seems OK as long as we only expect a handful during runtime.
    \Civi::dispatcher()->dispatch('hook_civicrm_decryptToken', $e);
    return $plainText;
  }

}

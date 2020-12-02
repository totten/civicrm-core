<?php

namespace Civi\Crypt;

class DefaultCiphers {

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \Civi\Crypt\Credential::encrypt()
   */
  public function onEncrypt($e) {
    switch ($e->keyId) {
      case 'plain':
        $e->cipherText = $e->plainText;
        $e->stopPropagation();;
        break;

      case 'legacy':
        $e->cipherText = \CRM_Utils_Crypt::encrypt($e->plainText);
        $e->stopPropagation();;
        break;
    }

    //    if (preg_match('/var_(.*)/', $e->keyId, $m)){
    //      /** @var \Crypt_Base $crypt */
    //      $crypt = $this->createConfigCrypt($m[1]);
    //      $e->cipherText = base64_encode($crypt->encrypt($e->plainText));
    //      $e->stopPropagation();
    //    }
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \Civi\Crypt\Credential::decrypt()
   */
  public function onDecrypt($e) {
    switch ($e->keyId) {
      case 'plain':
        $e->plainText = $e->cipherText;
        $e->stopPropagation();;
        break;

      case 'legacy':
        $e->plainText = \CRM_Utils_Crypt::decrypt($e->plainText);
        $e->stopPropagation();;
        break;
    }
    //    if (preg_match('/var_(.*)/', $e->keyId, $m)){
    //      /** @var \Crypt_Base $crypt */
    //      $crypt = $this->createConfigCrypt($m[1]);
    //      $e->cipherText = base64_encode($crypt->decrypt($e->plainText));
    //      $e->stopPropagation();
    //    }
  }

  //  /**
  //   * Lookup a key from `civicrm.settings.php` and create a phpseclib
  //   * cipher object.
  //   *
  //   * @param $keyName
  //   * @return \Crypt_Base
  //   */
  //  protected function createConfigCrypt($keyName) {
  //    global $civicrm_keys;
  //    if (!isset($civicrm_keys[$keyName])) {
  //      throw new \CRM_Core_Exception("Failed to locate encryption key \"$keyName\".");
  //    }
  //
  //  }

}

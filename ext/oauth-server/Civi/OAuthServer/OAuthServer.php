<?php

namespace Civi\OAuthServer;

use CRM_OAuthServer_ExtensionUtil as E;
use Civi\Core\Service\AutoService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Civi;

/**
 *
 */
class OAuthServer extends AutoService {


  protected ?\CRM_Utils_Cache_Interface $cache;

  /**
   * @service oauth_server
   * @inject crypto.registry, cache.long
   * @return \Civi\OAuthServer\OAuthServer
   */
  public static function factory(\Civi\Crypto\CryptoRegistry $registry, \CRM_Utils_Cache_Interface $cache = NULL) {
    $instance = new static();
    $instance->cache = $cache;
    // Registering our key via factory() means that we guarantee CRED key is already registered,
    // which helps with parsing. If using the factory is a problem, then CONNECT key probably
    // needs async registration, eg `$registry->addKey(['callback' => ...])`.
    if (!empty(Civi::settings()->get('oauth_civi_connect_keypair'))) {
      $registry->addKey($instance->createRegistration());
    }
    else {
      $instance->generateCreds();
    }
    return $instance;
  }

  /**
   * Generate a new key-pair to identify the current deployment.
   *
   * @return static
   */
  public function generateCreds(): CiviConnect {
    $keyPair = sodium_crypto_sign_keypair();
    Civi::settings()->set('oauth_server_eddsa',
      Civi::service('crypto.token')->encrypt($keyPair, 'CRED')
    );
    Civi::service('crypto.registry')->addKey($this->createRegistration());
    return $this;
  }

  /**
   * Generate metadata/registration record for our key-pair.
   *
   * @return array
   * @see \Civi\Crypto\CryptoRegistry::addKey()
   */
  protected function createRegistration(): array {
    $encryptedKeyPair = Civi::settings()->get('oauth_server_eddsa');
    $keyPair = Civi::service('crypto.token')->decrypt($encryptedKeyPair, 'CRED');
    return [
      'key' => $keyPair,
      'suite' => 'jwt-eddsa-keypair',
      'tags' => ['OAuthServer'],
      'id' => $this->createId($keyPair),
    ];
  }

  private function createId(string $keyPair): string {
    return 'eddsa_' . base64_encode(sodium_crypto_sign_publickey($keyPair));
  }

}

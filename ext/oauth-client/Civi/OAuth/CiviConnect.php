<?php

namespace Civi\OAuth;

use Civi;
use Civi\Core\Service\AutoService;

/**
 * Manage a connection to the `connect.civicrm.org` bridge-server.
 */
class CiviConnect extends AutoService {

  public static function isConfigured(): bool {
    $s = \Civi::settings();
    return !empty($s->get('oauth_civi_connect_id')) && !empty($s->get('oauth_civi_connect_keypair'));
  }

  /**
   * @service oauth_client.civi_connect
   * @inject crypto.registry
   * @return \Civi\OAuth\CiviConnect
   */
  public static function factory(\Civi\Crypto\CryptoRegistry $registry) {
    $instance = new static();
    if (!empty(Civi::settings()->get('oauth_civi_connect_keypair'))) {
      $registry->addKey($instance->createRegistration());
    }
    return $instance;
  }

  /**
   * Find or create the connection parameters for CiviConnect bridge service.
   *
   * @return array
   *   Tuple: [clientId, clientSecret]
   *   If there are no credentials, then both values are NULL.
   */
  public function getCreds(): ?array {
    return static::isConfigured() ? [$this->getId(), $this->createAuthToken()] : [NULL, NULL];
  }

  public function getId(): ?string {
    return Civi::settings()->get('oauth_civi_connect_id');
  }

  /**
   * Generate a new key-pair to identify the current deployment.
   *
   * @return static
   */
  public function generateCreds(): CiviConnect {
    $keyPair = sodium_crypto_sign_keypair();
    $id = 'eddsa_' . base64_encode(sodium_crypto_sign_publickey($keyPair));
    Civi::settings()->set('oauth_civi_connect_id', $id);
    Civi::settings()->set('oauth_civi_connect_keypair',
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
    $encryptedKeyPair = Civi::settings()->get('oauth_civi_connect_keypair');
    $keyPair = Civi::service('crypto.token')->decrypt($encryptedKeyPair, 'CRED');
    return [
      'key' => $keyPair,
      'suite' => 'jwt-eddsa-keypair',
      'tags' => ['CONNECT'],
      'id' => $this->getId(),
    ];
  }

  public function createAuthToken(): string {
    return Civi::service('crypto.jwt')->encode([
      'exp' => \CRM_Utils_Time::strtotime('+1 hour'),
      'scope' => 'CiviConnect',
    ], 'CONNECT');
  }

}

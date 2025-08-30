<?php

namespace Civi\OAuth;

use Civi;
use Civi\Core\Service\AutoService;

/**
 * @service oauth_client.civi_connect
 */
class CiviConnect extends AutoService {

  /**
   * Find or create the connection parameters for CiviConnect bridge service.
   *
   * @return array
   *   Tuple: [clientId, clientSecret]
   */
  public function getCreds(): array {
    $s = \Civi::settings();
    if (empty($s->get('oauth_civi_connect_id')) || empty($s->get('oauth_civi_connect_secret'))) {
      $this->generateCreds();
    }

    $encryptedSecret = $s->get('oauth_civi_connect_secret');
    return [
      $s->get('oauth_civi_connect_id'),
      Civi::service('crypto.token')->decrypt($encryptedSecret, 'CRED'),
    ];
  }

  public function generateCreds(): void {
    $rawSecret = random_bytes(32);
    $rawId = hash_hmac('sha256', 'challenged!', $rawSecret, TRUE);
    $id = 'rnd_' . \CRM_Utils_String::base64UrlEncode($rawId);
    $secret = \CRM_Utils_String::base64UrlEncode($rawSecret);

    Civi::settings()->set('oauth_civi_connect_id', $id);
    Civi::settings()->set('oauth_civi_connect_secret',
      Civi::service('crypto.token')->encrypt($secret, 'CRED')
    );
  }

}

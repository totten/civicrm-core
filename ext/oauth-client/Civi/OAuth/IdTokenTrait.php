<?php

namespace Civi\OAuth;

trait IdTokenTrait {

  abstract public function getJwks(): array;

  abstract public function decodeJwt(string $jwt): array;

  /**
   * Extract the decoded/validated ID token from the token-response.
   *
   * @param string $idToken
   *   Encoded JWT.
   * @return array|null
   *   List of JWT assertions (such as `sub` and `iss`).
   */
  public function parseIdToken(?string $idToken) {
    if ($idToken === NULL) {
      return NULL;
    }

    if (!empty($this->getJwks())) {
      return $this->decodeJwt($idToken);
    }

    // (Deprecated) OIDC exception 3.1.3.7[6] - If id_token comes from AuthorizationCode flow and TLS server,
    // then we can skip JWT/JWKS signature-checking.
    //
    // At time of writing, based on review of this extension and universe, there does not appear to be
    // a way to process an $idToken except when returned by the intended end-point (getBaseAccessTokenUrl).
    // So if getBaseAccessTokenUrl() has SSL/TLS, then the exception applies.
    //
    // However, this is not strongly guaranteed -- in particular, future/evolving/customized flows could
    // change things. To be prepared, we should phase-in JWKS.
    //
    // @link https://openid.net/specs/openid-connect-core-1_0.html#IDTokenValidation

    $baseAccessTokenUrl = $this->getBaseAccessTokenUrl([]);
    if (!preg_match(';^https:;', $baseAccessTokenUrl)) {
      \Civi::log()->error("CiviGenericProvider lacks JWKS to validate ID token. Please update OAuthProvider options to specify urlJwks. Upstream URL $baseAccessTokenUrl does NOT qualify for 3.1.3.7[6] exception. Rejected.");
      throw new \RuntimeException("Cannot decode ID token from insecure source.");
    }
    else {
      \Civi::log()->warning("CiviGenericProvider lacks JWKS to validate ID token. Please update OAuthProvider options to specify urlJwks. Upstream URL $baseAccessTokenUrl qualifies for 3.1.3.7[6] exception. Accepted.");
      $idTokenUnauth = $this->decodeUnauthenticatedJwt($idToken);
      return $idTokenUnauth['payload'];
    }
  }

  private function decodeUnauthenticatedJwt($t) {
    [$header, $payload] = explode('.', $t);

    return [
      'header' => json_decode(base64_decode($header), 1),
      'payload' => json_decode(base64_decode($payload), 1),
    ];
  }

}

<?php

namespace Civi\OAuth;

/**
 * FIXME: In PHP 8.2+, use trait-constant.
 */
define('_CIVI_OAUTH_DEFAULT_JWKS_ALGO', 'RS256');

/**
 * Many OAuth providers (esp OIDC-based) will provide signed tokens (JWTs). Parsing these
 * tokens requires access to the public-keys (JWKS) of the OAuth provider.
 *
 * Add this trait to a provider to support parsing signed tokens. Impelmenters should
 * set `urlJwks`, and they may parse tokens with `decodeJwt()`.
 */
trait JwksTrait {

  /**
   * @var string|null
   */
  protected $urlJwks;

  abstract protected function replaceUrlToken($str);

  public function getUrlJwks(): ?string {
    if (empty($this->urlJwks)) {
      return NULL;
    }
    return $this->replaceUrlToken($this->urlJwks);
  }

  /**
   * Fetch list of JWKs and store in Civi::cache('long').
   *
   * @return array
   * @throws \RuntimeException
   */
  public function getJwks(): array {
    $url = $this->getUrlJwks();
    if (empty($url)) {
      return [];
    }

    $cache = \Civi::cache('long');
    $cacheKey = 'oauth_jwks_' . md5($url);
    if ($cache->has($cacheKey)) {
      return $cache->get($cacheKey);
    }

    $request = $this->getRequest('GET', $url);
    $jwks = $this->getParsedResponse($request);
    $cache->set($cacheKey, $jwks);

    return $jwks;
  }

  /**
   * Decode a JWT using Firebase JWT library and keys from getJwks().
   *
   * @param string $jwt
   * @return array
   */
  public function decodeJwt(string $jwt): array {
    $jwks = $this->getJwks();
    if (empty($jwks)) {
      throw new \OAuthException("urlJwks is not set.");
    }
    $keys = \Firebase\JWT\JWK::parseKeySet($jwks, _CIVI_OAUTH_DEFAULT_JWKS_ALGO);
    return (array) \Firebase\JWT\JWT::decode($jwt, $keys);
  }

}

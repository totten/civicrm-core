<?php

namespace Civi\OAuth;

use Civi\Test\HeadlessInterface;
use Civi\Core\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class JwksTraitTest extends \PHPUnit\Framework\TestCase implements
    HeadlessInterface,
    HookInterface,
    TransactionalInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()->install('oauth-client')->apply();
  }

  /**
   * @return \League\OAuth2\Client\Provider\AbstractProvider|\Civi\OAuth\JwksTrait
   */
  protected function createProvider(array $options = [], array $collaborators = []) {
    // Quick and dirty way to make realistic class with JwksTrait.
    $this->assertContains(JwksTrait::class, class_uses(CiviGenericProvider::class));
    return new CiviGenericProvider($options, $collaborators);
  }

  public function testJwksUrl() {
    $provider = $this->createProvider([
      'urlAuthorize' => 'https://dummy/authorize',
      'urlAccessToken' => 'https://dummy/token',
      'urlResourceOwnerDetails' => 'https://dummy/userinfo',
      'urlJwks' => 'https://dummy/{{tenant}}/.well-known/jwks.json',
      'tenant' => 'tenant-123',
    ]);

    $this->assertEquals('https://dummy/tenant-123/.well-known/jwks.json', $provider->getUrlJwks());
  }

  public function testGetJwksAndCache() {
    $urlJwks = 'https://dummy/jwks.json';
    $mockJwks = [
      'keys' => [
        [
          'kty' => 'RSA',
          'alg' => 'RS256',
          'use' => 'sig',
          'kid' => 'key1',
          'n' => 'mockN',
          'e' => 'AQAB',
        ],
      ],
    ];

    $mockHandler = new \GuzzleHttp\Handler\MockHandler([
      new \GuzzleHttp\Psr7\Response(200, ['Content-Type' => 'application/json'], json_encode($mockJwks)),
    ]);
    $handlerStack = \GuzzleHttp\HandlerStack::create($mockHandler);
    $client = new \GuzzleHttp\Client(['handler' => $handlerStack]);

    $provider = $this->createProvider([
      'urlAuthorize' => 'https://dummy/authorize',
      'urlAccessToken' => 'https://dummy/token',
      'urlResourceOwnerDetails' => 'https://dummy/userinfo',
      'urlJwks' => $urlJwks,
    ], ['httpClient' => $client]);

    $cacheKey = 'oauth_jwks_' . md5($urlJwks);
    \Civi::cache('long')->delete($cacheKey);

    $jwks = $provider->getJwks();
    $this->assertEquals($mockJwks, $jwks);
    $this->assertTrue(\Civi::cache('long')->has($cacheKey));
    $this->assertEquals($mockJwks, \Civi::cache('long')->get($cacheKey));

    $jwksFromCache = $provider->getJwks();
    $this->assertEquals($mockJwks, $jwksFromCache);
  }

  public function testDecodeJwt() {
    $res = openssl_pkey_new([
      'digest_alg' => 'sha256',
      'private_key_bits' => 2048,
      'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);
    openssl_pkey_export($res, $privKey);
    $details = openssl_pkey_get_details($res);

    $n = \Firebase\JWT\JWT::urlsafeB64Encode($details['rsa']['n']);
    $e = \Firebase\JWT\JWT::urlsafeB64Encode($details['rsa']['e']);

    $urlJwks = 'https://dummy/jwks.json';
    $mockJwks = [
      'keys' => [
        [
          'kty' => 'RSA',
          'use' => 'sig',
          'kid' => 'key-1',
          'n' => $n,
          'e' => $e,
          'alg' => 'RS256',
        ],
      ],
    ];

    $cacheKey = 'oauth_jwks_' . md5($urlJwks);
    \Civi::cache('long')->set($cacheKey, $mockJwks);

    $provider = $this->createProvider([
      'urlAuthorize' => 'https://dummy/authorize',
      'urlAccessToken' => 'https://dummy/token',
      'urlResourceOwnerDetails' => 'https://dummy/userinfo',
      'urlJwks' => $urlJwks,
    ]);

    $payload = [
      'sub' => 'user-789',
      'name' => 'Alice',
      'iat' => time(),
    ];
    $jwt = \Firebase\JWT\JWT::encode($payload, $privKey, 'RS256', 'key-1');

    $decoded = $provider->decodeJwt($jwt);
    $this->assertEquals('user-789', $decoded['sub']);
    $this->assertEquals('Alice', $decoded['name']);
  }

}

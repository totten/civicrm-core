<?php
namespace Civi\OAuth;

use Civi;
use League\OAuth2\Client\Token\AccessToken;

/**
 * Class CiviGenericProvider
 * @package Civi\OAuth
 *
 * This is a variant of "GenericProvider" which tries to support some newer
 * conventions out-of-the-box.
 *
 * - By default, do not send "approval_prompt" for auth-code requests. Providers
 *   may prefer "prompt" nowadays.
 * - Allow one to fetch claims about the resource-owner from the `id_token`
 *   supported by OpenID Connect. This reduces the need for extra round-trips
 *   and proprietary scopes+URLs. To use this, set the option:
 *     "urlResourceOwnerDetails": "{{use_id_token}}",
 * - Allow support for {{tenant}} token in provider URLs, if the provider has
 *   the 'tenancy' option set to TRUE (eg: ms-exchange).
 */
class CiviGenericProvider extends \League\OAuth2\Client\Provider\GenericProvider {

  use ResponseModeTrait;
  use CiviConnectUrlTrait;

  // Add $urlJwks and decodeJwt(string $jwt) for examining JWTs sent by OAuth/OIDC.
  use JwksTrait;

  // Add parseIdToken(string $idToken) to help implement OIDC.
  use IdTokenTrait;

  /**
   * @var string
   */
  protected $tenant;

  /**
   * @var string
   */
  protected $url;

  /**
   * @var string
   */
  protected $realm;

  protected function getConfigurableOptions() {
    return array_merge(parent::getConfigurableOptions(), [
      'url',
      'tenant',
      'realm',
      'urlJwks',
    ]);
  }

  protected function fillProperties(array $options = []) {
    // If this client is generated for CiviConnect bridge, then swap the credentials.
    $needClient = ($options['clientId'] ?? NULL) === '{civi_connect}';
    $needSecret = ($options['clientSecret'] ?? NULL) === '{civi_connect}';
    if ($needClient && $needSecret) {
      [$options['clientId'], $options['clientSecret']] = Civi::service('oauth_client.civi_connect')->getCreds();
    }

    parent::fillProperties($options);
  }

  protected function getAuthorizationParameters(array $options) {
    $newOptions = parent::getAuthorizationParameters($options);
    if (!isset($options['approval_prompt'])) {
      // GenericProvider insists on filling in "approval_prompt", but this seems
      // to be disfavored nowadays b/c OpenID Connect defines "prompt".
      unset($newOptions['approval_prompt']);
    }
    return $newOptions;
  }

  /**
   * Returns the base URL for authorizing a client.
   *
   * Eg. https://oauth.service.com/authorize
   *
   * @return string
   */
  public function getBaseAuthorizationUrl() {
    $url = parent::getBaseAuthorizationUrl();
    return $this->replaceUrlToken($url);
  }

  /**
   * Returns the base URL for requesting an access token.
   *
   * Eg. https://oauth.service.com/token
   *
   * @param array $params
   * @return string
   */
  public function getBaseAccessTokenUrl(array $params) {
    $url = parent::getBaseAccessTokenUrl($params);
    return $this->replaceUrlToken($url);
  }

  /**
   * Replaces the following tokens in the end point url
   *
   * {{tenant}} 'common' for consumer accounts or the configured tenancy ID
   * {{url}} configured URL
   * {{realm}} configured Realm
   *
   * @param string $str URL to replace
   * @return string
   */
  protected function replaceUrlToken($str) {
    if (empty($str)) {
      return $str;
    }

    $vars = [
      // The !empty() seems a bit verbose, but `??` is strictly different, and this is how it was.
      '{{tenant}}' => !empty($this->tenant) ? $this->tenant : 'common',
      '{{realm}}' => !empty($this->realm) ? $this->realm : 'master',
      '{{url}}' => !empty($this->url) ? rtrim($this->url, '/') : '',
    ];
    return strtr($str, $vars);
  }

  /**
   * Requests resource owner details.
   *
   * @param \League\OAuth2\Client\Token\AccessToken $token
   * @return mixed
   */
  protected function fetchResourceOwnerDetails(AccessToken $token) {
    $url = $this->getResourceOwnerDetailsUrl($token);

    // If there is no resource-owner URL, and if there is an id_token, use it.
    if ($url === '{{use_id_token}}') {
      $idToken = $this->parseIdToken($token->jsonSerialize()['id_token'] ?? NULL);
      if ($idToken !== NULL) {
        return $idToken;
      }
    }

    return parent::fetchResourceOwnerDetails($token);
  }

}

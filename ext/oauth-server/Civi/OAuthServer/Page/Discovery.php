<?php

namespace Civi\OAuthServer\Page;

use CRM_OAuthServer_ExtensionUtil as E;

class Discovery extends \CRM_Core_Page {

  public function run() {
    \CRM_Utils_JSON::output($this->createIssuer());
  }

  public function createIssuer(): array {
    // return [
    //   'issuer' => Civi::url('civicrm/oauth-server/jwks.json')
    // ];
    return [
      "issuer" => Civi::url('civicrm/oauth-server/jwks.json'),
      "authorization_endpoint" => Civi::url('civicrm/oauth-server/authorize'),
      // "device_authorization_endpoint" => Civi::url('civicrm/oauth-server/device-code'),
      "token_endpoint" => Civi::url('civicrm/oauth-server/token'),
      "userinfo_endpoint" => Civi::url('civicrm/oauth-server/userinfo'),
      // "revocation_endpoint" => Civi::url('civicrm/oauth-server/revoke'),
      "jwks_uri" => Civi::url('civicrm/oauth-server/jwks.json'),
      "response_types_supported" => [
        "code",
        // "token",
        // "id_token",
        // "code token",
        // "code id_token",
        // "token id_token",
        // "code token id_token",
        // "none",
      ],
      "response_modes_supported" => [
        "query",
        // "fragment",
        // "form_post",
        // "web_message",
      ],
      "subject_types_supported" => [
        "public",
      ],
      "id_token_signing_alg_values_supported" => [
        "RS256",
      ],
      "scopes_supported" => [
        "openid",
        "email",
        "profile",
      ],
      "token_endpoint_auth_methods_supported" => [
        // We'll prefer POST because that would be more reliable cross-CMS.
        "client_secret_post",
        // "client_secret_basic",
      ],
      "claims_supported" => [
        "aud",
        "email",
        "email_verified",
        "exp",
        "family_name",
        "given_name",
        "iat",
        "iss",
        "name",
        "picture",
        "sub",
      ],
      // "code_challenge_methods_supported" => [
      //   "plain",
      //   "S256",
      // ],
      "grant_types_supported" => [
        "authorization_code",
        "refresh_token",
        // "urn:ietf:params:oauth:grant-type:device_code",
        // "urn:ietf:params:oauth:grant-type:jwt-bearer",
      ],

    ];

  }

}

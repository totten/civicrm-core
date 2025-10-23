<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\OAuthServer;

use Civi\Authx\Authenticator;
use Civi\Authx\AuthxException;
use Civi\Authx\CheckCredentialEvent;
use Civi\Core\Event\GenericHookEvent;
use Civi\Core\HookInterface;
use Civi\Core\Service\AutoService;
use GuzzleHttp\Psr7\Response;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * The OAuthServer has some routes (like `civicrm/oauth-server/token`) which accept
 * authentication via Client ID/Client Secret.
 *
 * @package Civi\Authx
 * @service oauth_server.authenticator
 */
class ClientAuthenticator extends AutoService implements EventSubscriberInterface {

  const TOKEN_ROUTE = 'civicrm/oauth-server/token';

  public static function getSubscribedEvents() {
    $events = [];
    $events['civi.invoke.auth'][] = ['onInvoke', 110];
    $events['civi.authx.checkCredential'][] = ['checkClientSecret', -400];
    // $events['civi.authx.checkPolicy'][] = ['checkPolicy', 400];
    return $events;
  }

  public function onInvoke(GenericHookEvent $e) {
    $path = implode('/', $e->args);
    if ($path === static::TOKEN_ROUTE) {
      $cred = $_SERVER['HTTP_X_CIVI_AUTH'] ?? $_SERVER['HTTP_AUTHORIZATION'] ?? '';
      if (str_starts_with($cred, 'Basic ')) {
        return \Civi::service('authx.authenticator')->auth($e, [
          'flow' => 'client_secret',
          'cred' => $cred,
        ]);
      }
      else {
        throw new AuthxException(static::TOKEN_ROUTE . ' requires Authorization: or X-Civi-Auth: with Basic credentials');
      }
    }
  }

  public function checkClientSecret(CheckCredentialEvent $check) {
    if ($check->getRequestPath() !== static::TOKEN_ROUTE) {
      return;
    }

    if ($check->credFormat !== 'Basic') {
      return;
    }

    $cred = base64_decode($check->credValue);
    [$clientId, $clientSecret] = explode(':', $cred, 2);
    // MySQL doesn't have a good way to resist timing-attacks, but
    $results = \CRM_Core_DAO::executeQuery('SELECT id, contact_id, client_id, client_secret FROM  WHERE client_id = %1', [
      1 => [$clientId, 'String'],
    ])->fetchAll();
    foreach ($results as $result) {
      if (hash_equals($result['client_secret'], $clientSecret)) {
        $check->accept(['contactId' => $result['contact_id'], 'credType' => 'client_secret']);
        return;
      }
    }

    $check->reject(static::TOKEN_ROUTE . ' requires valid client_id and client_secret');
  }

}

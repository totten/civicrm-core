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

namespace Civi\Core;

use Civi\API\Event\AuthorizeEvent;
use Civi\Core\Service\AutoService;
use GuzzleHttp\Psr7\Response;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service civi.core.maintenance
 */
class MaintenanceMode extends AutoService implements EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      '&civi.invoke.auth' => [['checkPageAuth', -1000]],
      'civi.api.authorize' => [['checkApiAuth', 1000]],
    ];
  }

  public function isAllowedByRight(): bool {
    return !\CRM_Utils_System::isMaintenanceMode() || \CRM_Core_Permission::check([['administer CiviCRM system', 'cms:bypass maintenance mode']]);
  }

  /**
   * @return array[]
   */
  private function getRoutePolicies(): array {
    return [
      'civicrm/ajax/.*' => ['allow' => 'none', 'type' => 'text/plain'],
      'civicrm/asset/builder' => ['allow' => 'all'],
      'civicrm/authx/.*' => ['allow' => 'all'],
      'civicrm/login' => ['allow' => 'all'],
      'civicrm/logout' => ['allow' => 'all'],
      // 'civicrm/home' => ['allow' => 'all'],
      '.' => ['allow' => 'none', 'type' => 'text/html'],
    ];
  }

  public function getApiPolicies(): array {
    return [
      'api4/.+/getFields' => ['allow' => 'all'],
      'api3/.+/getfields' => ['allow' => 'all'],
      'api4/User/.+' => ['allow' => 'all'],
      'api3/Job/.+' => ['allow' => 'bypass', 'type' => 'application/json'],
      '.' => ['allow' => 'bypass', 'type' => 'application/json'],
    ];
  }

  public function checkPageAuth(array $path): void {
    if ($this->isAllowedByRight()) {
      return;
    }

    $pathStr = implode('/', $path);
    if (preg_match(';^civicrm/ajax/api4/(\w+)/(\w+)$;', $pathStr, $matches)) {
      $pathStr = implode('/', ['api4', $matches[1], $matches[2]]);
      $policies = $this->getApiPolicies();
    }
    elseif (preg_match(';^civicrm/(ajax/rest|api/json);', $pathStr, $matches)) {
      $pathStr = implode('/', ['api3', $_REQUEST['entity'], $_REQUEST['action']]);
      $policies = $this->getApiPolicies();
    }
    else {
      $policies = $this->getRoutePolicies();
    }
    $bypassRequested = !empty($_REQUEST['run_in_maintenance_mode']);

    foreach ($policies as $pattern => $policy) {
      if (preg_match(';^' . $pattern . '$;', $pathStr)) {
        switch ($policy['allow']) {
          case 'all':
            return;

          case 'bypass':
            if ($bypassRequested) {
              return;
            }
            else {
              \CRM_Utils_System::sendResponse($this->createRejection($policy));
            }
            break;

          case 'none':
          default:
            \CRM_Utils_System::sendResponse($this->createRejection($policy));
            break;
        }
      }
    }
  }

  public function checkApiAuth(AuthorizeEvent $event) {
    $apiRequest = $event->getApiRequest();
    $pathStr = implode('/', ['api' . $apiRequest['version'], $apiRequest['entity'], $apiRequest['action']]);
    $policies = $this->getApiPolicies();
    $bypassRequested = !empty($_REQUEST['run_in_maintenance_mode']) || !empty($apiRequest['params']['run_in_maintenance_mode']);
    // FIXME: IIRC, APIv3 lets you sprinkle-in things like `run_in_maintenance_mode` anywhichway. But APIv4 may is aesthetically murkier.
    // And in both cases, one wants to consider the metadata. It really forces the question of how much we want to expose the bypass as a generic input.

    foreach ($policies as $pattern => $policy) {
      if (preg_match(';^' . $pattern . '$;', $pathStr)) {
        switch ($policy['allow']) {
          case 'all':
            return;

          case 'bypass':
            if ($bypassRequested) {
              return;
            }
            else {
              $event->setAuthorized(FALSE)->stopPropagation();
            }
            break;

          case 'none':
          default:
            $event->setAuthorized(FALSE)->stopPropagation();
            break;
        }
      }
    }
  }

  public function createRejection(array $policy): ResponseInterface {
    return match($policy['type']) {
      'application/json' => new Response(503, ['Content-type' => 'application/json'], json_encode([
        'status_code' => 503,
        'status_message' => 'Temporarily unavailable for maintenance',
      ])),

      'text/html' => new Response(503, ['Content-type' => 'text/html'], '<html><body><h1>HTTP 503: Temporarily unavailable for maintenance</h1></body></html>'),
      // FIXME: We need a way to make a Response with standard page-chrome.

      default => new Response(503, ['Content-type' => 'text/plain'], '503 Temporarily unavailable for maintenance'),
    };
  }

}

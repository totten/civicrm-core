<?php

namespace Civi\Core;

use Symfony\Component\EventDispatcher\ContainerAwareEventDispatcher;
use Symfony\Component\EventDispatcher\Event;

/**
 * Class CiviEventDispatcher
 * @package Civi\Core
 *
 * The CiviEventDispatcher is a Symfony dispatcher with additional support
 * for dispatching events through both Symfony as well as
 * CRM_Utils_Hook::invoke().
 *
 * @see \CRM_Utils_Hook
 */
class CiviEventDispatcher extends ContainerAwareEventDispatcher {

  /**
   * @inheritDoc
   */
  public function dispatch($eventName, Event $event = NULL) {
    $useHooks = (substr($eventName, 0, 5) === 'hook_') && (strpos($eventName, '::') === FALSE);

    parent::dispatch($eventName, $event);

    if (!$event->isPropagationStopped() && $useHooks) {
      /** @var \Civi\Core\Event\GenericHookEvent $event */
      self::dispatchHook($eventName, $event);
    }

    return $event;
  }

  /**
   * Invoke hooks using an event object.
   *
   * @param string $eventName
   *   Ex: 'hook_civicrm_dashboard'.
   * @param \Civi\Core\Event\GenericHookEvent $event
   */
  public static function dispatchHook($eventName, $event) {
    $hookName = substr($eventName, 5);
    $hooks = \CRM_Utils_Hook::singleton();
    $params = $event->getHookParams();
    $keys = $event->getHookParamOrder();
    $count = count($keys);

    switch ($count) {
      case 0:
        $fResult = $hooks->invoke($count, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, $hookName);
        break;

      case 1:
        $fResult = $hooks->invoke($count, $params[$keys[0]], \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, $hookName);
        break;

      case 2:
        $fResult = $hooks->invoke($count, $params[$keys[0]], $params[$keys[1]], \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, $hookName);
        break;

      case 3:
        $fResult = $hooks->invoke($count, $params[$keys[0]], $params[$keys[1]], $params[$keys[2]], \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, $hookName);
        break;

      case 4:
        $fResult = $hooks->invoke($count, $params[$keys[0]], $params[$keys[1]], $params[$keys[2]], $params[$keys[3]], \CRM_Utils_Hook::$_nullObject, \CRM_Utils_Hook::$_nullObject, $hookName);
        break;

      case 5:
        $fResult = $hooks->invoke($count, $params[$keys[0]], $params[$keys[1]], $params[$keys[2]], $params[$keys[3]], $params[$keys[4]], \CRM_Utils_Hook::$_nullObject, $hookName);
        break;

      case 6:
        $fResult = $hooks->invoke($count, $params[$keys[0]], $params[$keys[1]], $params[$keys[2]], $params[$keys[3]], $params[$keys[4]], $params[$keys[5]], $hookName);
        break;

      default:
        throw new \RuntimeException("hook_{$hookName} cannot support more than 6 parameters");
    }

    $event->addReturnValue($fResult);
  }

}

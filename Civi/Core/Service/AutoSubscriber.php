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
namespace Civi\Core\Service;

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * AutoSubscriber allows child classes to listen to events.
 *
 * Child classes must implement the `getSubscribedEvents` method, and the callbacks
 * it returns will be automatically registered.
 *
 * AutoSubscribers are registered as internal services, but they do not support annotations,
 * dependency-injection, etc.
 *
 * ^^^ [totten] Here is where it starts to lose me. These are the features that a service-container
 * offers, and these are the features that are being removed (for AutoSubscriber vs AutoServiceTrait).
 */
abstract class AutoSubscriber implements AutoServiceInterface, EventSubscriberInterface {

  /**
   * Register this class as a service in the container.
   *
   * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
   */
  final public static function buildContainer(ContainerBuilder $container): void {
    $id = static::CLASS;
    $reflection = new \ReflectionClass(static::CLASS);
    $file = $reflection->getFileName();
    $container->addResource(new \Symfony\Component\Config\Resource\FileResource($file));
    $definition = new Definition($id);
    $definition->setPublic(TRUE);
    $definition->addTag('internal');
    $definition->addTag('event_subscriber');
    $container->setDefinition($id, $definition);
  }

}

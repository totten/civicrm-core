<?php
// Generated via "mixin/upgrader-base@1/build.php 1.0"
namespace Civi\Mixin\UpgraderBaseV1;

  function registerClassAliases(array $classMap) {
    spl_autoload_register(function($requestedClass) use ($classMap) {
      if (isset($classMap[$requestedClass])) {
        class_alias($classMap[$requestedClass], $requestedClass);
      }
    });
  }

registerClassAliases([
  'Civi\\Mixin\\UpgraderBaseV1\\Base' => 'CRM_Extension_Upgrader_Base',
  'Civi\\Mixin\\UpgraderBaseV1\\IdentityTrait' => 'CRM_Extension_Upgrader_IdentityTrait',
  'Civi\\Mixin\\UpgraderBaseV1\\UpgraderInterface' => 'CRM_Extension_Upgrader_Interface',
  'Civi\\Mixin\\UpgraderBaseV1\\QueueTrait' => 'CRM_Extension_Upgrader_QueueTrait',
  'Civi\\Mixin\\UpgraderBaseV1\\RevisionsTrait' => 'CRM_Extension_Upgrader_RevisionsTrait',
  'Civi\\Mixin\\UpgraderBaseV1\\SchemaTrait' => 'CRM_Extension_Upgrader_SchemaTrait',
  'Civi\\Mixin\\UpgraderBaseV1\\TasksTrait' => 'CRM_Extension_Upgrader_TasksTrait',
]);
/**
 * Upgrader Base Class
 * @mixinName upgrader-base
 * @mixinVersion 1.0.1
 * @since 5.52
 */
return function($mixInfo, $bootCache) {
};

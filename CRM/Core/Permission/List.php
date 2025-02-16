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

/**
 * Class CRM_Core_Permission_List
 *
 * When presenting the administrator with a list of available permissions (`Permission.get`),
 * the methods in provide the default implementations.
 *
 * These methods are not intended for public consumption or frequent execution.
 *
 * @see \Civi\Api4\Action\Permission\Get
 */
class CRM_Core_Permission_List {

  /**
   * Enumerate concrete permissions that originate in CiviCRM (core or extension).
   *
   * @see \CRM_Utils_Hook::permissionList
   */
  public static function findCiviPermissions(array &$permissions): void {
    $allCorePerms = \CRM_Core_Permission::basicPermissions(TRUE, TRUE);
    foreach ($allCorePerms as $permName => $corePerm) {
      $permissions[$permName] = [
        'group' => 'civicrm',
        'title' => $corePerm['label'], /* Awesome */
        'description' => $corePerm['description'] ?? NULL,
        'is_active' => empty($corePerm['disabled']),
        'implies' => $corePerm['implies'] ?? NULL,
        'parent' => $corePerm['parent'] ?? NULL,
      ];
    }
  }

  /**
   * Enumerate permissions that originate in the CMS (core or module/plugin),
   * excluding any Civi permissions.
   *
   * @see \CRM_Utils_Hook::permissionList
   */
  public static function findCmsPermissions(array &$permissions): void {
    $config = \CRM_Core_Config::singleton();

    $ufPerms = $config->userPermissionClass->getAvailablePermissions();

    foreach ($ufPerms as $permName => $cmsPerm) {
      $permissions[$permName] = [
        'group' => 'cms',
        'title' => $cmsPerm['title'] ?? $permName,
        'description' => $cmsPerm['description'] ?? NULL,
        'is_synthetic' => $cmsPerm['is_synthetic'] ?? FALSE,
      ];
    }
  }

  /**
   * @see \CRM_Utils_Hook::permissionList
   */
  public static function findConstPermissions(array &$permissions): void {
    // There are a handful of special permissions defined in CRM/Core/Permission.
    // Enforcement of them is handled in `CRM_Core_Permission_*::check()`
    $permissions[\CRM_Core_Permission::ALWAYS_DENY_PERMISSION] = [
      'group' => 'const',
      'title' => ts('Generic: Deny all users'),
      'is_synthetic' => TRUE,
    ];
    $permissions[\CRM_Core_Permission::ALWAYS_ALLOW_PERMISSION] = [
      'group' => 'const',
      'title' => ts('Generic: Allow all users (including anonymous)'),
      'is_synthetic' => TRUE,
      // This line is here more as a bit of documentation (so it will show in `Civi\Api4\Permission::get()`).
      // The functionality that actually handles this pseudo-permission is in `CRM_Core_Permission_*::check()`
      'implies' => ['*'],
    ];
    $permissions[\CRM_Core_Permission::ANY_AUTHENTICATED_CONTACT] = [
      'group' => 'const',
      'title' => ts('Generic: Allow any authenticated contact'),
      'is_synthetic' => TRUE,
    ];
  }

}

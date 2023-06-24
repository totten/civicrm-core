<?php
use CRM_Standaloneusers_ExtensionUtil as E;

class CRM_Standaloneusers_BAO_User extends CRM_Standaloneusers_DAO_User implements \Civi\Core\HookInterface {

  /**
   * Create a new User based on array-data
   *
   * @param array $params key-value pairs
   * @return CRM_Standaloneusers_DAO_User|NULL
   *
   * public static function create($params) {
   * $className = 'CRM_Standaloneusers_DAO_User';
   * $entityName = 'User';
   * $hook = empty($params['id']) ? 'create' : 'edit';
   *
   * CRM_Utils_Hook::pre($hook, $entityName, CRM_Utils_Array::value('id', $params), $params);
   * $instance = new $className();
   * $instance->copyValues($params);
   * $instance->save();
   * CRM_Utils_Hook::post($hook, $entityName, $instance->id, $instance);
   *
   * return $instance;
   * } */

  /**
   * Event fired before modifying a User.
   * @param \Civi\Core\Event\PreEvent $event
   */
  public static function self_hook_civicrm_pre(\Civi\Core\Event\PreEvent $event) {
    if (in_array($event->action, ['create', 'edit'])) {
      if (isset($event->params['password']) && strpos($event->params['password'], '@HASH:') === 0) {
        $plain = substr($event->params['password'], 6);
        $security = \Civi\Standalone\Security::singleton();
        $event->params['password'] = $security->_password_crypt(\Civi\Standalone\Security::$hashMethod, $plain, $security->_password_generate_salt());
      }
    }
  }

}

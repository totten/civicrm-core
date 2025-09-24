<?php

namespace Civi\Api4;

use CRM_OAuth_ExtensionUtil as E;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\BasicEntity;
use Civi\Api4\Generic\BasicGetFieldsAction;
use Civi\Api4\Generic\Result;

class CiviConnect extends BasicEntity {

  public static function getFields() {
    return new BasicGetFieldsAction('CiviConnect', __FUNCTION__, fn() => []);
  }

  public static function enable(): AbstractAction {
    return new class('CiviConnect', __FUNCTION__) extends AbstractAction {

      public function _run(Result $result) {
        /**
         * @var \Civi\OAuth\CiviConnect $connect
         */
        $connect = \Civi::service('oauth_client.civi_connect');
        if (!$connect::isConfigured()) {
          $connect->generateCreds();
          \CRM_Core_ManagedEntities::singleton()->reconcile([E::LONG_NAME]);
        }
        $result[] = ['client_id' => $connect->getId()];
      }

    };
  }

  public static function disable(): AbstractAction {
    return new class('CiviConnect', __FUNCTION__) extends AbstractAction {

      public function _run(Result $result) {
        \Civi::settings()->revert('oauth_civi_connect_id');
        \Civi::settings()->revert('oauth_civi_connect_keypair');
        \CRM_Core_ManagedEntities::singleton()->reconcile([E::LONG_NAME]);
      }

    };
  }

}

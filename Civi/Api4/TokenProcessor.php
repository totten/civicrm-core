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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

namespace Civi\Api4;

/**
 * Utility API for evaluating templated expressions
 *
 * @searchable none
 * @package Civi\Api4
 */
class TokenProcessor extends Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Action\TokenProcessor\Render
   */
  public static function render($checkPermissions = TRUE) {
    return (new Action\TokenProcessor\Render(__CLASS__, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction(__CLASS__, __FUNCTION__, function() {
      // Would this be a good way to doucment 'contactId', 'activityId', etc?
      return [];
    }))->setCheckPermissions($checkPermissions);
  }

  public static function permissions() {
    return [
      'meta' => ['access CiviCRM'],
      'default' => ['administer CiviCRM'],
      'render' => [
        // nested array = OR
        [
          'edit message templates',
          'edit user-driven message templates',
          'edit system workflow message templates',
          'render templates',
        ],
      ],
    ];
  }

}

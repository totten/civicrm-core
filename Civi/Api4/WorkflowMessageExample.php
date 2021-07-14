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

namespace Civi\Api4;

use Civi\Api4\Generic\BasicGetAction;
use Civi\WorkflowMessage\ExampleScanner;

/**
 * CiviCRM menu route.
 *
 * Provides page routes registered in the CiviCRM menu system.
 *
 * Note: this is a read-only api as routes are set via xml files and hooks.
 *
 * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_alterMenu/
 * @searchable none
 * @since 5.19
 * @package Civi\Api4
 */
class WorkflowMessageExample extends \Civi\Api4\Generic\AbstractEntity {

  /**
   * @param bool $checkPermissions
   * @return Generic\AbstractGetAction
   */
  public static function get($checkPermissions = TRUE) {
    return (new class(__CLASS__, __FILE__) extends BasicGetAction {

      protected function getRecords() {
        $s = new ExampleScanner();
        $all = $s->findAll();

        $heavyFields = array_filter(['params', 'asserts'], [$this, '_isHeavyFieldSelected']);
        if (!empty($heavyFields)) {
          foreach ($all as &$item) {
            if (!empty($item['file']) && file_exists($item['file'])) {
              $json = \json_decode(file_get_contents($item['file']), 1);
            }
            foreach ($heavyFields as $heavyField) {
              $item[$heavyField] = $json[$heavyField] ?? NULL;
            }
          }
        }
        return $all;
      }

      protected function _isHeavyFieldSelected(string $field): bool {
        return in_array($field, $this->select) || $this->_whereContains($field);
      }

    })->setCheckPermissions($checkPermissions);
  }

  /**
   * @param bool $checkPermissions
   * @return Generic\BasicGetFieldsAction
   */
  public static function getFields($checkPermissions = TRUE) {
    return (new Generic\BasicGetFieldsAction(__CLASS__, __FUNCTION__, function () {
      return [
        [
          'name' => 'name',
          'title' => 'Example Name',
          'data_type' => 'String',
        ],
        [
          'name' => 'title',
          'title' => 'Example Title',
          'data_type' => 'String',
        ],
        [
          'name' => 'workflow',
          'title' => 'Workflow Name',
          'data_type' => 'String',
        ],
        [
          'name' => 'file',
          'title' => 'File Path',
          'data_type' => 'String',
          'description' => 'If the example is loaded from a file, this is the location.',
        ],
        [
          'name' => 'tags',
          'title' => 'Tags',
          'data_type' => 'String',
          'serialize' => \CRM_Core_DAO::SERIALIZE_SEPARATOR_BOOKEND,
        ],
        [
          'name' => 'params',
          'title' => 'Example Params',
          'data_type' => 'String',
          'serialize' => \CRM_Core_DAO::SERIALIZE_JSON,
        ],
        [
          'name' => 'asserts',
          'title' => 'Test assertions',
          'data_type' => 'String',
          'serialize' => \CRM_Core_DAO::SERIALIZE_JSON,
        ],
      ];
    }))->setCheckPermissions($checkPermissions);
  }

  /**
   * @return array
   */
  public static function permissions() {
    return [
      // FIXME: Perhaps use 'edit message templates' or similar?
      "meta" => ["access CiviCRM"],
      "default" => ["administer CiviCRM"],
    ];
  }

}

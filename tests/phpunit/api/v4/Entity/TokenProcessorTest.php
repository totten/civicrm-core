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


namespace api\v4\Entity;

use api\v4\UnitTestCase;

/**
 * @group headless
 */
class TokenProcessorTest extends UnitTestCase {

  public function getCreateBadExamples() {
    $es = [];

    $es['badFieldName'] = [
      [
        'status_id:name' => 'active',
        'entity_table' => 'civicrm_event',
        'entity_field' => 'zoological_taxonomy',
        'entity_id' => '*EVENT*',
        'language' => 'fr_CA',
        'string' => 'Hello world',
      ],
      '/non-existent or non-translatable field/',
    ];

    $es['badFieldType'] = [
      [
        'status_id:name' => 'active',
        'entity_table' => 'civicrm_event',
        'entity_field' => 'event_type_id',
        'entity_id' => '*EVENT*',
        'language' => 'fr_CA',
        'string' => '9',
      ],
      '/non-existent or non-translatable field/',
    ];

    $es['badEntityId'] = [
      [
        'records' => [],
        'context' => [],
        'messages' => [],
      ],
      '/Entity does not exist/',
    ];

    return $es;
  }

}

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
 * Class CRM_Case_WorkflowMessage_CaseActivityTest
 */
class CRM_Case_WorkflowMessage_CaseActivityTest extends CiviUnitTestCase {
  use \Civi\Test\WorkflowMessageTestTrait;

  public function getWorkflowClass(): string {
    return CRM_Case_WorkflowMessage_CaseActivity::class;
  }

}

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

use Civi\WorkflowMessage\WorkflowMessage;

class CRM_Case_WorkflowMessage_CaseActivityTest extends CiviUnitTestCase {
  use \Civi\Test\WorkflowMessageTestTrait;

  /**
   * Ensure that various methods of constructing a WorkflowMessage all produce similar results.
   * We use `case_activity` as a concrete example.
   */
  public function testConstructorEquivalence() {
    $examples = $this->createExamples();

    $instances = [];
    foreach ($examples as $exampleName => $exampleProps) {
      $instances["factory_$exampleName"] = WorkflowMessage::create('case_activity', $exampleProps);
      $instances["class_$exampleName"] = new CRM_Case_WorkflowMessage_CaseActivity($exampleProps);
    }

    /** @var \Civi\WorkflowMessage\WorkflowMessageInterface $refInstance */
    /** @var \Civi\WorkflowMessage\WorkflowMessageInterface $cmpInstance */

    $refName = $refInstance = NULL;
    $comparisons = 0;
    foreach ($instances as $cmpName => $cmpInstance) {
      if ($refName === NULL) {
        $refName = $cmpName;
        $refInstance = $cmpInstance;
        continue;
      }

      $this->assertSameWorkflowMessage($refInstance, $cmpInstance, "Compare $refName vs $cmpName: ");
      $comparisons++;
    }
    $this->assertTrue($comparisons > 0 && $comparisons === (2 * count($examples) - 1));
  }

  /**
   * @return array
   */
  protected function createExamples(): array {
    $client_id = $this->individualCreate();
    $contact_id = $this->individualCreate();

    $examples = [];
    $examples['adhoc_1'] = [
      'contactId' => $contact_id,
      'tplParams' => [
        'isCaseActivity' => 1,
        'client_id' => $client_id,
        'activityTypeName' => 'Follow up',
        'activitySubject' => 'Test 123',
        'idHash' => substr(sha1(CIVICRM_SITE_KEY . '1234'), 0, 7),
        'activity' => [
          'fields' => [
            [
              'label' => 'Case ID',
              'type' => 'String',
              'value' => '1234',
            ],
          ],
        ],
      ],
    ];
    $examples['adhoc_2'] = [
      'tokenContext' => [
        'contactId' => $contact_id,
      ],
      'tplParams' => [
        'isCaseActivity' => 1,
        'client_id' => $client_id,
        'activityTypeName' => 'Follow up',
        'activitySubject' => 'Test 123',
        'idHash' => substr(sha1(CIVICRM_SITE_KEY . '1234'), 0, 7),
        'activity' => [
          'fields' => [
            [
              'label' => 'Case ID',
              'type' => 'String',
              'value' => '1234',
            ],
          ],
        ],
      ],
    ];
    $examples['modelProps_1'] = [
      'modelProps' => [
        'contactId' => $contact_id,
        'isCaseActivity' => 1,
        'clientId' => $client_id,
        'activityTypeName' => 'Follow up',
        'activityFields' => [
          [
            'label' => 'Case ID',
            'type' => 'String',
            'value' => '1234',
          ],
        ],
        'activitySubject' => 'Test 123',
        'idHash' => substr(sha1(CIVICRM_SITE_KEY . '1234'), 0, 7),
      ],
    ];
    return $examples;
  }

}

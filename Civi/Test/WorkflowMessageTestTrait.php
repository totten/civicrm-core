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

namespace Civi\Test;

use Civi\WorkflowMessage\WorkflowMessage;

trait WorkflowMessageTestTrait {

  abstract public function getWorkflowClass(): string;

  /**
   * Ensure that various methods of constructing a WorkflowMessage all produce similar results.
   *
   * To see this, we take all the example data
   */
  public function testConstructorEquivalence() {
    $examples = \Civi\Api4\WorkflowMessageExample::get(0)
      ->setSelect(['name', 'params', 'asserts'])
      ->addWhere('workflow', '=', 'case_activity')
      ->addWhere('tags', 'CONTAINS', 'phpunit')
      ->execute()
      ->indexBy('name')
      ->column('params');
    $this->assertTrue(count($examples) > 1, 'Must have at least one example data-set');

    $class = $this->getWorkflowClass();
    $instances = [];
    foreach ($examples as $exampleName => $exampleProps) {
      $instances["factory_$exampleName"] = WorkflowMessage::create($class::WORKFLOW, $exampleProps);
      $instances["class_$exampleName"] = new $class($exampleProps);
    }

    /** @var \Civi\WorkflowMessage\WorkflowMessageInterface $refInstance */
    /** @var \Civi\WorkflowMessage\WorkflowMessageInterface $cmpInstance */

    // FIXME: we should use $example['asserts'] and check for specific equivalences rather than assuming all examples equiv.

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
   * @param \Civi\WorkflowMessage\WorkflowMessageInterface $refInstance
   * @param \Civi\WorkflowMessage\WorkflowMessageInterface $cmpInstance
   * @param string $prefix
   */
  protected function assertSameWorkflowMessage(\Civi\WorkflowMessage\WorkflowMessageInterface $refInstance, \Civi\WorkflowMessage\WorkflowMessageInterface $cmpInstance, string $prefix): void {
    $this->assertEquals($refInstance->export('tplParams'), $cmpInstance->export('tplParams'), "{$prefix}Should have same export(tplParams)");
    $this->assertEquals($refInstance->export('tokenContext'), $cmpInstance->export('tokenContext'), "{$prefix}should have same export(tokenContext)");
    $this->assertEquals($refInstance->export('envelope'), $cmpInstance->export('envelope'), "{$prefix}Should have same export(envelope)");
    $refExportAll = WorkflowMessage::exportAll($refInstance);
    $cmpExportAll = WorkflowMessage::exportAll($cmpInstance);
    $this->assertEquals($refExportAll, $cmpExportAll, "{$prefix}Should have same exportAll()");
  }

}

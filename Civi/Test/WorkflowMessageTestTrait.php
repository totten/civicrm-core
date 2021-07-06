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

  /**
   * @var string|null
   */
  protected $examplesDir;

  /**
   * @return string
   * @throws \ReflectionException
   */
  protected function getExamplesDir() {
    if ($this->examplesDir === NULL) {
      $c = new \ReflectionClass(get_class($this));
      $this->examplesDir = preg_replace('/\.php$', '', $c->getFileName());
    }
    return $this->examplesDir;
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

<?php

/**
 * @group headless
 */
class CRM_Example_FirstTest extends \PHPUnit\Framework\TestCase implements \Civi\Test\HeadlessInterface {

  public function setupHeadless() {
    // This setup just uses basic defaults (CiviCRM-core, mandatory extensions, etc)
    return \Civi\Test::headless()->apply();
  }

  public function testExtensions_1() {
    $exts = CRM_Extension_System::singleton()->getMapper();
    $this->assertEquals(FALSE, $exts->isActiveModule('tellafriend'), 'tellafriend should be inactive by default');
    $this->assertEquals(FALSE, $exts->isActiveModule('scheduled_communications'), 'scheduled_communications should be inactive by default');
    $this->assertEquals(FALSE, $exts->isActiveModule('legacycustomsearches'), 'legacycustomsearches should be inactive by default');
  }

  public function testExtensions_2() {
    $exts = CRM_Extension_System::singleton()->getMapper();
    $this->assertEquals(FALSE, $exts->isActiveModule('tellafriend'), 'tellafriend should be inactive by default');
    $this->assertEquals(FALSE, $exts->isActiveModule('scheduled_communications'), 'scheduled_communications should be inactive by default');
    $this->assertEquals(FALSE, $exts->isActiveModule('legacycustomsearches'), 'legacycustomsearches should be inactive by default');
  }

}

<?php

/**
 * @group headless
 */
class CRM_Example_ThirdTest extends \PHPUnit\Framework\TestCase implements \Civi\Test\HeadlessInterface {

  public function setupHeadless() {
    // This setup uses basic defaults -- and enables two extensions.
    return \Civi\Test::headless()->install(['scheduled_communications', 'legacycustomsearches'])->apply();
  }

  public function testExtensions_1() {
    $exts = CRM_Extension_System::singleton()->getMapper();
    $this->assertEquals(FALSE, $exts->isActiveModule('tellafriend'), 'tellafriend should be inactive by default');
    $this->assertEquals(TRUE, $exts->isActiveModule('scheduled_communications'), 'scheduled_communications should be active by default');
    $this->assertEquals(TRUE, $exts->isActiveModule('legacycustomsearches'), 'legacycustomsearches should be active by default');
  }

  public function testExtensions_2() {
    $exts = CRM_Extension_System::singleton()->getMapper();
    $this->assertEquals(FALSE, $exts->isActiveModule('tellafriend'), 'tellafriend should be inactive by default');
    $this->assertEquals(TRUE, $exts->isActiveModule('scheduled_communications'), 'scheduled_communications should be active by default');
    $this->assertEquals(TRUE, $exts->isActiveModule('legacycustomsearches'), 'legacycustomsearches should be active by default');
  }

}

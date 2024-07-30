<?php

/**
 * @group headless
 */
class CRM_Example_SecondTest extends \PHPUnit\Framework\TestCase implements \Civi\Test\HeadlessInterface {

  public function setupHeadless() {
    // This setup uses basic defaults -- and enables the extension `tellafriend`.
    return \Civi\Test::headless()->install('tellafriend')->apply();
  }

  public function testExtensions_1() {
    $exts = CRM_Extension_System::singleton()->getMapper();
    $this->assertEquals(TRUE, $exts->isActiveModule('tellafriend'), 'tellafriend should be active by default');
    $this->assertEquals(FALSE, $exts->isActiveModule('scheduled_communications'), 'scheduled_communications should be inactive by default');
    $this->assertEquals(FALSE, $exts->isActiveModule('legacycustomsearches'), 'legacycustomsearches should be inactive by default');
  }

  public function testExtensions_2() {
    $exts = CRM_Extension_System::singleton()->getMapper();
    $this->assertEquals(TRUE, $exts->isActiveModule('tellafriend'), 'tellafriend should be active by default');
    $this->assertEquals(FALSE, $exts->isActiveModule('scheduled_communications'), 'scheduled_communications should be inactive by default');
    $this->assertEquals(FALSE, $exts->isActiveModule('legacycustomsearches'), 'legacycustomsearches should be inactive by default');
  }

}

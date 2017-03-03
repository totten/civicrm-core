<?php
namespace Civi\Core\Event;

class GenericHookEventTest extends \CiviUnitTestCase {

  public function tearDown() {
    \CRM_Utils_Hook::singleton()->reset();
    parent::tearDown();
  }

  public function testDispatch() {
    \CRM_Utils_Hook::singleton()->setHook('civicrm_ghet',
      array($this, 'hook_civicrm_ghet'));
    \Civi::service('dispatcher')->addListener('hook_civicrm_ghet',
      array($this, 'onGhet'));

    $roString = 'readonly';
    $rwString = 'readwrite';
    $roArray = array('readonly');
    $rwArray = array('readwrite');
    $plainObj = new \stdClass();
    $refObj = new \stdClass();

    $returnValue = $this->runStub($roString, $rwString, $roArray, $rwArray, $plainObj, $refObj);

    $this->assertEquals('readonly', $roString);
    $this->assertEquals('readwrite added-string-via-event added-string-via-hook', $rwString);
    $this->assertEquals(array('readonly'), $roArray);
    $this->assertEquals(array('readwrite', 'added-to-array-via-event', 'added-to-array-via-hook'), $rwArray);
    $this->assertEquals('added-to-object-via-hook', $plainObj->prop1);
    $this->assertEquals('added-to-object-via-hook', $refObj->prop2);
    $this->assertEquals(array('early-running-result', 'late-running-result'), $returnValue);
  }

  public function runStub($roString, &$rwString, $roArray, &$rwArray, $plainObj, &$refObj) {
    $e = new GenericHookEvent(array(
      'roString' => $roString,
      'rwString' => &$rwString,
      'roArray' => $roArray,
      'rwArray' => &$rwArray,
      'plainObj' => $plainObj,
      'refObj' => &$refObj,
    ));
    \Civi::service('dispatcher')->dispatch('hook_civicrm_ghet', $e);
    return $e->getReturnValue();
  }

  public function hook_civicrm_ghet(&$roString, &$rwString, &$roArray, &$rwArray, $plainObj, &$refObj) {
    $roString .= 'changes should not propagate back';
    $rwString .= ' added-string-via-hook';
    $roArray[] = 'changes should not propagate back';
    $rwArray[] = 'added-to-array-via-hook';
    $plainObj->prop1 = 'added-to-object-via-hook';
    $refObj->prop2 = 'added-to-object-via-hook';
    return array('late-running-result');
  }

  public function onGhet(GenericHookEvent $e) {
    $e->roString .= 'changes should not propagate back';
    $e->rwString .= ' added-string-via-event';
    $e->roArray[] = 'changes should not propagate back';
    $e->rwArray[] = 'added-to-array-via-event';
    $e->plainObj->prop1 = 'added-to-object-via-event';
    $e->refObj->prop2 = 'added-to-object-via-event';
    $e->addReturnValue(array('early-running-result'));
  }

}

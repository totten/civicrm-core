<?php

namespace E2E\Core;

/**
 * Class UserOverrideTest
 *
 * Check that overriding session user behaves as expected.
 *
 * @package E2E\Core
 * @group e2e
 */
class UserOverrideTest extends \CiviEndToEndTestCase {

  protected function setUp() {
    parent::setUp();
  }

  protected function tearDown() {
    while (\CRM_Core_Session::singleton()->closeSubsession()) {
      // Loop until all overrides are cleared
    }
  }

  public function testOverride() {
    $session = \CRM_Core_Session::singleton();
    $originalUser = $session::getLoggedInContactID();
    $session->set('favoriteColor', 'red');
    $this->assertEquals('red', $session->get('favoriteColor'));
    $this->assertEquals(FALSE, $session->isSubsession());

    $session->createSubsession(['userID' => 2]);
    $session->set('favoriteColor', 'orange');
    $this->assertEquals(2, $session::getLoggedInContactID());
    $this->assertEquals(TRUE, $session->isSubsession());
    $this->assertEquals('orange', $session->get('favoriteColor'));

    $session->createSubsession(['userID' => 3]);
    $session->set('favoriteColor', 'yellow');
    $this->assertEquals(3, $session::getLoggedInContactID());
    $this->assertEquals(TRUE, $session->isSubsession());
    $this->assertEquals('yellow', $session->get('favoriteColor'));

    $session->createSubsession(['userID' => 4]);
    $session->set('favoriteColor', 'green');
    $this->assertEquals(4, $session::getLoggedInContactID());
    $this->assertEquals(TRUE, $session->isSubsession());
    $this->assertEquals('green', $session->get('favoriteColor'));

    // Unwind intermediate overrides

    $this->assertTrue($session->closeSubsession());
    $this->assertEquals(3, $session::getLoggedInContactID());
    $this->assertEquals(TRUE, $session->isSubsession());
    $this->assertEquals('yellow', $session->get('favoriteColor'));

    $this->assertTrue($session->closeSubsession());
    $this->assertEquals(2, $session::getLoggedInContactID());
    $this->assertEquals(TRUE, $session->isSubsession());
    $this->assertEquals('orange', $session->get('favoriteColor'));

    // Clear the final override

    $this->assertTrue($session->closeSubsession());
    $this->assertEquals($originalUser, $session::getLoggedInContactID());
    $this->assertEquals(FALSE, $session->isSubsession());
    $this->assertEquals('red', $session->get('favoriteColor'));

    $this->assertFalse($session->closeSubsession());
  }

}

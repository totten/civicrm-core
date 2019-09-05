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
    while (\CRM_Core_Session::singleton()->restoreCurrentUser()) {
      // Loop until all overrides are cleared
    }
  }

  public function testOverride() {
    $session = \CRM_Core_Session::singleton();
    $originalUser = $session::getLoggedInContactID();
    $session->set('favoriteColor', 'red');
    $this->assertEquals('red', $session->get('favoriteColor'));

    $session->overrideCurrentUser(2);
    $session->set('favoriteColor', 'orange');
    $this->assertEquals(2, $session::getLoggedInContactID());
    $this->assertEquals(2, $session->getOverriddenUser());
    $this->assertEquals('orange', $session->get('favoriteColor'));

    $session->overrideCurrentUser(3);
    $session->set('favoriteColor', 'yellow');
    $this->assertEquals(3, $session::getLoggedInContactID());
    $this->assertEquals(3, $session->getOverriddenUser());
    $this->assertEquals('yellow', $session->get('favoriteColor'));

    $session->overrideCurrentUser(4);
    $session->set('favoriteColor', 'green');
    $this->assertEquals(4, $session::getLoggedInContactID());
    $this->assertEquals(4, $session->getOverriddenUser());
    $this->assertEquals('green', $session->get('favoriteColor'));

    // Unwind intermediate overrides

    $this->assertTrue($session->restoreCurrentUser());
    $this->assertEquals(3, $session::getLoggedInContactID());
    $this->assertEquals(3, $session->getOverriddenUser());
    $this->assertEquals('yellow', $session->get('favoriteColor'));

    $this->assertTrue($session->restoreCurrentUser());
    $this->assertEquals(2, $session::getLoggedInContactID());
    $this->assertEquals(2, $session->getOverriddenUser());
    $this->assertEquals('orange', $session->get('favoriteColor'));

    // Clear the final override

    $this->assertTrue($session->restoreCurrentUser());
    $this->assertEquals($originalUser, $session::getLoggedInContactID());
    $this->assertNull($session->getOverriddenUser());
    $this->assertEquals('red', $session->get('favoriteColor'));

    $this->assertFalse($session->restoreCurrentUser());
  }

}

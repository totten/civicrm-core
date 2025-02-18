<?php

/**
 * Class CRM_Core_PermissionTest
 * @group headless
 * @group permissions
 */
class CRM_Core_PermissionTest extends CiviUnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    $this->useTransaction();
    // Other assertions will fail if the basic environment changes.
    $this->assertTrue(in_array('CiviContribute', Civi::settings()->get('enable_components')));
    $this->assertFalse(in_array('CiviCampaign', Civi::settings()->get('enable_components')));
  }

  public function testBasicPermissions() {
    $perms = CRM_Core_Permission::basicPermissions();

    $this->assertTrue(isset($perms['administer CiviCRM']), '"administer CiviCRM" should be defined');
    $this->assertEquals('CiviCRM: administer CiviCRM', $perms['administer CiviCRM']);

    $this->assertTrue(isset($perms['edit contributions']), '"edit contributions"  should be enabled when CiviContribute is enabled');
    $this->assertFalse(isset($perms['manage campaign']), '"manage campaign"  should be disabled when CiviCampaign is disabled');

    $this->assertFalse(isset($perms['*always deny*']), '"*always deny*"  should not be a basic permission');
    $this->assertFalse(isset($perms['cms:administer users']), '"cms:administer users"  should not be a basic permission');
  }

  public function testBasicPermissions_disabled() {
    $perms = CRM_Core_Permission::basicPermissions(TRUE);

    $this->assertTrue(isset($perms['administer CiviCRM']), '"administer CiviCRM" should be defined');
    $this->assertEquals('CiviCRM: administer CiviCRM', $perms['administer CiviCRM']);

    $this->assertTrue(isset($perms['edit contributions']), '"edit contributions"  should be enabled when CiviContribute is enabled');
    $this->assertTrue(isset($perms['manage campaign']), '"manage campaign"  should be enabled when CiviCampaign is enabled');

    $this->assertFalse(isset($perms['*always deny*']), '"*always deny*"  should not be a basic permission');
    $this->assertFalse(isset($perms['cms:administer users']), '"cms:administer users"  should not be a basic permission');
  }

  public function testBasicPermissions_array() {
    $perms = CRM_Core_Permission::basicPermissions(FALSE, TRUE);

    // Take an example. Make sure it's well-formed.
    $this->assertTrue(isset($perms['administer CiviCRM']));
    $this->assertEquals('CiviCRM: administer CiviCRM', $perms['administer CiviCRM']['label']);
    $this->assertStringContainsString('Perform all tasks', $perms['administer CiviCRM']['description']);
    $this->assertTrue(in_array('access CiviCRM', $perms['administer CiviCRM']['implies']), 'Administrative access should imply basic access');

    $this->assertTrue(isset($perms['edit contributions']), '"edit contributions"  should be enabled when CiviContribute is enabled');
    $this->assertFalse(isset($perms['manage campaign']), '"manage campaign"  should be disabled when CiviCampaign is disabled');

    $this->assertFalse(isset($perms['*always deny*']), '"*always deny*"  should not be a basic permission');
    $this->assertFalse(isset($perms['cms:administer users']), '"cms:administer users"  should not be a basic permission');
  }

}

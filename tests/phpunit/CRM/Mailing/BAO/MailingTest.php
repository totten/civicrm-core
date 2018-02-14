<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2017                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */

/**
 * Class CRM_Mailing_BAO_MailingTest
 */
class CRM_Mailing_BAO_MailingTest extends CiviUnitTestCase {

  public function setUp() {
    parent::setUp();
  }

  /**
   * Test CRM_Mailing_BAO_Mailing::getRecipients() on sms mode
   */
  public function testgetRecipients() {
    // Tests for SMS bulk mailing recipients
    // +CRM-21320 Ensure primary mobile number is selected over non-primary

    // Setup
    $smartGroupParams = array(
      'formValues' => array('contact_type' => array('IN' => array('Individual'))),
    );
    $group = $this->smartGroupCreate($smartGroupParams);
    $sms_provider = $this->callAPISuccess('SmsProvider', 'create', array(
      'sequential' => 1,
      'name' => 1,
      'title' => "Test",
      'username' => "Test",
      'password' => "Test",
      'api_type' => 1,
      'is_active' => 1,
    ));

    // Create Contact 1 and add in group
    $contactID1 = $this->individualCreate(array(), 0);
    $this->callAPISuccess('GroupContact', 'Create', array(
      'group_id' => $group,
      'contact_id' => $contactID1,
    ));

    // Create contact 2 and add in group
    $contactID2 = $this->individualCreate(array(), 1);
    $this->callAPISuccess('GroupContact', 'Create', array(
      'group_id' => $group,
      'contact_id' => $contactID2,
    ));

    $contactIDPhoneRecords = array(
      $contactID1 => array(
        'primary_phone_id' => CRM_Utils_Array::value('id', $this->callAPISuccess('Phone', 'create', array(
          'contact_id' => $contactID1,
          'phone' => "01 01",
          'location_type_id' => "Home",
          'phone_type_id' => "Mobile",
          'is_primary' => 1,
        ))),
        'other_phone_id' => CRM_Utils_Array::value('id', $this->callAPISuccess('Phone', 'create', array(
          'contact_id' => $contactID1,
          'phone' => "01 02",
          'location_type_id' => "Work",
          'phone_type_id' => "Mobile",
          'is_primary' => 0,
        ))),
      ),
      $contactID2 => array(
        'primary_phone_id' => CRM_Utils_Array::value('id', $this->callAPISuccess('Phone', 'create', array(
          'contact_id' => $contactID2,
          'phone' => "02 01",
          'location_type_id' => "Home",
          'phone_type_id' => "Mobile",
          'is_primary' => 1,
        ))),
        'other_phone_id' => CRM_Utils_Array::value('id', $this->callAPISuccess('Phone', 'create', array(
          'contact_id' => $contactID2,
          'phone' => "02 02",
          'location_type_id' => "Work",
          'phone_type_id' => "Mobile",
          'is_primary' => 0,
        ))),
      ),
    );

    // Prepare expected results
    $checkPhoneIDs = array(
      $contactID1 => $contactIDPhoneRecords[$contactID1]['primary_phone_id'],
      $contactID2 => $contactIDPhoneRecords[$contactID2]['primary_phone_id'],
    );

    // Create mailing
    $mailing = $this->callAPISuccess('Mailing', 'create', array('sms_provider_id' => $sms_provider['id']));
    $mailing_include_group = $this->callAPISuccess('MailingGroup', 'create', array(
      'mailing_id' => $mailing['id'],
      'group_type' => "Include",
      'entity_table' => "civicrm_group",
      'entity_id' => $group,
    ));

    // Populate the recipients table (job id doesn't matter)
    CRM_Mailing_BAO_Mailing::getRecipients($mailing['id']);

    // Get recipients
    $recipients = $this->callAPISuccess('MailingRecipients', 'get', array('mailing_id' => $mailing['id']));

    // Check the count is correct
    $this->assertEquals(2, $recipients['count'], 'Check recipient count');

    // Check we got the 'primary' mobile for both contacts
    foreach ($recipients['values'] as $value) {
      $this->assertEquals($value['phone_id'], $checkPhoneIDs[$value['contact_id']], 'Check correct phone number for contact ' . $value['contact_id']);
    }

    // Tidy up
    $this->deleteMailing($mailing['id']);
    $this->callAPISuccess('SmsProvider', 'Delete', array('id' => $sms_provider['id']));
    $this->groupDelete($group);
    $this->contactDelete($contactID1);
    $this->contactDelete($contactID2);
  }

  /**
   * Test alterMailingRecipients Hook which is called twice when we create a Mailing,
   *  1. In the first call we will modify the mailing filter to include only deceased recipients
   *  2. In the second call we will check if only deceased recipient is populated in MailingRecipient table
   */
  public function testAlterMailingRecipientsHook() {
    $groupID = $this->groupCreate();

    // Create deseased Contact 1 and add in group
    $contactID1 = $this->individualCreate(array('email' => 'abc@test.com', 'is_deceased' => 1), 0);
    // Create deseased Contact 2 and add in group
    $contactID2 = $this->individualCreate(array('email' => 'def@test.com'), 1);
    // Add both the created contacts in group
    $this->callAPISuccess('GroupContact', 'Create', array(
      'group_id' => $groupID,
      'contact_id' => $contactID1,
    ));
    $this->callAPISuccess('GroupContact', 'Create', array(
      'group_id' => $groupID,
      'contact_id' => $contactID2,
    ));
    // trigger the alterMailingRecipients hook
    $this->hookClass->setHook('civicrm_alterMailingRecipients', array($this, 'alterMailingRecipients'));

    // create mailing that will trigger alterMailingRecipients hook
    $params = array(
      'name' => 'mailing name',
      'subject' => 'Test Subject',
      'body_html' => '<p>HTML Body</p>',
      'text_html' => 'Text Body',
      'created_id' => 1,
      'groups' => array('include' => array($groupID)),
      'scheduled_date' => 'now',
    );
    $this->callAPISuccess('Mailing', 'create', $params);
  }

  /**
   * @implements CRM_Utils_Hook::alterMailingRecipients
   *
   * @param object $mailingObject
   * @param array $params
   * @param string $context
   */
  public function alterMailingRecipients(&$mailingObject, &$params, $context) {
    if ($context == 'pre') {
      // modify the filter to include only deceased recipient(s)
      $params['filters']['is_deceased'] = 'civicrm_contact.is_deceased = 1';
    }
    else {
      $mailingRecipients = $this->callAPISuccess('MailingRecipients', 'get', array(
        'mailing_id' => $mailingObject->id,
        'api.Email.getvalue' => array(
          'id' => '$value.email_id',
          'return' => 'email',
        ),
      ));
      $this->assertEquals(1, $mailingRecipients['count'], 'Check recipient count');
      $this->assertEquals('abc@test.com', $mailingRecipients['values'][$mailingRecipients['id']]['api.Email.getvalue'], 'Check if recipient email belong to deceased contact');
    }
  }

}

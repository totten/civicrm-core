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

/**
 * When an activity is created in a case, the "case_activity" email is sent.
 * Generally, the email is sent to the assignee, although (depending on
 * the configuration/add-ons) additional copies may be sent.
 *
 * @see CRM_Case_BAO_Case::sendActivityCopy
 */
class CRM_Case_WorkflowMessage_CaseActivity extends Civi\WorkflowMessage\WorkflowMessage {

  const GROUP = 'msg_tpl_workflow_case';
  const WORKFLOW = 'case_activity';

  /**
   * Recipient's contact ID.
   *
   * @var int
   * @scope tokenContext
   * @required
   */
  public $contactId;

  /**
   * The recipient.
   *
   * Example: ['contact_id' => 123, 'display_name' => 'Bob Roberts', role => 'FIXME']
   *
   * @var array
   * @scope tpl
   * @required
   */
  public $contact;

  /**
   * @var int
   * @scope tpl as client_id
   * @required
   */
  public $clientId;

  /**
   * @var string
   * @scope tpl
   * @required
   */
  public $activitySubject;

  /**
   * @var string
   * @scope tpl
   * @required
   */
  public $activityTypeName;

  /**
   * Unique ID for this activity. Unique and difficult to guess.
   *
   * @var string
   * @scope tpl
   * @required
   */
  public $idHash;

  /**
   * @var bool
   * @scope tpl
   * @required
   */
  public $isCaseActivity;

  /**
   * @var string
   * @scope tpl
   */
  public $editActURL;

  /**
   * @var string
   * @scope tpl
   */
  public $viewActURL;

  /**
   * @var string
   * @scope tpl
   */
  public $manageCaseURL;

  /**
   * List of conventional activity fields.
   *
   * Example: [['label' => ..., 'category' => ..., 'type' => ..., 'value' => ...]]
   *
   * @var array
   * @scope tpl as activity.fields
   * @required
   */
  public $activityFields;

  /**
   * List of custom activity fields, grouped by CustomGroup.
   *
   * Example: ['My Custom Stuff' => [['label' => ..., 'category' => ..., 'type' => ..., 'value' => ...]]]
   *
   * @var array
   * @scope tpl as activity.customGroups
   * @required
   */
  public $activityCustomGroups;

}

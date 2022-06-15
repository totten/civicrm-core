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

use Civi\WorkflowMessage\GenericWorkflowMessage;

/**
 * Receipt sent when confirming a back office contribution.
 *
 * @support template-only
 *
 * @see CRM_Contribute_Form_AdditionalInfo::emailReceipt
 */
class CRM_Contribute_WorkflowMessage_ContributionOfflineReceipt extends GenericWorkflowMessage {
  use CRM_Contribute_WorkflowMessage_ContributionTrait;
  public const WORKFLOW = 'contribution_offline_receipt';

  protected function exportExtraTplParams(array &$export): void {
    // Need to figure out where these values come from. Take them as parameters? Read them from related entities?
    $fixmeValues = [
      'lineItem' => NULL,
    ];
    $export = array_merge($fixmeValues, $export);
  }

  protected function exportExtraTokenContext(array &$export): void {
    $export['smartyTokenAlias']['is_pay_later'] = 'contribution.is_pay_later';
    $export['smartyTokenAlias']['amount'] = 'contribution_recur.amount|crmMoney';
  }

}

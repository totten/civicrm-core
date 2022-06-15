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
 * Invoice generated when invoicing is enabled.
 *
 * @support template-only
 * @see CRM_Contribute_Form_Task_Invoice::printPDF
 */
class CRM_Contribute_WorkflowMessage_ContributionInvoiceReceipt extends GenericWorkflowMessage {

  use CRM_Contribute_WorkflowMessage_ContributionTrait;

  public const WORKFLOW = 'contribution_invoice_receipt';

  protected function exportExtraTplParams(array &$export): void {
    // Need to figure out where these values come from. Take them as parameters? Read them from related entities?
    $fixmeValues = [
      'street_address' => NULL,
      'supplemental_address_1' => NULL,
      'supplemental_address_2' => NULL,
      'stateProvinceAbbreviation' => NULL,
      'postal_code' => NULL,
      'city' => NULL,
      'country' => NULL,
      'invoice_date' => date("F j, Y"),
      'lineItem' => NULL,
      'subTotal' => NULL,
      'amountPaid' => NULL,
      'amountDue' => NULL,
      'refundedStatusId' => NULL,
      'pendingStatusId' => NULL,
      'cancelledStatusId' => NULL,
      'title' => NULL,
    ];
    $export = array_merge($fixmeValues, $export);
  }

  /**
   * Specify any tokens that should be exported as smarty variables.
   *
   * @todo it might be that this should be moved to the trait as we
   * we work through these.
   *
   * @param array $export
   */
  protected function exportExtraTokenContext(array &$export): void {
    $export['smartyTokenAlias']['currency'] = 'contribution.currency';
    $export['smartyTokenAlias']['invoice_number'] = 'contribution.invoice_number';
    $export['smartyTokenAlias']['amount'] = 'contribution.total_amount';
    $export['smartyTokenAlias']['contribution_status_id'] = 'contribution.contribution_status_id';
  }

}

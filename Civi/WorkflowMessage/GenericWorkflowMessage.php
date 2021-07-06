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

namespace Civi\WorkflowMessage;

/**
 * Generic base-class for describing the inputs for a workflow email template.
 *
 * @method $this setContactId(int|null $contactId)
 * @method int|null getContactId()
 */
class GenericWorkflowMessage extends WorkflowMessage {

  /**
   * The contact receiving this message.
   *
   * @var int
   * @scope tokenContext, envelope
   */
  protected $contactId;

}

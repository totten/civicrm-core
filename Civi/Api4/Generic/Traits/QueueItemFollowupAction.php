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

namespace Civi\Api4\Generic\Traits;

use CRM_Utils_Array;

/**
 * Helper for defining a queue-related action.
 *
 * Like `AbstractBatchAction`, `QueueItemFollowupAction` is a helper for defining another action.
 * In contrast to `AbstractBatchAction`, it is decidedly singular - it deals with exactly
 * one queue at a time.
 *
 * @method $this setItem(array $item)
 * @method array getItem()
 */
trait QueueItemFollowupAction {

  /**
   * Previously claimed item - which should now be released.
   *
   * @var array
   *   Fields: {id: scalar, queue: string}
   * @required
   */
  protected $item;

  /**
   * @return \CRM_Queue_Queue
   * @throws \API_Exception
   */
  protected function loadQueue(): \CRM_Queue_Queue {
    if (empty($this->item['queue'])) {
      throw new \API_Exception("Queue item requires property 'queue'.");
    }
    $queue = \Civi::queue($this->item['queue']);
    return $queue;
  }

  /**
   * @return object
   * @throws \API_Exception
   */
  protected function formatItem(): object {
    if (empty($this->item['id'])) {
      throw new \API_Exception("Queue item requires property 'id'.");
    }
    $item = (object) CRM_Utils_Array::subset($this->item, ['id', 'queue']);
    return $item;
  }

}

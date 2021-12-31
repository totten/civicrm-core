<?php

namespace Civi\Api4\Action\Queue;

use Civi\Api4\Generic\Traits\QueueItemFollowupAction;

/**
 * Mark a previously claimed item as completed. Remove it.
 */
class DeleteItem extends \Civi\Api4\Generic\AbstractAction {

  use QueueItemFollowupAction;

  public function _run(\Civi\Api4\Generic\Result $result) {
    $queue = $this->loadQueue();
    $item = $this->formatItem();
    $queue->deleteItem($item);
  }

}

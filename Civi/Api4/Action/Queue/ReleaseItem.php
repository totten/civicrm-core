<?php

namespace Civi\Api4\Action\Queue;

use Civi\Api4\Generic\Traits\QueueItemFollowupAction;

/**
 * Abort work on a claimed item. Releases the item for another attempt.
 */
class ReleaseItem extends \Civi\Api4\Generic\AbstractAction {

  use QueueItemFollowupAction;

  public function _run(\Civi\Api4\Generic\Result $result) {
    $queue = $this->loadQueue();
    $item = $this->formatItem();
    $queue->releaseItem($item);
  }

}

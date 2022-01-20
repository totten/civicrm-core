<?php

namespace Civi\Api4\Action\Queue;

use Civi\Api4\Generic\Traits\QueueItemFollowupAction;

/**
 * Run the item.
 *
 * Note: We don't let callers choose arbitrary functions to run. We only run something if it's
 * legitimately in the queue. This requires re-reading the queue item for authenticity.
 *
 * If successful, this returns a copy of the executed task.
 * If the task is unsuccessful, it raises an exception (returning API-style error).
 */
class RunItem extends \Civi\Api4\Generic\AbstractAction {

  use QueueItemFollowupAction;

  /**
   * After executing the task, should we automatically
   * @var bool
   */
  public $autoMode = TRUE;

  public function _run(\Civi\Api4\Generic\Result $result) {
    $queue = $this->loadQueue();
    $lightItem = $this->formatItem();
    $item = $queue->fetchItem($lightItem->id);
    $outcome = (new \CRM_Queue_Autorunner())->run($queue, $item);
    $result[] = ['outcome' => $outcome, 'item' => $lightItem];
  }

}

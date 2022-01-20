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
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */

namespace api\v4\Entity;

use Civi\Api4\Queue;
use api\v4\UnitTestCase;

/**
 * @group headless
 */
class QueueTest extends UnitTestCase {

  public function testBasicLinearPolling() {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_linear';
    $queue = \Civi::queue($queueName, ['type' => 'Sql']);
    $this->assertEquals(0, $queue->numberOfItems());

    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['first']
    ));
    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['second']
    ));

    // Get item #1. Run it. Finish it.
    $first = Queue::claimItem()->setQueue($queueName)->execute()->single();
    $this->assertEquals([QueueTest::class, 'doSomething'], $first['data']['callback']);
    $this->assertEquals(['first'], $first['data']['arguments']);
    Queue::runItem(0)->setItem($first)->execute();
    $this->assertEquals(['first'], \Civi::$statics[__CLASS__]['doSomething']);
    Queue::deleteItem(0)->setItem($first)->execute();

    // Get item #2. Change our minds. Give it back.
    $second = Queue::claimItem()->setQueue($queueName)->execute()->single();
    $this->assertEquals([QueueTest::class, 'doSomething'], $second['data']['callback']);
    $this->assertEquals(['second'], $second['data']['arguments']);
    Queue::releaseItem(0)->setItem($second)->execute();

    // Get item #2. Run it. Finish it.
    $retrySecond = Queue::claimItem()->setQueue($queueName)->execute()->single();
    $this->assertEquals([QueueTest::class, 'doSomething'], $retrySecond['data']['callback']);
    $this->assertEquals(['second'], $retrySecond['data']['arguments']);
    Queue::runItem(0)->setItem($retrySecond)->execute();
    $this->assertEquals(['first', 'second'], \Civi::$statics[__CLASS__]['doSomething']);
    Queue::deleteItem(0)->setItem($retrySecond)->execute();
  }

  public function testBasicParallelPolling() {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_parallel';
    $queue = \Civi::queue($queueName, ['type' => 'SqlParallel']);
    $this->assertEquals(0, $queue->numberOfItems());

    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['first']
    ));
    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['second']
    ));

    $first = Queue::claimItem()->setQueue($queueName)->execute()->single();
    $second = Queue::claimItem()->setQueue($queueName)->execute()->single();

    // Just for fun, let's run these tasks in opposite order.

    $this->assertEquals([QueueTest::class, 'doSomething'], $second['data']['callback']);
    $this->assertEquals(['second'], $second['data']['arguments']);
    Queue::runItem(0)->setItem($second)->execute();
    $this->assertEquals(['second'], \Civi::$statics[__CLASS__]['doSomething']);

    $this->assertEquals([QueueTest::class, 'doSomething'], $first['data']['callback']);
    $this->assertEquals(['first'], $first['data']['arguments']);
    Queue::runItem(0)->setItem($first)->execute();
    $this->assertEquals(['second', 'first'], \Civi::$statics[__CLASS__]['doSomething']);

    Queue::deleteItem(0)->setItem($first)->execute();
    Queue::deleteItem(0)->setItem($second)->execute();
  }

  public function testEmptiness() {
    $queueName = 'QueueTest_' . md5(random_bytes(32)) . '_linear';
    $queue = \Civi::queue($queueName, ['type' => 'Sql']);
    $this->assertEquals(0, $queue->numberOfItems());

    $startResult = Queue::claimItem()->setQueue($queueName)->execute();
    $this->assertEquals(0, $startResult->count());

    \Civi::queue($queueName)->createItem(new \CRM_Queue_Task(
      [QueueTest::class, 'doSomething'],
      ['wa wa wa']
    ));
    $first = Queue::claimItem()->setQueue($queueName)->execute()->single();
    $this->assertEquals([QueueTest::class, 'doSomething'], $first['data']['callback']);
    $this->assertEquals(['wa wa wa'], $first['data']['arguments']);
    Queue::deleteItem(0)->setItem($first)->execute();

    $endResult = Queue::claimItem()->setQueue($queueName)->execute();
    $this->assertEquals(0, $endResult->count());
  }

  public static function doSomething(\CRM_Queue_TaskContext $ctx, string $something) {
    \Civi::$statics[__CLASS__]['doSomething'][] = $something;
    return TRUE;
  }

}

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
 * Ensure that the `Civi::queue()` facade works.
 *
 * @group headless
 */
class CRM_Queue_FacadeTest extends CiviUnitTestCase {

  public function setUp(): void {
    parent::setUp();
  }

  public function tearDown(): void {
    $tablesToTruncate = ['civicrm_queue_item', 'civicrm_queue'];
    $this->quickCleanup($tablesToTruncate);
    parent::tearDown();
  }

  public function testCreate() {
    $this->assertQueueStats([]);

    $apple = Civi::queue('autorun:apple');
    $this->assertInstanceOf(CRM_Queue_Queue_SqlParallel::class, $apple);
    $this->assertDBQuery(1, 'SELECT is_autorun FROM civicrm_queue WHERE name = "autorun:apple"');
    // $this->assertCount(1, Civi\Api4\Queue::get(0)->addWhere('name', 'LIKE', 'apple%')->execute());
    $apple->createItem(new CRM_Queue_Task([__CLASS__, 'trueFunc'], []));
    $this->assertQueueStats(['autorun:apple' => 1]);

    $banana = Civi::queue('/ajax/banana');
    $this->assertInstanceOf(CRM_Queue_Queue_Sql::class, $banana);
    $this->assertDBQuery(0, 'SELECT count(*) FROM civicrm_queue WHERE name LIKE "banana%"');
    // $this->assertCount(0, Civi\Api4\Queue::get(0)->addWhere('name', 'LIKE', 'banana%')->execute());
    $banana->createItem(new CRM_Queue_Task([__CLASS__, 'trueFunc'], []));
    $banana->createItem(new CRM_Queue_Task([__CLASS__, 'trueFunc'], []));
    $this->assertQueueStats(['autorun:apple' => 1, '/ajax/banana' => 2]);

    $apple2 = Civi::queue('autorun:apple');
    $this->assertEquals($apple, $apple2);
    $apple2->createItem(new CRM_Queue_Task([__CLASS__, 'trueFunc'], []));
    $this->assertQueueStats(['autorun:apple' => 2, '/ajax/banana' => 2]);

    // $queue = Civi::queue('autorun+sync/cherry');
    // $this->assertInstanceOf(CRM_Queue_Queue_Sql::class, $queue);
    // $this->assertDBQuery(1, 'SELECT is_autorun FROM civicrm_queue WHERE name LIKE "cherry%"');
    // $this->assertCount(0, Civi\Api4\Queue::get(1)->addWhere('name', 'LIKE', 'cherry%')->execute());
  }

  protected function assertQueueStats(array $expectedStats) {
    $actualStats = CRM_Core_DAO::executeQuery('SELECT queue_name, count(*) cnt FROM civicrm_queue_item GROUP BY queue_name')
      ->fetchMap('queue_name', 'cnt');
    ksort($actualStats);
    ksort($expectedStats);
    $this->assertEquals($expectedStats, $actualStats);
  }

  protected static function trueFunc() {
    return TRUE;
  }

}

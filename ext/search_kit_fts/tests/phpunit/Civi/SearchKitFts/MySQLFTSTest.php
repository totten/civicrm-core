<?php

namespace Civi\SearchKitFts;

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class MySQLFTSTest extends TestCase implements HeadlessInterface, HookInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testMySQLFTSLifecycle(): void {
    $searchDisplay = [
      'name' => 'TestContactQuickSearch',
      'settings' => [
        'columns' => [
          ['key' => 'contact_id', 'spec' => ['name' => 'contact_id', 'data_type' => 'Integer']],
          ['key' => 'display_name', 'spec' => ['name' => 'display_name', 'data_type' => 'String']],
          ['key' => 'primary_email', 'spec' => ['name' => 'primary_email', 'data_type' => 'String']],
        ],
      ],
    ];
    $savedSearch = ['name' => 'TestContactSearch', 'api_entity' => 'Contact', 'api_params' => []];
    $connection = ['name' => 'mysql', 'label' => 'MySQL', 'backend' => MySQLFTS::class];

    $fts = new MySQLFTS($connection, $savedSearch, $searchDisplay);

    // 1. Check getDriver returns PDO
    $this->assertInstanceOf(\PDO::class, $fts->getDriver());

    // 2. Initialize storage table
    $fts->initialize();
    $tableName = $fts->getTableName();
    $this->assertTrue(\CRM_Core_DAO::checkTableExists($tableName));

    // 3. Insert records
    $records = [
      ['contact_id' => 101, 'display_name' => 'Bob Builder', 'primary_email' => 'bob@example.com'],
      ['contact_id' => 102, 'display_name' => 'Alice Cooper', 'primary_email' => 'alice@example.org'],
    ];
    $converted = $fts->convertRecords($records);
    $this->assertArrayNotHasKey('fts', $converted[0]);
    $fts->insertRecords($converted);

    // 4. Query via MySQLGetAction
    $getAction = $fts->createApi4Action('get');
    $this->assertInstanceOf(MySQLGetAction::class, $getAction);

    // Search globally on fts column using CONTAINS (MATCH AGAINST)
    $getAction->setCheckPermissions(FALSE);
    $getAction->setWhere([['fts', 'CONTAINS', 'Builder']]);
    $results = $getAction->execute();
    $this->assertCount(1, $results);
    $this->assertEquals('Bob Builder', $results[0]['display_name']);

    // Targeted field search with =
    $getAction2 = $fts->createApi4Action('get');
    $getAction2->setCheckPermissions(FALSE);
    $getAction2->setWhere([['primary_email', '=', 'alice@example.org']]);
    $results2 = $getAction2->execute();
    $this->assertCount(1, $results2);
    $this->assertEquals(102, $results2[0]['contact_id']);

    // Check error thrown when = or != used on fts field
    try {
      $getActionErr = $fts->createApi4Action('get');
      $getActionErr->setCheckPermissions(FALSE);
      $getActionErr->setWhere([['fts', '=', 'Bob']]);
      $getActionErr->execute();
      $this->fail('Expected CRM_Core_Exception when using = on fts field');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertStringContainsString("Operator '=' is not supported for 'fts' field", $e->getMessage());
    }

    // 5. Truncate & Destroy
    $fts->truncate();
    $getAction3 = $fts->createApi4Action('get');
    $getAction3->setCheckPermissions(FALSE);
    $this->assertCount(0, $getAction3->execute());

    $fts->destroy();
    $this->assertFalse(\CRM_Core_DAO::checkTableExists($tableName));
  }

}

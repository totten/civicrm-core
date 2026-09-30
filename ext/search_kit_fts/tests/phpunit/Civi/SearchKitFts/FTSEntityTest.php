<?php

namespace Civi\SearchKitFts;

use Civi\Api4\Entity;
use Civi\Api4\SavedSearch;
use Civi\Api4\SearchDisplay;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class FTSEntityTest extends TestCase implements HeadlessInterface, HookInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testFTSEntityRegistrationAndQuery(): void {
    $displayName = 'TestContactFtsEntity';
    $searchName = 'TestContactSearchForEntity';

    // Delete pre-existing test display/search if present
    SearchDisplay::delete(FALSE)->addWhere('name', '=', $displayName)->execute();
    SavedSearch::delete(FALSE)->addWhere('name', '=', $searchName)->execute();

    // 1. Create SavedSearch
    $savedSearch = SavedSearch::create(FALSE)
      ->addValue('name', $searchName)
      ->addValue('title', 'Test Contact Search')
      ->addValue('api_entity', 'Contact')
      ->addValue('api_params', [])
      ->execute()
      ->first();

    // 2. Create SearchDisplay of type fts_mysql
    $searchDisplay = SearchDisplay::create(FALSE)
      ->addValue('name', $displayName)
      ->addValue('label', 'Test Contact FTS Entity')
      ->addValue('saved_search_id', $savedSearch['id'])
      ->addValue('type', 'fts_mysql')
      ->addValue('settings', [
        'columns' => [
          ['key' => 'id', 'spec' => ['name' => 'id', 'label' => 'ID', 'data_type' => 'Integer']],
          ['key' => 'display_name', 'spec' => ['name' => 'display_name', 'label' => 'Display Name', 'data_type' => 'String']],
        ],
      ])
      ->execute()
      ->first();

    $this->assertNotEmpty($searchDisplay['id']);

    // Clear APIv4 entity metadata cache
    \Civi::cache('metadata')->clear();
    \CRM_Core_DAO_AllCoreTables::flush();

    // 3. Verify APIv4 Entity list includes FTS_TestContactFtsEntity
    $entities = Entity::get(FALSE)
      ->addWhere('name', '=', 'FTS_' . $displayName)
      ->execute();

    $this->assertCount(1, $entities);
    $this->assertEquals('FTS_' . $displayName, $entities[0]['name']);

    // 4. Verify getFields
    $getFieldsAction = FTSEntity::getFields($displayName, FALSE);
    $fields = $getFieldsAction->execute();
    $fieldNames = $fields->column('name');
    $this->assertContains('fts', $fieldNames);
    $this->assertContains('display_name', $fieldNames);

    // 5. Populate MySQL FTS backend table with test records
    $connection = ['name' => 'mysql', 'label' => 'MySQL', 'backend' => MySQLFTS::class];
    $fts = new MySQLFTS($connection, $savedSearch, $searchDisplay);
    $fts->initialize();
    $fts->insertRecords([
      ['id' => 301, 'display_name' => 'Edward Elgar'],
      ['id' => 302, 'display_name' => 'Franz Liszt'],
    ]);

    // 6. Query APIv4 entity via FTSEntity::get
    $getAction = FTSEntity::get($displayName, FALSE);
    $getAction->setWhere([['fts', 'CONTAINS', 'Elgar']]);
    $results = $getAction->execute();

    $this->assertCount(1, $results);
    $this->assertEquals('Edward Elgar', $results[0]['display_name']);

    // Cleanup
    $fts->destroy();
    SearchDisplay::delete(FALSE)->addWhere('id', '=', $searchDisplay['id'])->execute();
    SavedSearch::delete(FALSE)->addWhere('id', '=', $savedSearch['id'])->execute();
  }

}

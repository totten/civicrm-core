<?php

namespace Civi\SearchKitFts;

use Civi\SearchKitFts\Exception\NoConnectionException;
use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class SolrFTSTest extends TestCase implements HeadlessInterface, HookInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testIndexNameResolution(): void {
    $searchDisplay = [
      'id' => 88,
      'name' => 'MyCustomSearch',
      'settings' => [],
    ];
    $savedSearch = ['name' => 'TestSearch'];
    $connection = ['name' => 'solr', 'label' => 'Solr', 'backend' => SolrFTS::class];

    $fts = new SolrFTS($connection, $savedSearch, $searchDisplay);

    \Civi::settings()->set('fts_solr_index', 'custom_[search_display.name]_[search_display.id]');
    $this->assertEquals('custom_mycustomsearch_88', $fts->getIndex());

    \Civi::settings()->set('fts_solr_index', 'literal_collection_1');
    $this->assertEquals('literal_collection_1', $fts->getIndex());
  }

  public function testSolrFTSLifecycle(): void {
    $solrUrl = \Civi::settings()->get('fts_solr_url') ?: 'http://localhost:8983';
    \Civi::settings()->set('fts_solr_url', $solrUrl);

    $searchDisplay = [
      'id' => 42,
      'name' => 'TestSolrQuickSearch',
      'settings' => [
        'columns' => [
          ['key' => 'contact_id', 'spec' => ['name' => 'contact_id', 'data_type' => 'Integer']],
          ['key' => 'display_name', 'spec' => ['name' => 'display_name', 'data_type' => 'String']],
          ['key' => 'primary_email', 'spec' => ['name' => 'primary_email', 'data_type' => 'String']],
        ],
      ],
    ];
    $savedSearch = ['name' => 'TestContactSearch', 'api_entity' => 'Contact', 'api_params' => []];
    $connection = ['name' => 'solr', 'label' => 'Solr', 'backend' => SolrFTS::class];

    $fts = new SolrFTS($connection, $savedSearch, $searchDisplay);

    // 1. Initialize
    try {
      $fts->initialize();
    }
    catch (NoConnectionException $e) {
      $this->markTestSkipped('Solr server not available: ' . $e->getMessage());
      return;
    }

    // 2. Clear any leftover test data
    $fts->truncate();

    // 3. Convert and insert records
    $records = [
      ['contact_id' => 201, 'display_name' => 'Charlie Chaplin', 'primary_email' => 'charlie@example.org'],
      ['contact_id' => 202, 'display_name' => 'Daisy Duck', 'primary_email' => 'daisy@example.net'],
    ];
    $converted = $fts->convertRecords($records);
    $this->assertEquals('TestSolrQuickSearch', $converted[0]['searchDisplayName']);
    $this->assertEquals('TestSolrQuickSearch:201', $converted[0]['id']);
    $this->assertEquals('Charlie Chaplin', $converted[0]['TestSolrQuickSearch_display_name']);
    $fts->insertRecords($converted);

    // 4. Query via SolrGetAction
    $getAction = $fts->createApi4Action('get');
    $this->assertInstanceOf(SolrGetAction::class, $getAction);

    // Global fts search
    $getAction->setCheckPermissions(FALSE);
    $getAction->setWhere([['fts', 'CONTAINS', 'Charlie']]);
    $results = $getAction->execute();
    $this->assertCount(1, $results);
    $this->assertEquals('201', $results[0]['id']);
    $this->assertEquals('Charlie Chaplin', $results[0]['display_name']);

    // Field-specific search with =
    $getAction2 = $fts->createApi4Action('get');
    $getAction2->setCheckPermissions(FALSE);
    $getAction2->setWhere([['primary_email', '=', 'daisy@example.net']]);
    $results2 = $getAction2->execute();
    $this->assertCount(1, $results2);
    $this->assertEquals('Daisy Duck', $results2[0]['display_name']);

    // Check error thrown when = or != used on fts field
    try {
      $getActionErr = $fts->createApi4Action('get');
      $getActionErr->setCheckPermissions(FALSE);
      $getActionErr->setWhere([['fts', '=', 'Charlie']]);
      $getActionErr->execute();
      $this->fail('Expected CRM_Core_Exception when using = on fts field');
    }
    catch (\CRM_Core_Exception $e) {
      $this->assertStringContainsString("Operator '=' is not supported for 'fts' field", $e->getMessage());
    }

    // 5. Truncate & verify
    $fts->truncate();
    $getAction3 = $fts->createApi4Action('get');
    $getAction3->setCheckPermissions(FALSE);
    $this->assertCount(0, $getAction3->execute());
  }

}

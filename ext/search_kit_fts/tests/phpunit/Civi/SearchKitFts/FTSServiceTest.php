<?php

namespace Civi\SearchKitFts;

use Civi\Test\HeadlessInterface;
use Civi\Test\HookInterface;
use Civi\Test\TransactionalInterface;
use PHPUnit\Framework\TestCase;

/**
 * @group headless
 */
class FTSServiceTest extends TestCase implements HeadlessInterface, HookInterface {

  public function setUpHeadless() {
    return \Civi\Test::headless()
      ->installMe(__DIR__)
      ->apply();
  }

  public function testServiceRegistered(): void {
    $fts = \Civi::service('fts');
    $this->assertInstanceOf(FTS::class, $fts);
  }

  public function testGetConnections(): void {
    /** @var FTS $fts */
    $fts = \Civi::service('fts');
    $connections = $fts->getConnections();
    $names = array_column($connections, 'name');

    $this->assertContains('mysql', $names);
    $this->assertContains('solr', $names);
    $this->assertContains('typesense', $names);
    $this->assertContains('elastic', $names);
  }

  public function testPickConnectionFallback(): void {
    /** @var FTS $fts */
    $fts = \Civi::service('fts');

    \Civi::settings()->set('fts_solr_url', NULL);
    $conn = $fts->pickConnection(['solr', 'mysql']);
    $this->assertNotNull($conn);
    $this->assertEquals('mysql', $conn['name']);

    \Civi::settings()->set('fts_solr_url', 'http://localhost:8983/');
    $conn = $fts->pickConnection(['solr', 'mysql']);
    $this->assertNotNull($conn);
    $this->assertEquals('solr', $conn['name']);
  }

}

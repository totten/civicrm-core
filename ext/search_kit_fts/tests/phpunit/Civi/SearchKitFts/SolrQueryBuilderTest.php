<?php

namespace Civi\SearchKitFts;

use PHPUnit\Framework\TestCase;

class SolrQueryBuilderTest extends TestCase {

  public function testBuildQueryEmpty(): void {
    $q = SolrQueryBuilder::buildQuery([]);
    $this->assertEquals('*:*', $q);
  }

  public function testBuildQueryFtsContains(): void {
    $where = [
      ['fts', 'CONTAINS', 'Mozart'],
    ];
    $q = SolrQueryBuilder::buildQuery($where);
    $this->assertEquals('fts:*Mozart*', $q);
  }

  public function testBuildQueryFtsEqualsThrowsException(): void {
    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage("Operator '=' is not supported for 'fts' field");
    SolrQueryBuilder::buildQuery([['fts', '=', 'Mozart']]);
  }

  public function testBuildQueryFtsNotEqualsThrowsException(): void {
    $this->expectException(\CRM_Core_Exception::class);
    $this->expectExceptionMessage("Operator '!=' is not supported for 'fts' field");
    SolrQueryBuilder::buildQuery([['fts', '!=', 'Mozart']]);
  }

  public function testBuildQueryFieldOperators(): void {
    $where = [
      ['display_name', 'CONTAINS', 'Wolfgang'],
      ['email', '=', 'wolfgang@example.org'],
      ['status', '!=', 'archived'],
    ];
    $q = SolrQueryBuilder::buildQuery($where);
    $expected = 'display_name:*Wolfgang* AND email:"wolfgang@example.org" AND *:* AND -status:"archived"';
    $this->assertEquals($expected, $q);
  }

  public function testBuildQueryEscaping(): void {
    $where = [
      ['title', '=', 'Sonata No. 11 (K. 331) / Rondo'],
    ];
    $q = SolrQueryBuilder::buildQuery($where);
    $this->assertEquals('title:"Sonata No. 11 \(K. 331\) \/ Rondo"', $q);
  }

  public function testBuildQueryParams(): void {
    $where = [
      ['fts', 'CONTAINS', 'Bach'],
    ];
    $params = SolrQueryBuilder::buildQueryParams($where, 50, 10);
    $this->assertEquals([
      'q' => 'fts:*Bach*',
      'rows' => 50,
      'start' => 10,
      'wt' => 'json',
    ], $params);
  }

}

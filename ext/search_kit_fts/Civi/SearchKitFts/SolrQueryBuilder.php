<?php

namespace Civi\SearchKitFts;

/**
 * Stateless query builder for constructing Apache Solr queries from APIv4 parameters.
 */
class SolrQueryBuilder {

  /**
   * Build Solr query string 'q' from APIv4 WHERE clauses.
   *
   * @param array $where
   * @param string $searchDisplayName
   * @return string
   * @throws \CRM_Core_Exception
   */
  public static function buildQuery(array $where, string $searchDisplayName = ''): string {
    $queryParts = [];

    if ($searchDisplayName !== '') {
      $escapedName = addcslashes($searchDisplayName, '"+-&|!(){}[]^~*?:\\/');
      $queryParts[] = "searchDisplayName:\"{$escapedName}\"";
    }

    foreach ($where as $clause) {
      if (!is_array($clause) || count($clause) < 3) {
        continue;
      }
      [$field, $op, $val] = $clause;

      if ($field === 'fts') {
        $solrField = $searchDisplayName ? "{$searchDisplayName}_fts" : 'fts';
        if ($op === 'CONTAINS' || $op === 'LIKE') {
          $valClean = trim((string) $val, '* ');
          $queryParts[] = "{$solrField}:*$valClean*";
        }
        elseif ($op === '=' || $op === '!=') {
          throw new \CRM_Core_Exception("Operator '$op' is not supported for 'fts' field in SolrFTS. Use 'CONTAINS' instead.");
        }
        else {
          throw new \CRM_Core_Exception("Unsupported operator '$op' for 'fts' field in SolrFTS");
        }
        continue;
      }

      $fieldClean = preg_replace('/[^a-zA-Z0-9_]/', '', $field);
      if ($fieldClean === 'id') {
        $solrField = 'id';
      }
      else {
        $solrField = ($searchDisplayName && !str_starts_with($fieldClean, "{$searchDisplayName}_")) ? "{$searchDisplayName}_{$fieldClean}" : $fieldClean;
      }

      if ($op === 'CONTAINS' || $op === 'LIKE') {
        $valClean = trim((string) $val, '* ');
        $queryParts[] = "{$solrField}:*$valClean*";
      }
      elseif ($op === '=') {
        $valClean = addcslashes((string) $val, '"+-&|!(){}[]^~*?:\\/');
        if ($fieldClean === 'id' && $searchDisplayName && !str_contains($valClean, ':')) {
          $valClean = "{$searchDisplayName}:{$valClean}";
        }
        $queryParts[] = "{$solrField}:\"$valClean\"";
      }
      elseif ($op === '!=') {
        $valClean = addcslashes((string) $val, '"+-&|!(){}[]^~*?:\\/');
        if ($fieldClean === 'id' && $searchDisplayName && !str_contains($valClean, ':')) {
          $valClean = "{$searchDisplayName}:{$valClean}";
        }
        $queryParts[] = "*:* AND -{$solrField}:\"$valClean\"";
      }
    }

    return $queryParts ? implode(' AND ', $queryParts) : '*:*';
  }

  /**
   * Build array of HTTP GET query parameters for Solr select endpoint.
   *
   * @param array $where
   * @param int|null $limit
   * @param int|null $offset
   * @param string $searchDisplayName
   * @return array{q: string, rows: int, start: int, wt: string}
   */
  public static function buildQueryParams(array $where, ?int $limit = 100, ?int $offset = 0, string $searchDisplayName = ''): array {
    return [
      'q' => self::buildQuery($where, $searchDisplayName),
      'rows' => $limit ?: 100,
      'start' => $offset ?: 0,
      'wt' => 'json',
    ];
  }

}

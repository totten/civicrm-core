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
   * @return string
   * @throws \CRM_Core_Exception
   */
  public static function buildQuery(array $where): string {
    $queryParts = [];

    foreach ($where as $clause) {
      if (!is_array($clause) || count($clause) < 3) {
        continue;
      }
      [$field, $op, $val] = $clause;

      if ($field === 'fts') {
        if ($op === 'CONTAINS' || $op === 'LIKE') {
          $valClean = trim((string) $val, '* ');
          $queryParts[] = "fts:*$valClean*";
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

      if ($op === 'CONTAINS' || $op === 'LIKE') {
        $valClean = trim((string) $val, '* ');
        $queryParts[] = "$fieldClean:*$valClean*";
      }
      elseif ($op === '=') {
        $valClean = addcslashes((string) $val, '"+-&|!(){}[]^~*?:\\/');
        $queryParts[] = "$fieldClean:\"$valClean\"";
      }
      elseif ($op === '!=') {
        $valClean = addcslashes((string) $val, '"+-&|!(){}[]^~*?:\\/');
        $queryParts[] = "*:* AND -$fieldClean:\"$valClean\"";
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
   * @return array{q: string, rows: int, start: int, wt: string}
   */
  public static function buildQueryParams(array $where, ?int $limit = 100, ?int $offset = 0): array {
    return [
      'q' => self::buildQuery($where),
      'rows' => $limit ?: 100,
      'start' => $offset ?: 0,
      'wt' => 'json',
    ];
  }

}

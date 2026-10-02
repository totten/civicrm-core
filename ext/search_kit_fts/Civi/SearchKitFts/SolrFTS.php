<?php

namespace Civi\SearchKitFts;

use Civi\Api4\Generic\AbstractAction;
use Civi\Search\Meta;
use Civi\SearchKitFts\Exception\NoConnectionException;
use GuzzleHttp\Client;

class SolrFTS extends AbstractFTS {

  protected ?Client $httpClient = NULL;

  protected ?string $index = NULL;

  /**
   * Get pre-configured Guzzle 7 HTTP client for Solr instance root URL.
   *
   * @return \GuzzleHttp\Client
   * @throws \Civi\SearchKitFts\Exception\NoConnectionException
   */
  public function http(): Client {
    if ($this->httpClient === NULL) {
      $url = \Civi::settings()->get('fts_solr_url');
      if (empty($url)) {
        throw new NoConnectionException("fts_solr_url setting is not configured.");
      }
      $baseUrl = rtrim($url, '/') . '/';
      $this->httpClient = new Client([
        'base_uri' => $baseUrl,
        'http_errors' => FALSE,
        'timeout' => 5,
      ]);
    }
    return $this->httpClient;
  }

  /**
   * Resolve the index/collection name from setting fts_solr_index.
   *
   * @return string
   */
  public function getIndex(): string {
    if ($this->index === NULL) {
      $pattern = \Civi::settings()->get('fts_solr_index');

      $variables = [];
      $variables['[search_display.id]'] = (string) ($this->searchDisplay['id'] ?? '');
      $variables['[search_display.name]'] = (string) ($this->searchDisplay['name'] ?? '');

      $parsedDsn = parse_url(CIVICRM_DSN);
      if (!empty($parsedDsn['path'])) {
        $variables['[mysql.db]'] = trim($parsedDsn['path'], '/');
      }

      $indexName = strtr($pattern, $variables);
      $cleanIndex = mb_strtolower(preg_replace('/[^a-zA-Z0-9_-]/', '', $indexName));
      $this->index = $cleanIndex ?: 'civicrm';
    }
    return $this->index;
  }

  /**
   * Return schema fields common to all search displays in the collection.
   *
   * @return array
   */
  public function createCommonSchema(): array {
    return [
      [
        'name' => 'searchDisplayName',
        'type' => 'string',
        'stored' => TRUE,
        'indexed' => TRUE,
      ],
    ];
  }

  /**
   * Return local schema fields needed by this specific search display.
   *
   * @return array
   */
  public function createLocalSchema(): array {
    $name = $this->searchDisplay['name'];
    $columns = $this->searchDisplay['settings']['columns'] ?? [];
    $fields = [
      [
        'name' => "{$name}_fts",
        'type' => 'text_general',
        'stored' => TRUE,
        'indexed' => TRUE,
        'multiValued' => FALSE,
      ],
    ];

    foreach ($columns as $col) {
      $key = Meta::createSqlName($col['key'])[0];
      $colName = $col['spec']['name'] ?? $key;
      if (!$colName) {
        continue;
      }
      $fields[] = [
        'name' => "{$name}_{$colName}",
        'type' => 'text_general',
        'stored' => TRUE,
        'indexed' => TRUE,
        'multiValued' => FALSE,
      ];
    }

    return $fields;
  }

  /**
   * Apply field definitions to the Solr index schema.
   *
   * @param array $schema
   * @param bool $overwrite
   * @return void
   */
  public function applySchema(array $schema, bool $overwrite = FALSE): void {
    $index = $this->getIndex();
    $schemaUrl = "solr/{$index}/schema";

    foreach ($schema as $field) {
      $action = $overwrite ? 'replace-field' : 'add-field';
      $res = $this->http()->post($schemaUrl, [
        'json' => [$action => $field],
      ]);

      if ($res->getStatusCode() >= 400) {
        $altAction = $overwrite ? 'add-field' : 'replace-field';
        $this->http()->post($schemaUrl, [
          'json' => [$altAction => $field],
        ]);
      }
    }
  }

  public function createApi4Action(string $action): AbstractAction {
    if ($action === 'get') {
      return new SolrGetAction($this->connection, $this->savedSearch, $this->searchDisplay, $this);
    }
    throw new \CRM_Core_Exception("Unsupported action '$action' for SolrFTS");
  }

  public function initialize(): void {
    $index = $this->getIndex();

    // Check if index/collection exists
    $res = $this->http()->get("solr/{$index}/select", [
      'query' => ['q' => '*:*', 'rows' => 0, 'wt' => 'json'],
    ]);

    if ($res->getStatusCode() !== 200) {
      // Auto-create Collection using SolrCloud APIs (based on _default configset)
      $createRes = $this->http()->get("solr/admin/collections", [
        'query' => [
          'action' => 'CREATE',
          'name' => $index,
          'collection.configName' => '_default',
          'numShards' => 1,
          'wt' => 'json',
        ],
      ]);

      if ($createRes->getStatusCode() !== 200) {
        throw new NoConnectionException("Unable to create Solr collection '$index'");
      }
    }

    // Apply global schema gently ($overwrite == FALSE)
    $this->applySchema($this->createCommonSchema(), FALSE);

    // Apply local schema forcefully ($overwrite == TRUE)
    $this->applySchema($this->createLocalSchema(), TRUE);
  }

  public function truncate(): void {
    $index = $this->getIndex();
    $name = $this->searchDisplay['name'];
    $path = "solr/{$index}/update";

    $this->http()->post($path, [
      'query' => ['commit' => 'true'],
      'json' => [
        'delete' => [
          'query' => "searchDisplayName:\"{$name}\"",
        ],
      ],
    ]);
  }

  public function destroy(): void {
    try {
      $this->truncate();
    }
    catch (\Exception $e) {
      // Ignore
    }
  }

  public function convertRecords(array $records): array {
    $converted = [];
    $columns = $this->searchDisplay['settings']['columns'] ?? [];
    $name = $this->searchDisplay['name'];

    foreach ($records as $index => $record) {
      $doc = [];
      $ftsParts = [];

      $rawId = (string) ($record['id'] ?? $record['contact_id'] ?? ($index + 1));
      $doc['id'] = "{$name}:{$rawId}";
      $doc['searchDisplayName'] = $name;

      foreach ($columns as $col) {
        $key = Meta::createSqlName($col['key'])[0];
        $colName = $col['spec']['name'] ?? $key;
        if (!$colName) {
          continue;
        }

        $val = $record[$key] ?? $record[$colName] ?? NULL;
        if (is_array($val)) {
          $val = implode(', ', $val);
        }
        $doc["{$name}_{$colName}"] = $val;

        if ($val !== NULL && $val !== '') {
          $ftsParts[] = (string) $val;
        }
      }

      $doc["{$name}_fts"] = implode(' ', $ftsParts);
      $converted[] = $doc;
    }

    return $converted;
  }

  public function insertRecords(array $records): void {
    if (empty($records)) {
      return;
    }

    $index = $this->getIndex();
    $path = "solr/{$index}/update";

    $this->http()->post($path, [
      'query' => ['commit' => 'true'],
      'json' => $records,
    ]);
  }

  public function getRecordsFromSolr(SolrGetAction $action): array {
    $index = $this->getIndex();
    $name = $this->searchDisplay['name'];

    $queryParams = SolrQueryBuilder::buildQueryParams(
      $action->getWhere(),
      $action->getLimit(),
      $action->getOffset(),
      $name
    );

    $path = "solr/{$index}/select";
    $response = $this->http()->get($path, [
      'query' => $queryParams,
    ]);

    $statusCode = $response->getStatusCode();
    $body = (string) $response->getBody();

    if ($statusCode !== 200) {
      throw new \CRM_Core_Exception("Solr query failed for path '$path' with HTTP code {$statusCode}: {$body}");
    }

    $data = json_decode($body, TRUE);
    $docs = $data['response']['docs'] ?? [];
    $prefix = "{$name}_";
    $idPrefix = "{$name}:";

    foreach ($docs as &$doc) {
      unset($doc['_version_'], $doc['_root_'], $doc['searchDisplayName']);

      if (isset($doc['id'])) {
        $rawId = is_array($doc['id']) ? $doc['id'][0] : $doc['id'];
        if (str_starts_with($rawId, $idPrefix)) {
          $rawId = substr($rawId, strlen($idPrefix));
        }
        $doc['id'] = $rawId;
      }

      foreach ($doc as $k => $v) {
        if ($k === 'id') {
          continue;
        }
        $val = (is_array($v) && count($v) === 1) ? $v[0] : $v;
        if (str_starts_with($k, $prefix)) {
          $cleanKey = substr($k, strlen($prefix));
          $doc[$cleanKey] = $val;
          unset($doc[$k]);
        }
        else {
          $doc[$k] = $val;
        }
      }
    }

    return $docs;
  }

}

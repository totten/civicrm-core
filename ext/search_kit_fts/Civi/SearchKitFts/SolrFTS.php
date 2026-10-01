<?php

namespace Civi\SearchKitFts;

use Civi\Api4\Generic\AbstractAction;
use Civi\SearchKitFts\Exception\NoConnectionException;
use GuzzleHttp\Client;

class SolrFTS extends AbstractFTS {

  protected ?Client $httpClient = NULL;
  protected ?string $activeCoreName = NULL;

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

  public function getCoreName(): string {
    $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $this->searchDisplay['name']));
    return 'fts_' . $cleanName;
  }

  /**
   * Get active core path or specific core path.
   *
   * @param string $path
   * @return string
   */
  public function getCorePath(string $path = ''): string {
    $core = $this->activeCoreName ?? $this->getCoreName();
    $corePath = "solr/{$core}";
    if ($path) {
      return $corePath . '/' . ltrim($path, '/');
    }
    return $corePath;
  }

  public function createApi4Action(string $action): AbstractAction {
    if ($action === 'get') {
      return new SolrGetAction($this->connection, $this->savedSearch, $this->searchDisplay, $this);
    }
    throw new \CRM_Core_Exception("Unsupported action '$action' for SolrFTS");
  }

  public function initialize(): void {
    $coreName = $this->getCoreName();

    try {
      $response = $this->http()->get("solr/{$coreName}/select", [
        'query' => ['q' => '*:*', 'rows' => 0, 'wt' => 'json'],
      ]);
      if ($response->getStatusCode() === 200) {
        $this->activeCoreName = $coreName;
        return;
      }
    }
    catch (\Exception $e) {
      // Core check failed, attempt creation
    }

    // Core is not healthy/ready, attempt to create via Solr Admin API
    try {
      $createRes = $this->http()->get("solr/admin/cores", [
        'query' => ['action' => 'CREATE', 'name' => $coreName, 'wt' => 'json'],
      ]);
      if ($createRes->getStatusCode() === 200) {
        $this->activeCoreName = $coreName;
        return;
      }
    }
    catch (\Exception $e) {
      // Admin core create failed
    }

    // Fallback to gettingstarted core if standalone server without default configsets
    try {
      $fallbackCheck = $this->http()->get("solr/gettingstarted/select", [
        'query' => ['q' => '*:*', 'rows' => 0, 'wt' => 'json'],
      ]);
      if ($fallbackCheck->getStatusCode() === 200) {
        $this->activeCoreName = 'gettingstarted';
        return;
      }
    }
    catch (\Exception $e) {
      // Fallback failed
    }

    throw new NoConnectionException("Unable to initialize Solr core '$coreName' and fallback failed.");
  }

  public function truncate(): void {
    $path = $this->getCorePath('update');
    $this->http()->post($path, [
      'query' => ['commit' => 'true'],
      'json' => ['delete' => ['query' => '*:*']],
    ]);
  }

  public function destroy(): void {
    try {
      $this->truncate();
    }
    catch (\Exception $e) {
      // Ignore
    }

    try {
      $this->http()->get("solr/admin/cores", [
        'query' => [
          'action' => 'UNLOAD',
          'core' => $this->getCoreName(),
          'deleteIndex' => 'true',
          'deleteDataDir' => 'true',
          'wt' => 'json',
        ],
      ]);
    }
    catch (\Exception $e) {
      // Ignore unload errors
    }
  }

  public function convertRecords(array $records): array {
    $converted = [];
    $columns = $this->searchDisplay['settings']['columns'] ?? [];

    foreach ($records as $index => $record) {
      $doc = [];
      $ftsParts = [];

      // Determine ID
      $doc['id'] = (string) ($record['id'] ?? $record['contact_id'] ?? ($index + 1));

      foreach ($columns as $col) {
        $key = $col['key'] ?? NULL;
        $colName = $col['spec']['name'] ?? $key;
        if (!$colName) {
          continue;
        }

        $val = $record[$key] ?? $record[$colName] ?? NULL;
        if (is_array($val)) {
          $val = implode(', ', $val);
        }
        $doc[$colName] = $val;

        if ($val !== NULL && $val !== '') {
          $ftsParts[] = (string) $val;
        }
      }

      $doc['fts'] = implode(' ', $ftsParts);
      $converted[] = $doc;
    }

    return $converted;
  }

  public function insertRecords(array $records): void {
    if (empty($records)) {
      return;
    }

    $path = $this->getCorePath('update');
    $this->http()->post($path, [
      'query' => ['commit' => 'true'],
      'json' => $records,
    ]);
  }

  public function getRecordsFromSolr(SolrGetAction $action): array {
    $queryParams = SolrQueryBuilder::buildQueryParams(
      $action->getWhere(),
      $action->getLimit(),
      $action->getOffset()
    );

    $path = $this->getCorePath('select');
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

    foreach ($docs as &$doc) {
      unset($doc['_version_'], $doc['_root_']);
      foreach ($doc as $k => $v) {
        if (is_array($v) && count($v) === 1) {
          $doc[$k] = $v[0];
        }
      }
    }

    return $docs;
  }

}

<?php

namespace Civi\SearchKitFts;

use Civi\Api4\Generic\AbstractAction;
use Civi\SearchKitFts\Exception\NoConnectionException;

class SolrFTS extends AbstractFTS {

  protected ?string $activeCoreUrl = NULL;

  public function getSolrBaseUrl(): string {
    $url = \Civi::settings()->get('fts_solr_url');
    if (empty($url)) {
      throw new NoConnectionException("fts_solr_url setting is not configured.");
    }
    return rtrim($url, '/');
  }

  public function getCoreName(): string {
    $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $this->searchDisplay['name']));
    return 'fts_' . $cleanName;
  }

  public function getCoreUrl(string $path = ''): string {
    if ($this->activeCoreUrl !== NULL) {
      $coreUrl = $this->activeCoreUrl;
    }
    else {
      $baseUrl = $this->getSolrBaseUrl();
      $parsed = parse_url($baseUrl);
      $pathParts = array_values(array_filter(explode('/', $parsed['path'] ?? '')));

      if (count($pathParts) >= 2 && $pathParts[0] === 'solr') {
        $coreUrl = $baseUrl;
      }
      else {
        if (!str_contains($baseUrl, '/solr')) {
          $baseUrl .= '/solr';
        }
        $coreUrl = $baseUrl . '/' . $this->getCoreName();
      }
      $this->activeCoreUrl = $coreUrl;
    }

    if ($path) {
      return $coreUrl . '/' . ltrim($path, '/');
    }
    return $coreUrl;
  }

  public function createApi4Action(string $action): AbstractAction {
    if ($action === 'get') {
      return new SolrGetAction($this->connection, $this->savedSearch, $this->searchDisplay, $this);
    }
    throw new \CRM_Core_Exception("Unsupported action '$action' for SolrFTS");
  }

  public function initialize(): void {
    $selectCheckUrl = $this->getCoreUrl('select?q=*:*&rows=0&wt=json');
    $response = $this->httpRequest('GET', $selectCheckUrl);

    if ($response['code'] !== 200) {
      // Core is not healthy/ready (returns 404, 400, or 500), attempt to create via Solr Admin API
      $baseUrl = $this->getSolrBaseUrl();
      if (!str_contains($baseUrl, '/solr')) {
        $baseUrl .= '/solr';
      }
      $adminUrl = $baseUrl . '/admin/cores?action=CREATE&name=' . $this->getCoreName() . '&wt=json';
      $createRes = $this->httpRequest('GET', $adminUrl);

      if ($createRes['code'] !== 200) {
        // Fallback to gettingstarted core if standalone server without default configsets
        $fallbackUrl = $baseUrl . '/gettingstarted';
        $fallbackCheck = $this->httpRequest('GET', $fallbackUrl . '/select?q=*:*&rows=0&wt=json');
        if ($fallbackCheck['code'] === 200) {
          $this->activeCoreUrl = $fallbackUrl;
        }
        else {
          throw new NoConnectionException("Unable to initialize Solr core at " . $this->getCoreUrl() . " and fallback failed.");
        }
      }
    }
  }

  public function truncate(): void {
    $updateUrl = $this->getCoreUrl('update?commit=true');
    $this->httpRequest('POST', $updateUrl, ['delete' => ['query' => '*:*']]);
  }

  public function destroy(): void {
    try {
      $this->truncate();
      $baseUrl = $this->getSolrBaseUrl();
      if (!str_contains($baseUrl, '/solr')) {
        $baseUrl .= '/solr';
      }
      $adminUrl = $baseUrl . '/admin/cores?action=UNLOAD&core=' . $this->getCoreName() . '&deleteIndex=true&deleteDataDir=true&wt=json';
      $this->httpRequest('GET', $adminUrl);
    }
    catch (\Exception $e) {
      // Ignore unload errors if core was shared or already dropped
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

    $updateUrl = $this->getCoreUrl('update?commit=true');
    $this->httpRequest('POST', $updateUrl, $records);
  }

  public function getRecordsFromSolr(SolrGetAction $action): array {
    $queryParts = [];

    foreach ($action->getWhere() as $clause) {
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

    $q = $queryParts ? implode(' AND ', $queryParts) : '*:*';
    $limit = $action->getLimit() ?: 100;
    $offset = $action->getOffset() ?: 0;

    $selectUrl = $this->getCoreUrl('select?' . http_build_query([
      'q' => $q,
      'rows' => $limit,
      'start' => $offset,
      'wt' => 'json',
    ]));

    $response = $this->httpRequest('GET', $selectUrl);
    if ($response['code'] !== 200) {
      throw new \CRM_Core_Exception("Solr query failed for URL '$selectUrl' with HTTP code {$response['code']}: {$response['body']}");
    }

    $data = json_decode($response['body'], TRUE);
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

  /**
   * Helper to perform HTTP requests to Solr.
   */
  protected function httpRequest(string $method, string $url, ?array $postData = NULL): array {
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);

    if ($method === 'POST') {
      curl_setopt($ch, CURLOPT_POST, TRUE);
      curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData ?? []));
    }

    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($body === FALSE) {
      throw new NoConnectionException("Failed to connect to Solr server at $url: $error");
    }

    return ['code' => $code, 'body' => $body];
  }

}

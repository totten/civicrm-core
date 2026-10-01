<?php

namespace Civi\SearchKitFts;

use Civi\Core\Service\AutoService;

/**
 * @service fts
 */
class FTS extends AutoService {

  /**
   * Find/create the backend service object for a particular FTS SearchDisplay
   *
   * @param string $name
   *   Ex: 'FTS_ContactQuickSearch' or 'ContactQuickSearch'
   * @return AbstractFTS
   *   Ex: An instance of SolrFTS or MySQLFTS or similar
   * @throws \Civi\SearchKitFts\Exception\NoConnectionException
   * @throws \CRM_Core_Exception
   */
  public function getByName(string $name): AbstractFTS {
    if (str_starts_with($name, 'FTS_')) {
      $displayName = substr($name, 4);
    }
    else {
      $displayName = $name;
    }

    $display = \Civi\Api4\SearchDisplay::get(FALSE)
      ->addSelect('id', 'name', 'label', 'type', 'settings', 'saved_search_id', 'saved_search_id.name', 'saved_search_id.api_entity', 'saved_search_id.api_params')
      ->addWhere('name', '=', $displayName)
      ->addWhere('type', '=', 'fts')
      ->execute()
      ->first();

    if (!$display) {
      throw new \CRM_Core_Exception("FTS SearchDisplay not found: {$displayName}");
    }

    $savedSearch = [
      'id' => $display['saved_search_id'],
      'name' => $display['saved_search_id.name'],
      'api_entity' => $display['saved_search_id.api_entity'],
      'api_params' => $display['saved_search_id.api_params'],
    ];

    $preferred = $display['settings']['preferred_engines'] ?? $display['settings']['preferredConnections'] ?? ['mysql'];
    $connection = $this->pickConnection((array) $preferred);

    if (!$connection) {
      throw new \Civi\SearchKitFts\Exception\NoConnectionException("No available FTS connection for display {$displayName}");
    }

    $backendClass = $connection['backend'];
    return new $backendClass($connection, $savedSearch, $display);
  }

  /**
   * @param array $preferredConnections
   *   List of acceptable connection types, from most-preferred to least-preferred.
   *   Ex: ['typesense', 'solr', 'mysql']
   * @return array{name: string, label: string, checkAvailable: callable, backend: string}|null
   *   First available connection.
   */
  public function pickConnection(array $preferredConnections): ?array {
    $connections = [];
    foreach ($this->getConnections() as $conn) {
      $connections[$conn['name']] = $conn;
    }

    foreach ($preferredConnections as $pref) {
      if (isset($connections[$pref])) {
        $conn = $connections[$pref];
        if (call_user_func($conn['checkAvailable'])) {
          return $conn;
        }
      }
    }
    return NULL;
  }

  /**
   * Get list of known connections.
   *
   * @return array{array{name: string, label: string, checkAvailable: callable, backend: string}}
   */
  public function getConnections(): array {
    return [
      [
        'name' => 'mysql',
        'label' => 'MySQL',
        'checkAvailable' => fn() => TRUE,
        'backend' => '\Civi\SearchKitFts\MySQLFTS',
      ],
      [
        'name' => 'solr',
        'label' => 'Solr',
        'checkAvailable' => fn() => !empty(\Civi::settings()->get('fts_solr_url')),
        'backend' => '\Civi\SearchKitFts\SolrFTS',
      ],
      [
        'name' => 'typesense',
        'label' => 'TypeSense',
        'checkAvailable' => fn() => !empty(\Civi::settings()->get('fts_typesense_url')),
        'backend' => '\Civi\SearchKitFts\TypeSenseFTS',
      ],
      [
        'name' => 'elastic',
        'label' => 'ElasticSearch',
        'checkAvailable' => fn() => !empty(\Civi::settings()->get('fts_elastic_url')),
        'backend' => '\Civi\SearchKitFts\ElasticFTS',
      ],
    ];
  }

}

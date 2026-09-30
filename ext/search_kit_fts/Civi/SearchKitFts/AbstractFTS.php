<?php

namespace Civi\SearchKitFts;

abstract class AbstractFTS {

  public function __construct(
    protected array $connection,
    protected array $savedSearch,
    protected array $searchDisplay
  ) {
  }

  /**
   * Prepare an APIv4 action based on this FTS engine.
   *
   * @param string $action
   *  Example: 'get'
   * @return \Civi\Api4\Generic\AbstractAction
   */
  abstract public function createApi4Action(string $action): \Civi\Api4\Generic\AbstractAction;

  /**
   * Prepare storage space.
   */
  abstract public function initialize(): void;

  /**
   * Clear all data.
   */
  abstract public function truncate(): void;

  /**
   * Destroy all data+metadata.
   */
  abstract public function destroy(): void;

  /**
   * Perform a full rebuild.
   */
  public function rebuild(): void {
    $this->truncate();
    $page = 1;
    $pageSize = 500;
    while (TRUE) {
      $result = civicrm_api4('SearchDisplay', 'run', [
        'savedSearch' => $this->savedSearch['name'],
        'display' => $this->searchDisplay['name'],
        'return' => 'page:' . $page,
        'limit' => $pageSize,
        'checkPermissions' => FALSE,
      ]);
      $records = (array) $result;
      if (!count($records)) {
        break;
      }
      $converted = $this->convertRecords($records);
      $this->insertRecords($converted);
      if (count($records) < $pageSize) {
        break;
      }
      $page++;
    }
  }

  /**
   * Translate from a local APIv4-style data-record to a remote/FTS-style data-record.
   */
  abstract public function convertRecords(array $records): array;

  abstract public function insertRecords(array $records): void;

}

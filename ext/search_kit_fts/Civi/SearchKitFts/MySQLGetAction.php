<?php

namespace Civi\SearchKitFts;

use Civi\Api4\Generic\BasicGetAction;
use Civi\Api4\Generic\Result;

class MySQLGetAction extends BasicGetAction {

  public function __construct(
    protected array $connection,
    protected array $savedSearch,
    protected array $searchDisplay,
    protected MySQLFTS $ftsBackend
  ) {
    $entityName = 'FTS_' . $searchDisplay['name'];
    parent::__construct($entityName, 'get');
  }

  public function _run(Result $result) {
    $values = $this->getRecords();
    $result->exchangeArray($values);
  }

  protected function getRecords(): array {
    return $this->ftsBackend->getRecordsFromSql($this);
  }

}

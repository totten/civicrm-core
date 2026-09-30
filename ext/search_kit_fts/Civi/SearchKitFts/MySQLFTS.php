<?php

namespace Civi\SearchKitFts;

use Civi\Api4\Generic\AbstractAction;

class MySQLFTS extends AbstractFTS {

  public function getTableName(): string {
    $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $this->searchDisplay['name']));
    return 'civicrm_fts_' . $cleanName;
  }

  public function createApi4Action(string $action): AbstractAction {
    if ($action === 'get') {
      return new MySQLGetAction($this->connection, $this->savedSearch, $this->searchDisplay, $this);
    }
    throw new \CRM_Core_Exception("Unsupported action '$action' for MySQLFTS");
  }

  public function initialize(): void {
    $tableName = $this->getTableName();
    \CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS `$tableName`");

    $columnDefs = ["`id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY"];
    $columns = $this->searchDisplay['settings']['columns'] ?? [];

    foreach ($columns as $column) {
      $colName = $column['spec']['name'] ?? $column['key'] ?? NULL;
      if ($colName) {
        $dataType = $column['spec']['data_type'] ?? 'String';
        $sqlType = match ($dataType) {
          'Integer', 'Boolean' => 'INT',
          'Float', 'Money' => 'DOUBLE',
          'Date', 'Timestamp' => 'DATETIME',
          default => 'VARCHAR(255)',
        };
        $columnDefs[] = "`$colName` $sqlType NULL";
      }
    }

    $columnDefs[] = "`fts` LONGTEXT NULL";
    $columnDefs[] = "FULLTEXT INDEX `fts_idx` (`fts`)";

    $sql = "CREATE TABLE `$tableName` (\n  " . implode(",\n  ", $columnDefs) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    \CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, FALSE, FALSE);
  }

  public function truncate(): void {
    $tableName = $this->getTableName();
    try {
      \CRM_Core_DAO::executeQuery("TRUNCATE TABLE `$tableName`", [], TRUE, NULL, FALSE, FALSE);
    }
    catch (\Exception $e) {
      // Fallback to delete if truncate fails
      \CRM_Core_DAO::executeQuery("DELETE FROM `$tableName`", [], TRUE, NULL, FALSE, FALSE);
    }
  }

  public function destroy(): void {
    $tableName = $this->getTableName();
    \CRM_Core_DAO::executeQuery("DROP TABLE IF EXISTS `$tableName`", [], TRUE, NULL, FALSE, FALSE);
  }

  public function convertRecords(array $records): array {
    $converted = [];
    $columns = $this->searchDisplay['settings']['columns'] ?? [];

    foreach ($records as $record) {
      $row = [];
      $ftsTextParts = [];

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
        $row[$colName] = $val;

        if ($val !== NULL && $val !== '') {
          $ftsTextParts[] = (string) $val;
        }
      }

      $row['fts'] = implode(' ', $ftsTextParts);
      $converted[] = $row;
    }

    return $converted;
  }

  public function insertRecords(array $records): void {
    if (empty($records)) {
      return;
    }

    $tableName = $this->getTableName();
    $cols = array_keys($records[0]);
    $quotedCols = array_map(fn($c) => "`$c`", $cols);

    $valueRows = [];
    foreach ($records as $record) {
      $escapedVals = [];
      foreach ($cols as $c) {
        $val = $record[$c] ?? NULL;
        if ($val === NULL) {
          $escapedVals[] = 'NULL';
        }
        else {
          $escapedVals[] = "'" . \CRM_Core_DAO::escapeString((string) $val) . "'";
        }
      }
      $valueRows[] = '(' . implode(', ', $escapedVals) . ')';
    }

    $sql = sprintf(
      "INSERT INTO `%s` (%s) VALUES %s",
      $tableName,
      implode(', ', $quotedCols),
      implode(",\n", $valueRows)
    );

    \CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, FALSE, FALSE);
  }

  public function getRecordsFromSql(MySQLGetAction $action): array {
    $tableName = $this->getTableName();
    $whereClauses = [];

    foreach ($action->getWhere() as $clause) {
      if (!is_array($clause) || count($clause) < 3) {
        continue;
      }
      [$field, $op, $val] = $clause;
      $fieldSql = "`" . preg_replace('/[^a-zA-Z0-9_]/', '', $field) . "`";

      if ($op === 'CONTAINS' || $op === 'LIKE') {
        $valEscaped = \CRM_Core_DAO::escapeString((string) $val);
        $whereClauses[] = "$fieldSql LIKE '%$valEscaped%'";
      }
      elseif ($op === '=') {
        if ($val === NULL) {
          $whereClauses[] = "$fieldSql IS NULL";
        }
        else {
          $valEscaped = \CRM_Core_DAO::escapeString((string) $val);
          $whereClauses[] = "$fieldSql = '$valEscaped'";
        }
      }
      elseif ($op === '!=') {
        if ($val === NULL) {
          $whereClauses[] = "$fieldSql IS NOT NULL";
        }
        else {
          $valEscaped = \CRM_Core_DAO::escapeString((string) $val);
          $whereClauses[] = "$fieldSql != '$valEscaped'";
        }
      }
    }

    $sql = "SELECT * FROM `$tableName`";
    if ($whereClauses) {
      $sql .= " WHERE " . implode(' AND ', $whereClauses);
    }

    $limit = $action->getLimit();
    $offset = $action->getOffset();
    if ($limit) {
      $sql .= " LIMIT $limit";
      if ($offset) {
        $sql .= " OFFSET $offset";
      }
    }

    $dao = \CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, FALSE, FALSE);
    $rows = [];
    while ($dao->fetch()) {
      $row = $dao->toArray();
      unset($row['id']); /* internal autoincrement primary key */
      $rows[] = $row;
    }

    return $rows;
  }

}

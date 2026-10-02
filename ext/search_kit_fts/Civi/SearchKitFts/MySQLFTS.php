<?php

namespace Civi\SearchKitFts;

use Civi\Api4\Generic\AbstractAction;
use Civi\Search\Meta;
use PDO;

class MySQLFTS extends AbstractFTS {

  protected ?PDO $pdo = NULL;

  /**
   * Get a separate, non-transactional PDO connection to MySQL.
   *
   * @return \PDO
   */
  public function getDriver(): PDO {
    if ($this->pdo === NULL) {
      require_once 'DB.php';
      $dsn = \CRM_Utils_SQL::autoSwitchDSN(\CIVICRM_DSN);
      $dsninfo = \DB::parseDSN($dsn);
      $host = $dsninfo['hostspec'];
      $port = @$dsninfo['port'];
      $database = $dsninfo['database'];
      $bufferedQuery = defined('Pdo\Mysql::ATTR_USE_BUFFERED_QUERY') ? \Pdo\Mysql::ATTR_USE_BUFFERED_QUERY : PDO::MYSQL_ATTR_USE_BUFFERED_QUERY;

      $this->pdo = new PDO(
        "mysql:host={$host}" . ($port ? ";port=$port" : "") . ($database ? ";dbname=$database" : "") . ";charset=utf8mb4",
        $dsninfo['username'],
        $dsninfo['password'],
        [
          $bufferedQuery => TRUE,
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
      );
    }
    return $this->pdo;
  }

  public function getTableName(): string {
    $cleanName = strtolower(preg_replace('/[^a-zA-Z0-9_]/', '', $this->searchDisplay['name']));
    return 'civicrm_fts_' . $cleanName;
  }

  /**
   * Get text/string column names for the fulltext index.
   *
   * @return array
   */
  public function getFtsColumns(): array {
    $columns = $this->searchDisplay['settings']['columns'] ?? [];
    $ftsCols = [];
    foreach ($columns as $column) {
      $colName = $column['spec']['name'] ?? Meta::createSqlName($column['key'])[0] ?? NULL;
      if ($colName && $colName !== 'id') {
        $dataType = $column['spec']['data_type'] ?? 'String';
        if (in_array($dataType, ['String', 'Text', 'Array'], TRUE) || !isset($column['spec']['data_type'])) {
          $ftsCols[] = $colName;
        }
      }
    }
    if (empty($ftsCols)) {
      foreach ($columns as $column) {
        $colName = $column['spec']['name'] ?? Meta::createSqlName($column['key'])[0] ?? NULL;
        if ($colName) {
          $ftsCols[] = $colName;
        }
      }
    }
    return $ftsCols;
  }

  public function createApi4Action(string $action): AbstractAction {
    if ($action === 'get') {
      return new MySQLGetAction($this->connection, $this->savedSearch, $this->searchDisplay, $this);
    }
    throw new \CRM_Core_Exception("Unsupported action '$action' for MySQLFTS");
  }

  public function initialize(): void {
    $pdo = $this->getDriver();
    $tableName = $this->getTableName();
    $pdo->exec("DROP TABLE IF EXISTS `$tableName`");

    $columnDefs = ["`id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY"];
    $columns = $this->searchDisplay['settings']['columns'] ?? [];

    foreach ($columns as $column) {
      $colName = $column['spec']['name'] ?? Meta::createSqlName($column['key'])[0] ?? NULL;
      if ($colName && strtolower($colName) !== 'id') {
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

    $ftsCols = $this->getFtsColumns();
    if (!empty($ftsCols)) {
      $columnDefs[] = "FULLTEXT INDEX `fts_idx` (`" . implode('`, `', $ftsCols) . "`)";
    }

    $sql = "CREATE TABLE `$tableName` (\n  " . implode(",\n  ", $columnDefs) . "\n) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
    $pdo->exec($sql);
  }

  public function truncate(): void {
    $pdo = $this->getDriver();
    $tableName = $this->getTableName();
    try {
      $pdo->exec("TRUNCATE TABLE `$tableName`");
    }
    catch (\Exception $e) {
      $pdo->exec("DELETE FROM `$tableName`");
    }
  }

  public function destroy(): void {
    $pdo = $this->getDriver();
    $tableName = $this->getTableName();
    $pdo->exec("DROP TABLE IF EXISTS `$tableName`");
  }

  public function convertRecords(array $records): array {
    $converted = [];
    $columns = $this->searchDisplay['settings']['columns'] ?? [];

    foreach ($records as $record) {
      $row = [];
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
        $row[$colName] = $val;
      }
      $converted[] = $row;
    }

    return $converted;
  }

  public function insertRecords(array $records): void {
    if (empty($records)) {
      return;
    }

    $pdo = $this->getDriver();
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
          $escapedVals[] = $pdo->quote((string) $val);
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

    $pdo->exec($sql);
  }

  public function getRecordsFromSql(MySQLGetAction $action): array {
    $pdo = $this->getDriver();
    $tableName = $this->getTableName();
    $whereClauses = [];

    foreach ($action->getWhere() as $clause) {
      if (!is_array($clause) || count($clause) < 3) {
        continue;
      }
      [$field, $op, $val] = $clause;

      if ($field === 'fts') {
        if ($op === 'CONTAINS' || $op === 'LIKE') {
          $ftsCols = $this->getFtsColumns();
          if (empty($ftsCols)) {
            throw new \CRM_Core_Exception("No text columns available for 'fts' search in MySQLFTS");
          }
          $ftsColsSql = implode('`, `', $ftsCols);
          $valQuoted = $pdo->quote((string) $val);
          $whereClauses[] = "MATCH(`$ftsColsSql`) AGAINST($valQuoted IN NATURAL LANGUAGE MODE)";
        }
        elseif ($op === '=' || $op === '!=') {
          throw new \CRM_Core_Exception("Operator '$op' is not supported for 'fts' field in MySQLFTS. Use 'CONTAINS' instead.");
        }
        else {
          throw new \CRM_Core_Exception("Unsupported operator '$op' for 'fts' field in MySQLFTS");
        }
        continue;
      }

      $fieldSql = "`" . preg_replace('/[^a-zA-Z0-9_]/', '', $field) . "`";

      if ($op === 'CONTAINS' || $op === 'LIKE') {
        $valEscaped = addcslashes((string) $val, '%_\\');
        $valQuoted = $pdo->quote("%{$valEscaped}%");
        $whereClauses[] = "$fieldSql LIKE $valQuoted";
      }
      elseif ($op === '=') {
        if ($val === NULL) {
          $whereClauses[] = "$fieldSql IS NULL";
        }
        else {
          $whereClauses[] = "$fieldSql = " . $pdo->quote((string) $val);
        }
      }
      elseif ($op === '!=') {
        if ($val === NULL) {
          $whereClauses[] = "$fieldSql IS NOT NULL";
        }
        else {
          $whereClauses[] = "$fieldSql != " . $pdo->quote((string) $val);
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

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$row) {
      unset($row['id']);
    }

    return $rows;
  }

}

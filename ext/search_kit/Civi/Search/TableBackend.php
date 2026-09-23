<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Search;

class TableBackend extends AbstractBackend {

  use \Civi\Api4\Generic\Traits\SavedSearchInspectorTrait;

  public function __construct(
    protected string $skEntity,
    protected string $realEntity,
    protected array $realParams,
    protected array $settings
  ) {
    parent::__construct($skEntity, $realEntity, $realParams, $settings);
    $this->savedSearch = [
      'api_entity' => $realEntity,
      'api_params' => $realParams,
    ];
    $this->loadSavedSearch();
  }

  public function initialize(): void {
    [, $displayName] = explode('_', $this->skEntity, 2);
    $tableName = _getSearchKitDisplayTableName($displayName);

    $table = [
      'name' => $tableName,
      'is_multiple' => FALSE,
      'attributes' => 'ENGINE=InnoDB',
      'fields' => [],
    ];

    foreach ($this->settings['columns'] as $column) {
      $expr = $this->getSelectExpression($column['key']);
      if (!$expr) {
        continue;
      }
      $table['fields'][] = $this->formatSQLSpec($column, $expr);
    }

    $sql = \CRM_Core_BAO_SchemaHandler::buildTableSQL($table);
    // do not i18n-rewrite
    \CRM_Core_DAO::executeQuery($sql, [], TRUE, NULL, FALSE, FALSE);
  }

  public function clearData(): void {
    [, $displayName] = explode('_', $this->skEntity, 2);
    $tableName = _getSearchKitDisplayTableName($displayName);
    \CRM_Core_DAO::executeQuery("TRUNCATE TABLE `$tableName`");
  }

  public function fillData(): void {
    [, $displayName] = explode('_', $this->skEntity, 2);
    $tableName = _getSearchKitDisplayTableName($displayName);
    $sql = (new SKEntityGenerator())->createQuery($this->realEntity, $this->realParams, $this->settings);
    $columnSpecs = array_column($this->settings['columns'], 'spec');
    $columns = implode(', ', array_column($columnSpecs, 'name'));
    \CRM_Core_DAO::executeQuery("INSERT INTO `$tableName` ($columns) $sql");
  }

  public function destroy(): void {
    [, $displayName] = explode('_', $this->skEntity, 2);
    $tableName = _getSearchKitDisplayTableName($displayName);
    \CRM_Core_BAO_SchemaHandler::dropTable($tableName);
  }

  /**
   * @param array $column
   * @param array{fields: array, expr: \Civi\Api4\Query\SqlExpression, dataType: string} $expr
   * @return array
   */
  private function formatSQLSpec(array $column, array $expr): array {
    $field = \CRM_Utils_Array::first($expr['fields']);
    // Store serialized values as text
    if ($expr['expr']->getSerialize()) {
      $type = 'text';
    }
    // Try to use the exact sql column type as the original field
    elseif (!empty($field['column_name']) && !empty($field['table_name']) && $field['data_type'] === $expr['dataType']) {
      $columns = \CRM_Core_DAO::executeQuery("DESCRIBE `{$field['table_name']}`")
        ->fetchMap('Field', 'Type');
      $type = $columns[$field['column_name']] ?? NULL;
    }
    // If we can't get the data type from the column, take an educated guess
    if (empty($type)) {
      $map = [
        'Array' => 'text',
        'Boolean' => 'tinyint',
        'Date' => 'date',
        'Float' => 'double',
        'Integer' => 'int',
        'String' => 'text',
        'Text' => 'text',
        'Timestamp' => 'datetime',
        'Money' => 'decimal(20,2)',
      ];
      $type = $map[$expr['dataType']] ?? 'text';
    }
    $defn = [
      'name' => $column['spec']['name'],
      'type' => $type,
      // Adds an index to non-fk fields
      'searchable' => TRUE,
    ];
    // Add FK indexes
    if ($expr['expr']->getType() === 'SqlField' && !empty($field['fk_entity'])) {
      $defn['fk_table_name'] = \Civi\Api4\Utils\CoreUtil::getTableName($field['fk_entity']);
      $defn['fk_field_name'] = $field['fk_column'];
      $defn['fk_attributes'] = ' ON DELETE SET NULL';
    }
    return $defn;
  }

}

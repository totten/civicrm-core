<?php
/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 *
 * Generated from __ECHO('$table["sourceFile"]')
 * __ECHO('$generated')
 * (GenCodeChecksum:__ECHO('$genCodeChecksum'))
 */
//META: echo $useHelper;

/**
 * Database access object for the __ECHO('$table["entity"]') entity.
 */
class __ECHO_table_className extends CRM_Core_DAO {
  const EXT = __ECHO_ext;
  const TABLE_ADDED = __EXPORT_table_add;
  // META: if (!empty($table['component'])) printf("  const COMPONENT = %s;\n", var_export($table['component'], 1));

  /**
   * Static instance to hold the table name.
   *
   * @var string
   */
  public static $_tableName = __EXPORT_table_name;

  // META: if (!empty($table['icon'])) {
  /**
   * Icon associated with this entity.
   *
   * @var string
   */
  public static $_icon = __EXPORT_table_icon;
  // META: }

  // META: if (!empty($table['labelField'])) {
  /**
   * Field to show when displaying a record.
   *
   * @var string
   */
  public static $_labelField = __EXPORT_table_labelField;
  // META: }

  /**
   * Should CiviCRM log any modifications to this table in the civicrm_log table.
   *
   * @var bool
   */
  // META: printf("  public static \$_log = %s;\n", strtoupper($table['log']));

  // META: foreach($table['fields'] as $field) {
  // META:   printf("  /**\n");
  // META:   if ($field['comment']) printf("   * %s\n   *\n", preg_replace("/\n[ ]*/", "\n* ", $field['comment']));
  // META:   printf("   * @var %s\n", $field['phpType']);
  // META:   printf("   */\n");
  // META:   printf("  public \$%s;\n", $field['name']);
  // META: }

  /**
   * Class constructor.
   */
  public function __construct() {
    $this->__table = __EXPORT('$table["name"]');
    parent::__construct();
  }

  /**
   * Returns localized title of this entity.
   *
   * @param bool $plural
   *   Whether to return the plural version of the title.
   */
  public static function getEntityTitle($plural = FALSE) {
    return $plural ? __ECHO('$tsFunctionName')(__EXPORT('$table["titlePlural"]')) : __ECHO('$tsFunctionName')(__EXPORT('$table["title"]'));
  }

  // META: if (!empty($table['description'])) {
  /**
   * Returns user-friendly description of this entity.
   *
   * @return string
   */
  public static function getEntityDescription() {
    return __ECHO('$tsFunctionName')(__EXPORT('$table["description"]'));
  }
  // META: }


  /**
   * Return a mapping from field-name to the corresponding key (as used in fields()).
   *
   * @return array
   *   Array(string $name => string $uniqueName).
   */
  public static function &fieldKeys() {
    if (!isset(Civi::$statics[__CLASS__]['fieldKeys'])) {
      Civi::$statics[__CLASS__]['fieldKeys'] = array_flip(CRM_Utils_Array::collect('name', self::fields()));
    }
    return Civi::$statics[__CLASS__]['fieldKeys'];
  }

  /**
   * Returns if this table needs to be logged
   *
   * @return bool
   */
  public function getLog() {
    return self::$_log;
  }

  /**
   * Returns the list of indices
   *
   * @param bool $localize
   *
   * @return array
   */
  public static function indices($localize = TRUE) {
    $indices = __ECHO('$indicesPhp');
    return ($localize && !empty($indices)) ? CRM_Core_DAO_AllCoreTables::multilingualize(__CLASS__, $indices) : $indices;
  }

}

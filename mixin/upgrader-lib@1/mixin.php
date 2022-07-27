<?php
/**
 * Generated via "mixin/upgrader-lib@1/build.php 1.0"
 *
 * @mixinName upgrader-lib
 * @mixinVersion 1.0.0
 * @since 5.52
 */
namespace Civi\Mixin\UpgraderLibV1;

// @codingStandardsIgnoreStart
// SOURCE: CRM/Extension/Upgrader/Base.php


/**
 * Base class which provides helpers to execute upgrade logic.
 *
 * LIFECYCLE METHODS: Subclasses may optionally define install(), postInstall(),
 * uninstall(), enable(), disable().
 *
 * UPGRADE METHODS: Subclasses may define any number of methods named "upgrade_NNNN()".
 * Each value of NNNN is treated as a new schema revision. (See also: RevisionsTrait)
 *
 * QUEUE METHODS: Upgrade tasks execute within a queue. If an upgrader needs to perform
 * a large amount of work, it can use "addTask()" / "prependTask()" / "appendTask()".
 * (See also: QueueTrait)
 *
 * EXECUTE METHODS: When writing lifecycle methods, upgrade methods, or queue
 * tasks, you may wish to execute common steps like "run a SQL file".
 * (See also: TasksTrait)
 */
class Base implements UpgraderInterface {

  use IdentityTrait;
  use QueueTrait;
  use RevisionsTrait;
  use TasksTrait;
  use SchemaTrait;

  /**
   * {@inheritDoc}
   */
  public function notify(string $event, array $params = []) {
    $cb = [$this, 'on' . ucfirst($event)];
    return is_callable($cb) ? call_user_func_array($cb, $params) : NULL;
  }

  // ******** Hook delegates ********

  /**
   * Run early installation steps. Ex: Create new MySQL table.
   *
   * This dispatches directly to each new extension. You will only receive notices for your own installation.
   *
   * If multiple extensions are installed simultaneously, they will all run
   * `hook_install`/`hook_enable` back-to-back (in order of dependency).
   *
   * This runs BEFORE refreshing major caches and services (such as
   * `ManagedEntities` and `CRM_Logging_Schema`).
   *
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_install
   */
  public function onInstall() {
    $files = glob($this->getExtensionDir() . '/sql/*_install.sql');
    if (is_array($files)) {
      foreach ($files as $file) {
        CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $file);
      }
    }
    $files = glob($this->getExtensionDir() . '/sql/*_install.mysql.tpl');
    if (is_array($files)) {
      foreach ($files as $file) {
        $this->executeSqlTemplate($file);
      }
    }
    $files = glob($this->getExtensionDir() . '/xml/*_install.xml');
    if (is_array($files)) {
      foreach ($files as $file) {
        $this->executeCustomDataFileByAbsPath($file);
      }
    }
    if (is_callable([$this, 'install'])) {
      $this->install();
    }
  }

  /**
   * Run later installation steps. Ex: Call a bespoke API-job for the first time.
   *
   * This dispatches directly to each new extension. You will only receive notices for your own installation.
   *
   * If multiple extensions are installed simultaneously, they will all run
   * `hook_postInstall` back-to-back (in order of dependency).
   *
   * This runs AFTER refreshing major caches and services (such as
   * `ManagedEntities` and `CRM_Logging_Schema`).
   *
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_postInstall
   */
  public function onPostInstall() {
    $revisions = $this->getRevisions();
    if (!empty($revisions)) {
      $this->setCurrentRevision(max($revisions));
    }
    if (is_callable([$this, 'postInstall'])) {
      $this->postInstall();
    }
  }

  /**
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_unnstall
   */
  public function onUninstall() {
    $files = glob($this->getExtensionDir() . '/sql/*_uninstall.mysql.tpl');
    if (is_array($files)) {
      foreach ($files as $file) {
        $this->executeSqlTemplate($file);
      }
    }
    if (is_callable([$this, 'uninstall'])) {
      $this->uninstall();
    }
    $files = glob($this->getExtensionDir() . '/sql/*_uninstall.sql');
    if (is_array($files)) {
      foreach ($files as $file) {
        CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $file);
      }
    }
  }

  /**
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_enable
   */
  public function onEnable() {
    // stub for possible future use
    if (is_callable([$this, 'enable'])) {
      $this->enable();
    }
  }

  /**
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_disable
   */
  public function onDisable() {
    // stub for possible future use
    if (is_callable([$this, 'disable'])) {
      $this->disable();
    }
  }

  /**
   * @see https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_upgrade
   */
  public function onUpgrade($op, CRM_Queue_Queue $queue = NULL) {
    switch ($op) {
      case 'check':
        return [$this->hasPendingRevisions()];

      case 'enqueue':
        $this->setQueue($queue);
        return $this->enqueuePendingRevisions();

      default:
    }
  }

}
// @codingStandardsIgnoreEnd
// @codingStandardsIgnoreStart
// SOURCE: CRM/Extension/Upgrader/IdentityTrait.php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * Track minimal information which identifies the target extension.
 */
trait IdentityTrait {

  /**
   * @var string
   *   eg 'com.example.myextension'
   */
  protected $extensionName;

  /**
   * @var string
   *   full path to the extension's source tree
   */
  protected $extensionDir;

  /**
   * {@inheritDoc}
   */
  public function init(array $params) {
    $this->extensionName = $params['key'];
    $system = CRM_Extension_System::singleton();
    $mapper = $system->getMapper();
    $this->extensionDir = $mapper->keyToBasePath($this->extensionName);
  }

  /**
   * @return string
   *   Ex: 'org.example.foobar'
   */
  public function getExtensionKey() {
    return $this->extensionName;
  }

  /**
   * @return string
   *   Ex: '/var/www/sites/default/ext/org.example.foobar'
   */
  public function getExtensionDir() {
    return $this->extensionDir;
  }

}
// @codingStandardsIgnoreEnd
// @codingStandardsIgnoreStart
// SOURCE: CRM/Extension/Upgrader/Interface.php


/**
 * An "upgrader" is a class that handles the DB install+upgrade lifecycle
 * for an extension.
 */
interface UpgraderInterface {

  /**
   * @param array $params
   *   - string $key: Long form name ('org.example.myext')
   */
  public function init(array $params);

  /**
   * Notify the upgrader about a key lifecycle event, such as installation or uninstallation.
   *
   * Each event corresponds to a hook, such as `hook_civicrm_install` or `hook_civicrm_upgrade`.
   *
   * @param string $event
   *   One of the following: 'install', 'onPostInstall', 'enable', 'disable', 'uninstall', 'upgrade'
   * @param array $params
   *   Any data that would ordinarily be provided via the equivalent hook.
   *
   * @return mixed
   */
  public function notify(string $event, array $params = []);

}
// @codingStandardsIgnoreEnd
// @codingStandardsIgnoreStart
// SOURCE: CRM/Extension/Upgrader/QueueTrait.php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * The QueueTrait provides helper methods for adding new tasks to a queue.
 */
trait QueueTrait {

  abstract public function getExtensionKey();

  /**
   * @var \CRM_Queue_Queue
   */
  protected $queue;

  /**
   * @var \CRM_Queue_TaskContext
   */
  protected $ctx;

  /**
   * Adapter that lets you add normal (non-static) member functions to the queue.
   *
   * While working through a task-queue, the _queueAdapter is called statically. It looks up
   * the appropriate object and invokes the expected method.
   *
   * ```
   * CRM_Extension_Upgrader::_queueAdapter($ctx, 'org.example.myext', 'methodName', 'arg1', 'arg2');
   * ```
   */
  public static function _queueAdapter(CRM_Queue_TaskContext $ctx, string $extensionKey, string $method, ...$args) {
    /** @var static $upgrader */
    $upgrader = \CRM_Extension_System::singleton()->getMapper()->getUpgrader($extensionKey);
    if ($upgrader->ctx !== NULL) {
      throw new \RuntimeException(sprintf("Cannot execute task for %s (%s::%s) - task already active.", $extensionKey, get_class($upgrader), $method));
    }

    $upgrader->ctx = $ctx;
    $upgrader->queue = $ctx->queue;
    try {
      return call_user_func_array([$upgrader, $method], $args);
    } finally {
      $upgrader->ctx = NULL;
    }
  }

  public function addTask(string $title, string $funcName, ...$options) {
    return $this->prependTask($title, $funcName, ...$options);
  }

  /**
   * Enqueue a task based on a method in this class.
   *
   * The task is weighted so that it is processed as part of the currently-pending revision.
   *
   * After passing the $funcName, you can also pass parameters that will go to
   * the function. Note that all params must be serializable.
   */
  public function prependTask(string $title, string $funcName, ...$options) {
    $task = new CRM_Queue_Task(
      [get_class($this), '_queueAdapter'],
      array_merge([$this->getExtensionKey(), $funcName], $options),
      $title
    );
    return $this->queue->createItem($task, ['weight' => -1]);
  }

  /**
   * Enqueue a task based on a method in this class.
   *
   * The task has a default weight.
   *
   * @return mixed
   */
  protected function appendTask(string $title, string $funcName, ...$options) {
    $task = new CRM_Queue_Task(
      [get_class($this), '_queueAdapter'],
      array_merge([$this->getExtensionKey(), $funcName], $options),
      $title
    );
    return $this->queue->createItem($task);
  }

  // ******** Basic getters/setters ********

  /**
   * @return \CRM_Queue_Queue
   */
  public function getQueue(): \CRM_Queue_Queue {
    return $this->queue;
  }

  /**
   * @param \CRM_Queue_Queue $queue
   */
  public function setQueue(\CRM_Queue_Queue $queue): void {
    $this->queue = $queue;
  }

}
// @codingStandardsIgnoreEnd
// @codingStandardsIgnoreStart
// SOURCE: CRM/Extension/Upgrader/RevisionsTrait.php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * The revisions trait automatically enqueues any functions named 'upgrade_NNNN()'
 * (where NNNN is taken to be a revision number).
 */
trait RevisionsTrait {

  /**
   * @return string
   */
  abstract public function getExtensionKey();

  abstract protected function appendTask(string $title, string $funcName, ...$options);

  /**
   * @var array
   *   sorted numerically
   */
  private $revisions;

  /**
   * @var bool
   *   Flag to clean up extension revision data in civicrm_setting
   */
  private $revisionStorageIsDeprecated = FALSE;

  /**
   * Determine if there are any pending revisions.
   *
   * @return bool
   */
  public function hasPendingRevisions() {
    $revisions = $this->getRevisions();
    $currentRevision = $this->getCurrentRevision();

    if (empty($revisions)) {
      return FALSE;
    }
    if (empty($currentRevision)) {
      return TRUE;
    }

    return ($currentRevision < max($revisions));
  }

  /**
   * Add any pending revisions to the queue.
   */
  public function enqueuePendingRevisions() {
    $currentRevision = $this->getCurrentRevision();
    foreach ($this->getRevisions() as $revision) {
      if ($revision > $currentRevision) {
        $title = ts('Upgrade %1 to revision %2', [
          1 => $this->getExtensionKey(),
          2 => $revision,
        ]);

        // note: don't use addTask() because it sets weight=-1

        $this->appendTask($title, 'upgrade_' . $revision);
        $this->appendTask($title, 'setCurrentRevision', $revision);
      }
    }
  }

  /**
   * Get a list of revisions.
   *
   * @return array
   *   revisionNumbers sorted numerically
   */
  public function getRevisions() {
    if (!is_array($this->revisions)) {
      $this->revisions = [];

      $clazz = new \ReflectionClass(get_class($this));
      $methods = $clazz->getMethods();
      foreach ($methods as $method) {
        if (preg_match('/^upgrade_(.*)/', $method->name, $matches)) {
          $this->revisions[] = $matches[1];
        }
      }
      sort($this->revisions, SORT_NUMERIC);
    }

    return $this->revisions;
  }

  public function getCurrentRevision() {
    $revision = CRM_Core_BAO_Extension::getSchemaVersion($this->getExtensionKey());
    if (!$revision) {
      $revision = $this->getCurrentRevisionDeprecated();
    }
    return $revision;
  }

  private function getCurrentRevisionDeprecated() {
    $key = $this->getExtensionKey() . ':version';
    if ($revision = \Civi::settings()->get($key)) {
      $this->revisionStorageIsDeprecated = TRUE;
    }
    return $revision;
  }

  public function setCurrentRevision($revision) {
    CRM_Core_BAO_Extension::setSchemaVersion($this->getExtensionKey(), $revision);
    // clean up legacy schema version store (CRM-19252)
    $this->deleteDeprecatedRevision();
    return TRUE;
  }

  private function deleteDeprecatedRevision() {
    if ($this->revisionStorageIsDeprecated) {
      $setting = new \CRM_Core_BAO_Setting();
      $setting->name = $this->getExtensionKey() . ':version';
      $setting->delete();
      CRM_Core_Error::debug_log_message("Migrated extension schema revision ID for {$this->getExtensionKey()} from civicrm_setting (deprecated) to civicrm_extension.\n");
    }
  }

}
// @codingStandardsIgnoreEnd
// @codingStandardsIgnoreStart
// SOURCE: CRM/Extension/Upgrader/SchemaTrait.php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * The SchemaTrait provides utilities for altering tables during an upgrade.
 */
trait SchemaTrait {

  /**
   * Add a column to a table if it doesn't already exist
   *
   * @param string $table
   * @param string $column
   * @param string $properties
   *
   * @return bool
   */
  public static function addColumn($table, $column, $properties) {
    if (!CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, $column, FALSE)) {
      $query = "ALTER TABLE `$table` ADD COLUMN `$column` $properties";
      CRM_Core_DAO::executeQuery($query, [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

  /**
   * Drop a column from a table if it exists.
   *
   * @param string $table
   * @param string $column
   * @return bool
   */
  public static function dropColumn($table, $column) {
    if (CRM_Core_BAO_SchemaHandler::checkIfFieldExists($table, $column, FALSE)) {
      CRM_Core_DAO::executeQuery("ALTER TABLE `$table` DROP COLUMN `$column`",
        [], TRUE, NULL, FALSE, FALSE);
    }
    return TRUE;
  }

  /**
   * Add an index to one or more columns.
   *
   * @param string $table
   * @param string|array $columns
   * @param string $prefix
   * @return bool
   */
  public static function addIndex($table, $columns, $prefix = 'index') {
    $tables = [$table => (array) $columns];
    CRM_Core_BAO_SchemaHandler::createIndexes($tables, $prefix);
    return TRUE;
  }

  /**
   * Drop index from a table if it exists.
   *
   * @param string $table
   * @param string $indexName
   * @return bool
   */
  public static function dropIndex($table, $indexName) {
    CRM_Core_BAO_SchemaHandler::dropIndexIfExists($table, $indexName);
    return TRUE;
  }

}
// @codingStandardsIgnoreEnd
// @codingStandardsIgnoreStart
// SOURCE: CRM/Extension/Upgrader/TasksTrait.php

/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

/**
 * The TasksTrait provides a library of tasks that are useful to run during an upgrade.
 */
trait TasksTrait {

  /**
   * @return string
   */
  abstract public function getExtensionDir();

  /**
   * Run a CustomData file.
   *
   * @param string $relativePath
   *   the CustomData XML file path (relative to this extension's dir)
   * @return bool
   */
  public function executeCustomDataFile($relativePath) {
    $xml_file = $this->getExtensionDir() . '/' . $relativePath;
    return $this->executeCustomDataFileByAbsPath($xml_file);
  }

  /**
   * Run a CustomData file
   *
   * @param string $xml_file
   *   the CustomData XML file path (absolute path)
   *
   * @return bool
   */
  protected function executeCustomDataFileByAbsPath($xml_file) {
    $import = new CRM_Utils_Migrate_Import();
    $import->run($xml_file);
    return TRUE;
  }

  /**
   * Run a SQL file.
   *
   * @param string $tplFile
   *   The SQL file path (relative to this extension's dir, or absolute)
   *
   * @return bool
   */
  public function executeSqlFile($tplFile) {
    $tplFile = CRM_Utils_File::isAbsolute($tplFile) ? $tplFile : $this->getExtensionDir() . DIRECTORY_SEPARATOR . $tplFile;
    CRM_Utils_File::sourceSQLFile(CIVICRM_DSN, $tplFile);
    return TRUE;
  }

  /**
   * Run the sql commands in the specified file.
   *
   * @param string $tplFile
   *   The SQL file path (relative to this extension's dir, or absolute).
   *   Ex: "sql/mydata.mysql.tpl".
   *
   * @return bool
   * @throws \CRM_Core_Exception
   */
  public function executeSqlTemplate($tplFile) {
    // Assign multilingual variable to Smarty.
    $upgrade = new CRM_Upgrade_Form();

    $tplFile = CRM_Utils_File::isAbsolute($tplFile) ? $tplFile : $this->getExtensionDir() . DIRECTORY_SEPARATOR . $tplFile;
    $smarty = CRM_Core_Smarty::singleton();
    $smarty->assign('domainID', CRM_Core_Config::domainID());
    CRM_Utils_File::sourceSQLFile(
      CIVICRM_DSN, $smarty->fetch($tplFile), NULL, TRUE
    );
    return TRUE;
  }

  /**
   * Run one SQL query.
   *
   * This is just a wrapper for CRM_Core_DAO::executeSql, but it
   * provides syntactic sugar for queueing several tasks that
   * run different queries
   *
   * @return bool
   */
  public function executeSql($query, $params = []) {
    // FIXME verify that we raise an exception on error
    CRM_Core_DAO::executeQuery($query, $params);
    return TRUE;
  }

}
// @codingStandardsIgnoreEnd
return function($mixInfo, $bootCache) {
};

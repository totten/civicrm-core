<?php

namespace Civi\Install;

/**
 * Class Schema
 *
 * @package Civi\Install
 */
class Schema {

  /**
   * The table to check for. If it doesn't exist, then
   * assume this is a new installation.
   */
  const TEST_TABLE = 'civicrm_contact';

  /**
   * @var Settings
   */
  protected $settings;

  /**
   * @param Settings $settings
   *   Description of the new installation.
   */
  public function __construct($settings) {
    $this->settings = $settings;
  }

  /**
   * Install the new schema.
   *
   * @return array
   *   List of messages.
   */
  public function install() {
    $messages = array();

    if ($this->hasSchema($this->settings->dsn)) {
      $messages[] = array(
        'title' => 'CiviCRM Schema',
        'severity' => Requirements::REQUIREMENT_WARNING,
        'details' => 'The schema is already loaded',
      );
      return $messages;
    }

    $sqlPath = $this->settings->root . '/sql';

    $this->source($this->settings->dsn, $sqlPath . '/civicrm.mysql');

    if (!empty($this->settings->loadGenerated)) {
      $this->source($this->settings->dsn, $sqlPath . '/civicrm_generated.mysql', TRUE);
    }
    else {
      if (isset($this->settings->lang)
        and preg_match('/^[a-z][a-z]_[A-Z][A-Z]$/', $this->settings->lang)
        and file_exists($sqlPath . "/civicrm_data.{$this->settings->lang}.mysql")
        and file_exists($sqlPath . "/civicrm_acl.{$this->settings->lang}.mysql")
      ) {
        $this->source($this->settings->dsn, $sqlPath . "/civicrm_data.{$this->settings->lang}.mysql");
        $this->source($this->settings->dsn, $sqlPath . "/civicrm_acl.{$this->settings->lang}.mysql");
      }
      else {
        $this->source($this->settings->dsn, $sqlPath . '/civicrm_data.mysql');
        $this->source($this->settings->dsn, $sqlPath . '/civicrm_acl.mysql');
      }
    }

    $messages[] = array(
      'title' => 'CiviCRM Schema',
      'severity' => Requirements::REQUIREMENT_OK,
      'details' => 'Loaded',
    );

    return $messages;
  }

  /**
   * This class should not exist. It's being preserved as part of refactoring, but
   * ideally it should be deleted. Use CRM_Utils_File::sourceSQLFile instead.
   *
   * @param string $dsn
   * @param string $fileName
   * @param bool $lineMode
   */
  public function source($dsn, $fileName, $lineMode = FALSE) {
    $db = $this->connect($dsn);

    if (!$lineMode) {
      $string = file_get_contents($fileName);

      // change \r\n to fix windows issues
      $string = str_replace("\r\n", "\n", $string);

      //get rid of comments starting with # and --

      $string = preg_replace("/^#[^\n]*$/m", "\n", $string);
      $string = preg_replace("/^(--[^-]).*/m", "\n", $string);

      $queries = preg_split('/;\s*$/m', $string);
      foreach ($queries as $query) {
        $query = trim($query);
        if (!empty($query)) {
          $res = &$db->query($query);
          if (\PEAR::isError($res)) {
            print_r($res);
            die("Cannot execute $query: " . $res->getMessage());
          }
        }
      }
    }
    else {
      $fd = fopen($fileName, "r");
      while ($string = fgets($fd)) {
        $string = preg_replace("/^#[^\n]*$/m", "\n", $string);
        $string = preg_replace("/^(--[^-]).*/m", "\n", $string);

        $string = trim($string);
        if (!empty($string)) {
          $res = &$db->query($string);
          if (\PEAR::isError($res)) {
            die("Cannot execute $string: " . $res->getMessage());
          }
        }
      }
    }
  }

  /**
   * Determine if Civi's schema is already loaded in this DB.
   *
   * @param $dsn
   * @return bool
   */
  public function hasSchema($dsn) {
    $db = $this->connect($dsn);

    $r = $db->query("SHOW TABLES LIKE '" . self::TEST_TABLE . "'");
    if (\PEAR::isError($r)) {
      throw new \Exception("Failed to check for pre-installed schema.");
    }

    return $r->numRows() > 0;
  }

  /**
   * @param $dsn
   * @return object
   */
  protected function connect($dsn) {
    require_once "packages/DB.php";
    $db = \DB::connect($dsn);
    if (\PEAR::isError($db)) {
      die("Cannot open $dsn: " . $db->getMessage());
    }
    $db->query("SET NAMES utf8");
    return $db;
  }

}

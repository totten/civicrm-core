<?php

namespace Civi\Install;

/**
 * Class Settings
 * @package Civi\Install
 */
class Settings {

  const DEFAULT_LANG = 'en_US';

  /**
   * @param array $array
   *   List of key-value pairs.
   * @return Settings
   */
  public static function create($array) {
    return new Settings($array);
  }

  /**
   * @var string
   *   Type of content-management system.
   *   "Drupal|Drupal6|Drupal8|Joomla|WordPress"
   */
  public $uf;

  /**
   * @var string
   *   Path to civicrm.settings.php.
   */
  public $settingsPhp;

  /**
   * @var string
   *   Path to civicrm source root.
   */
  public $root;

  /**
   * @var string
   *   The base data dir. Used to compute default paths for other data dirs.
   *   Ex: "/var/www/sites/default/files/civicrm".
   */
  public $dataDir;

  /**
   * @var string
   *   Path to templates root.
   *   Default: $dataDir/templates_c
   */
  public $templateCompileDir;

  /**
   * @var string
   *   The data-source for the Civi DB.
   *   Ex: "mysql://user:pass@host:port/db?new_link=true".
   */
  public $dsn;

  /**
   * @var string
   *   A 16-32 byte key used to authenticate calls to backend scripts.
   *   The key may include alphanumerics and punctuation.
   *   Default: Randomly generated.
   * @see http://wiki.civicrm.org/confluence/display/CRMDOC/Command-line+Script+Configuration
   */
  public $siteKey;

  /**
   * @var string
   *   The data-source for the CMS DB.
   *   Ex: "mysql://user:pass@host:port/db?new_link=true".
   */
  public $ufDsn;

  /**
   * @var string
   *   The base URL of the CMS.
   *   Ex: "http://localhost".
   */
  public $ufBaseUrl;

  /**
   * @var string|NULL
   *   The data-source for the logging DB.
   *   Ex: "mysql://user:pass@host:port/db?new_link=true"
   *   Use NULL to always match the Civi DSN.
   */
  public $loggingDsn;

  /**
   * @var string
   *   Language/locale (e.g. "en_US" or "fr_CA").
   */
  public $lang = self::DEFAULT_LANG;

  /**
   * @var string
   *   The path to the localization files.
   *   Default: $root/l10n.
   */
  public $langDir;

  /**
   * @var bool
   *   Whether to load generated demo data.
   */
  public $loadGenerated;

  /**
   * @param array $arr
   *   List of key-value pairs.
   */
  public function __construct($arr) {
    foreach ($arr as $key => $value) {
      $this->{$key} = $value;
    }
  }

  /**
   * Make a clone of this settings object.
   *
   * @return Settings
   */
  public function copy() {
    return new Settings((array) $this);
  }

  /**
   * Fill in defaults.
   *
   * @return $this
   */
  public function fill() {
    $defaults = array();

    $defaults['lang'] = self::DEFAULT_LANG;

    if ($this->root) {
      // Other things break if langDir does not end in '/'.
      $this->root = rtrim($this->root, '/') . '/';
    }

    if ($this->root) {
      $defaults['langDir'] = $this->root . '/l10n';
    }

    if ($this->dataDir) {
      $defaults['templateCompileDir'] = $this->dataDir . '/templates_c';
    }

    // Generate random siteKey.
    $defaults['siteKey'] = md5(rand() . mt_rand() . rand() . uniqid('', TRUE) . $this->ufBaseUrl);
    // Would prefer openssl_random_pseudo_bytes(), but I don't think it's universally available.

    foreach ($defaults as $key => $value) {
      if (empty($this->{$key})) {
        $this->{$key} = $value;
      }
    }

    if ($this->langDir) {
      // Other things break if langDir does not end in '/'.
      $this->langDir = rtrim($this->langDir, '/') . '/';
    }

    return $this;
  }

  /**
   * Determine if the settings object is generally well-formed.
   *
   * @return array
   *   List of missing values.
   */
  public function validate() {
    $reqs = array(
      'uf',
      'settingsPhp',
      'root',
      'dataDir',
      'templateCompileDir',
      'dsn',
      'siteKey',
      'ufDsn',
      'ufBaseUrl',
    );
    $missing = array();
    foreach ($reqs as $req) {
      if (empty($this->{$req})) {
        $missing[] = $req;
      }
    }
    return $missing;
  }

}

<?php

namespace Civi\Install;

/**
 * Class SettingsFile
 *
 * @package Civi\Install
 */
class SettingsFile {

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
   * Generate the civicrm.settings.php file.
   *
   * @return array
   *   List of messages.
   */
  public function install() {
    $messages = array();

    if (file_exists($this->settings->settingsPhp)) {
      $docLink = \CRM_Utils_System::docURL2('Installation and Upgrades', FALSE, ts('Installation Guide'), NULL, NULL, "wiki");
      $messages[] = array(
        'title' => ts("Oops! CiviCRM is already installed"),
        'details' => ts("CiviCRM has already been installed. <ul><li>To <strong>start over</strong>, you must delete or rename the existing CiviCRM settings file - <strong>civicrm.settings.php</strong> - from <strong>%1</strong>.</li><li>To <strong>upgrade an existing installation</strong>, <a href='%2'>refer to the online documentation</a>.</li></ul>", array(
          1 => dirname($this->settings->settingsPhp),
          2 => $docLink,
        )),
        'severity' => Requirements::REQUIREMENT_WARNING,
      );
    }
    else {
      file_put_contents($this->settings->settingsPhp, $this->renderSettingsFile());
    }

    return $messages;
  }

  /**
   * @return string
   *   Settings file content.
   */
  public function renderSettingsFile() {
    $civiDb = $this->parseDsn($this->settings->dsn);
    $ufDb = $this->parseDsn($this->settings->ufDsn);

    $params = array(
      'crmRoot' => addslashes($this->settings->root),
      'templateCompileDir' => addslashes($this->settings->templateCompileDir),
      'frontEnd' => 0,
      'dbUser' => addslashes($civiDb['username']),
      'dbPass' => addslashes($civiDb['password']),
      'dbHost' => addslashes($civiDb['host']),
      'dbName' => addslashes($civiDb['database']),
      'cms' => $this->settings->uf,
      'CMSdbUser' => addslashes($ufDb['username']),
      'CMSdbPass' => addslashes($ufDb['password']),
      'CMSdbHost' => addslashes($ufDb['host']),
      'CMSdbName' => addslashes($ufDb['database']),
      'baseURL' => $this->settings->ufBaseUrl,
      'siteKey' => $this->settings->siteKey,
    );

    $tplPath = $this->settings->root . '/templates/CRM/common/civicrm.settings.php.template';
    $str = file_get_contents($tplPath);
    foreach ($params as $key => $value) {
      $str = str_replace('%%' . $key . '%%', $value, $str);
    }
    return trim($str);
  }

  /**
   * @param string $dsn
   * @return array
   */
  protected function parseDsn($dsn) {
    $parts = parse_url($dsn);
    $db = array();
    $db['host'] = $parts['host'] . (empty($parts['port']) ? '' : (':' . $parts['port']));
    $db['database'] = trim($parts['path'], '/');
    $db['username'] = $parts['user'];
    $db['password'] = $parts['pass'];
    return $db;
  }

}

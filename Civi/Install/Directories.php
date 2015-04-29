<?php

namespace Civi\Install;

/**
 * Class Directories
 *
 * @package Civi\Install
 */
class Directories {

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

    $dirs = array(
      dirname($this->settings->dataDir),
      $this->settings->dataDir,
      $this->settings->dataDir . DIRECTORY_SEPARATOR . 'templates_c',
    );
    foreach ($dirs as $dir) {
      if (!is_dir($dir)) {
        if (!mkdir($dir, 0777)) {
          $messages = array(
            'title' => ts("Failed to create data directory"),
            'details' => ts("CiviCRM could not create a data folder (<code>%1</code>). Please create it and ensure that the web user has permission to write to it.", array(
              1 => dirname($this->settings->settingsPhp),
            )),
            'severity' => Requirements::REQUIREMENT_ERROR,
          );
        }
      }
    }

    return $messages;
  }
}

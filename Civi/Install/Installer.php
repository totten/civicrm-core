<?php

namespace Civi\Install;

/**
 * Class Installer
 * @package Civi\Install
 *
 * The installer defines a series of installation steps (downloading
 * l10n files, creating a settings file, etc). These steps may be
 * called en masse (install()) or individually.
 *
 * To determine if an installation step failed, check $installer->hasError()
 * and $installer->getMessages().
 *
 * Note: All functions currently return status messages as an
 * array(title=>$,details=>$,severity=>$). These should probably
 * be converted to use CRM_Utils_Check_Message.
 */
class Installer {

  /**
   * @var bool
   */
  protected $booted;

  /**
   * @var array
   */
  protected $messages;

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
    $this->clearMessages();
  }

  /**
   * Configure some global variables.
   */
  public function boot() {
    if ($this->booted) {
      return;
    }
    $this->booted = TRUE;
    if (defined('CIVICRM_UF')) {
      throw new \Exception('CIVICRM_UF should not be defined yet!');
    }
    define('CIVICRM_UF', $this->settings->uf);
    \CRM_Core_Config::singleton(FALSE);
    //$GLOBALS['civicrm_default_error_scope'] = NULL; // What does this do?
  }

  /**
   * Perform a full, automated installation.
   *
   * @throws \Civi\Install\Exception
   */
  public function install() {
    foreach ($this->getSteps() as $step => $label) {
      $this->$step();
      if ($this->hasError()) {
        break;
      }
    }
  }

  /**
   * FIXME: Move to I18n class
   */
  public function configI18n() {
    $this->boot();
    $i18nInstaller = new I18n($this->settings);
    $this->addMessages($i18nInstaller->install());
  }

  public function checkRequirements() {
    $this->boot();
    $requirements = new Requirements($this->settings);
    $this->addMessages($requirements->checkAll());
  }

  /**
   * Download core, packages, vendor, etc.
   */
  public function download() {
    $this->boot();
    // TODO
  }

  /**
   * Create any missing data folders.
   */
  public function createFolders() {
    $this->boot();
    $dirInstaller = new Directories($this->settings);
    $this->addMessages($dirInstaller->install());
  }

  /**
   * Create or update the civicrm.settings.php.
   */
  public function createSettings() {
    $this->boot();
    $settingsInstaller = new SettingsFile($this->settings);
    $this->addMessages($settingsInstaller->install());
  }

  /**
   * Create the SQL schema (if it doesn't exist).
   */
  public function createSchema() {
    $this->boot();
    $schemaInstall = new Schema($this->settings);
    $this->addMessages($schemaInstall->install());
  }

  /**
   * Cleanup any dirty bits.
   */
  public function flush() {
    $c = \CRM_Core_Config::singleton(FALSE);
    $c->free();
  }

  protected function addMessages($messages) {
    $this->messages = array_merge($this->messages, $messages);
    return $this;
  }

  protected function addMessage($message) {
    $this->messages[] = $message;
    return $this;
  }

  /**
   * @return array
   */
  public function getMessages() {
    return $this->messages;
  }

  /**
   * Clear out any messages.
   */
  public function clearMessages() {
    $this->messages = array();
  }

  /**
   * Determine if there are any error messages.
   *
   * @return bool
   */
  public function hasError() {
    foreach ($this->messages as $error) {
      if ($error['severity'] >= Requirements::REQUIREMENT_ERROR) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Get the list of standard installation steps.
   *
   * @return array
   *   Array(string $func => string $label).
   */
  public function getSteps() {
    return array(
      'configI18n' => ts('Internationalize'),
      'checkRequirements' => ts('Check requirements'),
      'createFolders' => ts('Create data folders' ),
      'download' => ts('Download extra files'),
      'createSettings' => ts('Create settings file'),
      'createSchema' => ts('Create SQL schema'),
      'flush' => ts('Flush caches'),
    );
  }

}

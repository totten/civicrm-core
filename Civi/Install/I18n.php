<?php

namespace Civi\Install;

/**
 * Class I18n
 *
 * @package Civi\Install
 */
class I18n {

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
   * FIXME: Attempt to download l10n files.
   *
   * @return array
   *   List of messages.
   */
  public function install() {
    $messages = array();

    if (!$this->isValidLang($this->settings->lang)) {
      $messages[] = array(
        'title' => 'Localization',
        'details' => sprintf('The specified language is not supported (%s). Using default language (%s).',
          preg_replace('/[^a-zA-Z_]/', '_', $this->settings->lang),
          Settings::DEFAULT_LANG
        ),
        'severity' => Requirements::REQUIREMENT_WARNING,
      );
      $this->settings->lang = Settings::DEFAULT_LANG;
    }

    global $tsLocale;
    $tsLocale = $this->settings->lang;

    // The translation files are in the parent directory (l10n)
    $config = \CRM_Core_Config::singleton();
    $config->gettextResourceDir = $this->settings->langDir;
    $config->lcMessages = $this->settings->lang;

    $i18n = \CRM_Core_I18n::singleton();

    return $messages;
  }

  public function getValidLangs() {
    global $langs;
    require_once $this->settings->root . '/install/langs.php';
    return $langs;
  }

  /**
   * Determine if $locale is generally supported by Civi.
   *
   * @param string $locale
   *   Ex: 'en_US'.
   * @return bool
   */
  public function isValidLang($locale) {
    $validLangs = $this->getValidLangs();
    return isset($validLangs[$locale]);
  }

  /**
   * Determine if $locale is specifically supported on this
   * installation (ie whether the data files are available).
   *
   * @param string $locale
   *   Ex: 'en_US'.
   * @return bool
   */
  public function isAvailLang($locale) {
    return file_exists($this->getLocalizedSqlFile($locale));
  }

  /**
   * @param string $locale
   *   Ex: 'en_US'.
   * @return string
   */
  protected function getLocalizedSqlFile($locale) {
    if ($locale == 'en_US') {
      return implode('/', array(
        $this->settings->root,
        "sql",
        "civicrm_data.mysql",
      ));
    }
    else {
      return implode('/', array(
        $this->settings->root,
        "sql",
        "civicrm_data.$locale.mysql",
      ));
    }
  }

}

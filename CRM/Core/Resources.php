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
use Civi\Core\Event\GenericHookEvent;

/**
 * This class facilitates the loading of resources
 * such as JavaScript files and CSS files.
 *
 * Any URLs generated for resources may include a 'cache-code'. By resetting the
 * cache-code, one may force clients to re-download resource files (regardless of
 * any HTTP caching rules).
 *
 * TODO: This is currently a thin wrapper over CRM_Core_Region. We
 * should incorporte services for aggregation, minimization, etc.
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Core_Resources implements CRM_Core_Resources_AdderInterface {
  const DEFAULT_WEIGHT = 0;
  const DEFAULT_REGION = 'page-footer';

  /**
   * We don't have a container or dependency-injection, so use singleton instead
   *
   * @var object
   */
  private static $_singleton = NULL;

  /**
   * @var CRM_Extension_Mapper
   */
  private $extMapper = NULL;

  /**
   * @var CRM_Core_Resources_Strings
   */
  private $strings = NULL;

  /**
   * Added core resources.
   *
   * Format is ($regionName => bool).
   *
   * @var array
   */
  protected $addedCoreResources = [];

  /**
   * Added core styles.
   *
   * Format is ($regionName => bool).
   *
   * @var array
   */
  protected $addedCoreStyles = [];

  /**
   * Added settings.
   *
   * Format is ($regionName => bool).
   *
   * @var array
   */
  protected $addedSettings = [];

  /**
   * A value to append to JS/CSS URLs to coerce cache resets.
   *
   * @var string
   */
  protected $cacheCode = NULL;

  /**
   * The name of a setting which persistently stores the cacheCode.
   *
   * @var string
   */
  protected $cacheCodeKey = NULL;

  /**
   * Are ajax popup screens enabled.
   *
   * @var bool
   */
  public $ajaxPopupsEnabled;

  /**
   * @var \Civi\Core\Paths
   */
  protected $paths;

  /**
   * Get or set the single instance of CRM_Core_Resources.
   *
   * @param CRM_Core_Resources $instance
   *   New copy of the manager.
   *
   * @return CRM_Core_Resources
   */
  public static function singleton(CRM_Core_Resources $instance = NULL) {
    if ($instance !== NULL) {
      self::$_singleton = $instance;
    }
    if (self::$_singleton === NULL) {
      self::$_singleton = Civi::service('resources');
    }
    return self::$_singleton;
  }

  /**
   * Construct a resource manager.
   *
   * @param CRM_Extension_Mapper $extMapper
   *   Map extension names to their base path or URLs.
   * @param CRM_Core_Resources_Strings $strings
   *   JS-localization cache.
   * @param string|null $cacheCodeKey Random code to append to resource URLs; changing the code forces clients to reload resources
   */
  public function __construct($extMapper, $strings, $cacheCodeKey = NULL) {
    $this->extMapper = $extMapper;
    $this->strings = $strings;
    $this->cacheCodeKey = $cacheCodeKey;
    if ($cacheCodeKey !== NULL) {
      $this->cacheCode = Civi::settings()->get($cacheCodeKey);
    }
    if (!$this->cacheCode) {
      $this->resetCacheCode();
    }
    $this->ajaxPopupsEnabled = (bool) Civi::settings()->get('ajaxPopupsEnabled');
    $this->paths = Civi::paths();
  }

  /**
   * Assimilate all the resources listed in a bundle.
   *
   * @param \CRM_Core_Resources_Bundle $bundle
   * @return static
   */
  public function addBundle($bundle) {
    $pickRegion = function ($snippet) {
      if (isset($snippet['settings'])) {
        return $this->getSettingRegion($snippet['region'] ?? NULL)->_name;
      }
      else {
        return $snippet['region'] ?? self::DEFAULT_REGION;
      }
    };

    $byRegion = [];
    foreach ($bundle->getAll() as $snippet) {
      $snippet['region'] = $pickRegion($snippet);
      $byRegion[$snippet['region']][$snippet['name']] = $snippet;
    }

    foreach ($byRegion as $regionName => $snippets) {
      CRM_Core_Region::instance($regionName)->merge($snippets);
    }
    return $this;
  }

  /**
   * Export permission data to the client to enable smarter GUIs.
   *
   * Note: Application security stems from the server's enforcement
   * of the security logic (e.g. in the API permissions). There's no way
   * the client can use this info to make the app more secure; however,
   * it can produce a better-tuned (non-broken) UI.
   *
   * @param array|string $permNames
   *   List of permission names to check/export.
   * @return CRM_Core_Resources
   */
  public function addPermissions($permNames) {
    $this->getSettingRegion()->addPermissions($permNames);
    return $this;
  }

  /**
   * Add an item to the collection.
   *
   * @param array $snippet
   * @return array
   *   The full/computed snippet (with defaults applied).
   * @see CRM_Core_Resources_CollectionInterface::add()
   */
  public function add($snippet) {
    if (!isset($snippet['region'])) {
      throw new \RuntimeException("CRM_Core_Resources::add() - Missing required \"region\".");
    }
    return CRM_Core_Region::instance($snippet['region'])->add($snippet);
  }

  /**
   * Add a JavaScript file to the current page using <SCRIPT SRC>.
   *
   * @param string $ext
   *   extension name; use 'civicrm' for core.
   * @param string $file
   *   file path -- relative to the extension base dir.
   * @param array $options
   *   Options may be a key-value array or a list of positional arguments.
   *   Valid options:
   *     - int $weight (DEFAULT_WEIGHT);
   *       Relative weight within a given region.
   *     - string $region (DEFAULT_REGION)
   *       Location within the output page; 'html-header', 'page-header', 'page-footer'.
   *     - bool|string $translate
   *       Whether to load translated strings for this file. Use one of:
   *       - FALSE: Do not load translated strings.
   *       - TRUE: Load translated strings. Use the $ext's default domain.
   *       - string: Load translated strings. Use a specific domain.
   *
   * @return CRM_Core_Resources
   *
   * @throws \CRM_Core_Exception
   */
  public function addScriptFile(string $ext, string $file, ...$options) {
    $this->add(CRM_Core_Resources_CollectionTrait::mergeOptions($options, [
      'scriptFile' => [$ext, $file],
      'name' => "$ext:$file",
      // Setting the name above may appear superfluous, but it preserves a historical quirk
      // where Region::add() and Resources::addScriptFile() produce slightly different orderings..
    ]));
    return $this;
  }

  /**
   * Add a JavaScript file to the current page using <SCRIPT SRC>.
   *
   * @param string $url
   * @param array $options
   *   Options may be a key-value array or a list of positional arguments.
   *   Valid options:
   *     - int $weight (DEFAULT_WEIGHT);
   *       Relative weight within a given region.
   *     - string $region (DEFAULT_REGION)
   *       Location within the output page; 'html-header', 'page-header', 'page-footer'.
   * @return CRM_Core_Resources
   */
  public function addScriptUrl(string $url, ...$options) {
    $this->add(CRM_Core_Resources_CollectionTrait::mergeOptions($options, [
      'scriptUrl' => $url,
      'name' => $url,
      // Setting the name above may appear superfluous, but it preserves a historical quirk
      // where Region::add() and Resources::addScriptUrl() produce slightly different orderings..
    ]));
    return $this;
  }

  /**
   * Add a JavaScript file to the current page using <SCRIPT SRC>.
   *
   * @param string $code
   *   JavaScript source code.
   * @param array $options
   *   Options may be a key-value array or a list of positional arguments.
   *   Valid options:
   *     - int $weight (DEFAULT_WEIGHT);
   *       Relative weight within a given region.
   *     - string $region (DEFAULT_REGION)
   *       Location within the output page; 'html-header', 'page-header', 'page-footer'.
   * @return CRM_Core_Resources
   */
  public function addScript(string $code, ...$options) {
    $this->add(CRM_Core_Resources_CollectionTrait::mergeOptions($options, [
      'script' => $code,
    ]));
    return $this;
  }

  /**
   * Add JavaScript variables to CRM.vars
   *
   * Example:
   * From the server:
   * CRM_Core_Resources::singleton()->addVars('myNamespace', array('foo' => 'bar'));
   * Access var from javascript:
   * CRM.vars.myNamespace.foo // "bar"
   *
   * @see https://docs.civicrm.org/dev/en/latest/standards/javascript/
   *
   * @param string $nameSpace
   *   Usually the name of your extension.
   * @param array $vars
   * @param string $region
   *   The region to add settings to (eg. for payment processors usually billing-block)
   *
   * @return CRM_Core_Resources
   */
  public function addVars(string $nameSpace, array $vars, $region = NULL) {
    $this->getSettingRegion($region)->addVars($nameSpace, $vars);
    return $this;
  }

  /**
   * Add JavaScript variables to the root of the CRM object.
   * This function is usually reserved for low-level system use.
   * Extensions and components should generally use addVars instead.
   *
   * @param array $settings
   * @param string $region
   *   The region to add settings to (eg. for payment processors usually billing-block)
   *
   * @return CRM_Core_Resources
   */
  public function addSetting(array $settings, $region = NULL) {
    $this->getSettingRegion($region)->addSetting($settings);
    return $this;
  }

  /**
   * Add JavaScript variables to the global CRM object via a callback function.
   *
   * @param callable $callable
   * @return CRM_Core_Resources
   */
  public function addSettingsFactory($callable) {
    $this->getSettingRegion()->addSettingsFactory($callable);
    return $this;
  }

  /**
   * Helper fn for addSettingsFactory.
   */
  public function getSettings() {
    return $this->getSettingRegion()->getSettings();
  }

  /**
   * Add translated string to the js CRM object.
   * It can then be retrived from the client-side ts() function
   * Variable substitutions can happen from client-side
   *
   * Note: this function rarely needs to be called directly and is mostly for internal use.
   * See CRM_Core_Resources::addScriptFile which automatically adds translated strings from js files
   *
   * Simple example:
   * // From php:
   * CRM_Core_Resources::singleton()->addString('Hello');
   * // The string is now available to javascript code i.e.
   * ts('Hello');
   *
   * Example with client-side substitutions:
   * // From php:
   * CRM_Core_Resources::singleton()->addString('Your %1 has been %2');
   * // ts() in javascript works the same as in php, for example:
   * ts('Your %1 has been %2', {1: objectName, 2: actionTaken});
   *
   * NOTE: This function does not work with server-side substitutions
   * (as this might result in collisions and unwanted variable injections)
   * Instead, use code like:
   * CRM_Core_Resources::singleton()->addSetting(array('myNamespace' => array('myString' => ts('Your %1 has been %2', array(subs)))));
   * And from javascript access it at CRM.myNamespace.myString
   *
   * @param string|array $text
   * @param string|null $domain
   * @return CRM_Core_Resources
   */
  public function addString($text, $domain = 'civicrm') {
    $this->getSettingRegion()->addString($text, $domain);
    return $this;
  }

  /**
   * Add a CSS file to the current page using <LINK HREF>.
   *
   * @param string $ext
   *   extension name; use 'civicrm' for core.
   * @param string $file
   *   file path -- relative to the extension base dir.
   * @param array $options
   *   Options may be a key-value array or a list of positional arguments.
   *   Valid options:
   *     - int $weight (DEFAULT_WEIGHT);
   *       Relative weight within a given region.
   *     - string $region (DEFAULT_REGION)
   *       Location within the output page; 'html-header', 'page-header', 'page-footer'.
   * @return CRM_Core_Resources
   */
  public function addStyleFile(string $ext, string $file, ...$options) {
    $this->add(CRM_Core_Resources_CollectionTrait::mergeOptions($options, [
      'styleFile' => [$ext, $file],
      'name' => "$ext:$file",
      // Setting the name above may appear superfluous, but it preserves a historical quirk
      // where Region::add() and Resources::addScriptUrl() produce slightly different orderings..
    ]));
    return $this;
  }

  /**
   * Add a CSS file to the current page using <LINK HREF>.
   *
   * @param string $url
   * @param array $options
   *   Options may be a key-value array or a list of positional arguments.
   *   Valid options:
   *     - int $weight (DEFAULT_WEIGHT);
   *       Relative weight within a given region.
   *     - string $region (DEFAULT_REGION)
   *       Location within the output page; 'html-header', 'page-header', 'page-footer'.
   * @return CRM_Core_Resources
   */
  public function addStyleUrl(string $url, ...$options) {
    $this->add(CRM_Core_Resources_CollectionTrait::mergeOptions($options, [
      'styleUrl' => $url,
      'name' => $url,
      // Setting the name above may appear superfluous, but it preserves a historical quirk
      // where Region::add() and Resources::addScriptUrl() produce slightly different orderings..
    ]));
    return $this;
  }

  /**
   * Add a CSS content to the current page using <STYLE>.
   *
   * @param string $code
   *   CSS source code.
   * @param array $options
   *   Options may be a key-value array or a list of positional arguments.
   *   Valid options:
   *     - int $weight (DEFAULT_WEIGHT);
   *       Relative weight within a given region.
   *     - string $region (DEFAULT_REGION)
   *       Location within the output page; 'html-header', 'page-header', 'page-footer'.
   * @return CRM_Core_Resources
   */
  public function addStyle(string $code, ...$options) {
    $this->add(CRM_Core_Resources_CollectionTrait::mergeOptions($options, [
      'style' => $code,
    ]));
    return $this;
  }

  /**
   * Determine file path of a resource provided by an extension.
   *
   * @param string $ext
   *   extension name; use 'civicrm' for core.
   * @param string|null $file
   *   file path -- relative to the extension base dir.
   *
   * @return bool|string
   *   full file path or FALSE if not found
   */
  public function getPath($ext, $file = NULL) {
    // TODO consider caching results
    $base = $this->paths->hasVariable($ext)
      ? rtrim($this->paths->getVariable($ext, 'path'), '/')
      : $this->extMapper->keyToBasePath($ext);
    if ($file === NULL) {
      return $base;
    }
    $path = $base . '/' . $file;
    if (is_file($path)) {
      return $path;
    }
    return FALSE;
  }

  /**
   * Determine public URL of a resource provided by an extension.
   *
   * @param string $ext
   *   extension name; use 'civicrm' for core.
   * @param string $file
   *   file path -- relative to the extension base dir.
   * @param bool $addCacheCode
   *
   * @return string, URL
   */
  public function getUrl($ext, $file = NULL, $addCacheCode = FALSE) {
    if ($file === NULL) {
      $file = '';
    }
    if ($addCacheCode) {
      $file = $this->addCacheCode($file);
    }
    // TODO consider caching results
    $base = $this->paths->hasVariable($ext)
      ? $this->paths->getVariable($ext, 'url')
      : ($this->extMapper->keyToUrl($ext) . '/');
    return $base . $file;
  }

  /**
   * Evaluate a glob pattern in the context of a particular extension.
   *
   * @param string $ext
   *   Extension name; use 'civicrm' for core.
   * @param string|array $patterns
   *   Glob pattern; e.g. "*.html".
   * @param null|int $flags
   *   See glob().
   * @return array
   *   List of matching files, relative to the extension base dir.
   * @see glob()
   */
  public function glob($ext, $patterns, $flags = NULL) {
    $path = $this->getPath($ext);
    $patterns = (array) $patterns;
    $files = [];
    foreach ($patterns as $pattern) {
      if (preg_match(';^(assetBuilder|ext)://;', $pattern)) {
        $files[] = $pattern;
      }
      if (CRM_Utils_File::isAbsolute($pattern)) {
        // Absolute path.
        $files = array_merge($files, (array) glob($pattern, $flags));
      }
      else {
        // Relative path.
        $files = array_merge($files, (array) glob("$path/$pattern", $flags));
      }
    }
    // Deterministic order.
    sort($files);
    $files = array_unique($files);
    return array_map(function ($file) use ($path) {
      return CRM_Utils_File::relativize($file, "$path/");
    }, $files);
  }

  /**
   * @return string
   */
  public function getCacheCode() {
    return $this->cacheCode;
  }

  /**
   * @param $value
   * @return CRM_Core_Resources
   */
  public function setCacheCode($value) {
    $this->cacheCode = $value;
    if ($this->cacheCodeKey) {
      Civi::settings()->set($this->cacheCodeKey, $value);
    }
    return $this;
  }

  /**
   * @return CRM_Core_Resources
   */
  public function resetCacheCode() {
    $this->setCacheCode(CRM_Utils_String::createRandom(5, CRM_Utils_String::ALPHANUMERIC));
    // Also flush cms resource cache if needed
    CRM_Core_Config::singleton()->userSystem->clearResourceCache();
    return $this;
  }

  /**
   * This adds CiviCRM's standard css and js to the specified region of the document.
   * It will only run once.
   *
   * @param string $region
   * @return CRM_Core_Resources
   */
  public function addCoreResources($region = 'html-header') {
    // TODO: The addBundle() call has merge/dedupe behavior, so maybe the guard could be simpler. I'm just not sure how to validate the change.
    if (!isset($this->addedCoreResources[$region]) && !self::isAjaxMode()) {
      $this->addedCoreResources[$region] = TRUE;
      $this->addBundle(CRM_Core_Resources_Common::createFullBundle($region));
      $this->addCoreStyles($region);
    }
    return $this;
  }

  /**
   * This will add CiviCRM's standard CSS
   *
   * @param string $region
   * @return CRM_Core_Resources
   */
  public function addCoreStyles($region = 'html-header') {
    // TODO: The addBundle() call has merge/dedupe behavior, so maybe the guard could be removed. I'm just not sure how to validate the change.
    if (!isset($this->addedCoreStyles[$region])) {
      $this->addedCoreStyles[$region] = TRUE;
      $this->addBundle(CRM_Core_Resources_Common::createStyleBundle($region));
    }
    return $this;
  }

  /**
   * Flushes cached translated strings.
   * @return CRM_Core_Resources
   */
  public function flushStrings() {
    $this->strings->flush();
    return $this;
  }

  /**
   * @return CRM_Core_Resources_Strings
   */
  public function getStrings() {
    return $this->strings;
  }

  /**
   * Create dynamic script for localizing js widgets.
   */
  public static function outputLocalizationJS() {
    CRM_Core_Page_AJAX::setJsHeaders();
    $config = CRM_Core_Config::singleton();
    $vars = [
      'moneyFormat' => json_encode(CRM_Utils_Money::format(1234.56)),
      'contactSearch' => json_encode($config->includeEmailInName ? ts('Start typing a name or email...') : ts('Start typing a name...')),
      'otherSearch' => json_encode(ts('Enter search term...')),
      'entityRef' => self::getEntityRefMetadata(),
      'ajaxPopupsEnabled' => self::singleton()->ajaxPopupsEnabled,
      'allowAlertAutodismissal' => (bool) Civi::settings()->get('allow_alert_autodismissal'),
      'resourceCacheCode' => self::singleton()->getCacheCode(),
      'locale' => CRM_Core_I18n::getLocale(),
      'cid' => (int) CRM_Core_Session::getLoggedInContactID(),
    ];
    print CRM_Core_Smarty::singleton()->fetchWith('CRM/common/l10n.js.tpl', $vars);
    CRM_Utils_System::civiExit();
  }

  /**
   * @return bool
   *   is this page request an ajax snippet?
   */
  public static function isAjaxMode() {
    if (in_array(CRM_Utils_Array::value('snippet', $_REQUEST), [
      CRM_Core_Smarty::PRINT_SNIPPET,
      CRM_Core_Smarty::PRINT_NOFORM,
      CRM_Core_Smarty::PRINT_JSON,
    ])
    ) {
      return TRUE;
    }
    list($arg0, $arg1) = array_pad(explode('/', CRM_Utils_System::currentPath()), 2, '');
    return ($arg0 === 'civicrm' && in_array($arg1, ['ajax', 'angularprofiles', 'asset']));
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::buildAsset()
   */
  public static function renderMenubarStylesheet(GenericHookEvent $e) {
    if ($e->asset !== 'crm-menubar.css') {
      return;
    }
    $e->mimeType = 'text/css';
    $content = '';
    $config = CRM_Core_Config::singleton();
    $cms = strtolower($config->userFramework);
    $cms = $cms === 'drupal' ? 'drupal7' : $cms;
    $items = [
      'bower_components/smartmenus/dist/css/sm-core-css.css',
      'css/crm-menubar.css',
      "css/menubar-$cms.css",
    ];
    foreach ($items as $item) {
      $content .= file_get_contents(self::singleton()->getPath('civicrm', $item));
    }
    $params = $e->params;
    // "color" is deprecated in favor of the more specific "menubarColor"
    $menubarColor = $params['color'] ?? $params['menubarColor'];
    $vars = [
      '$resourceBase' => rtrim($config->resourceBase, '/'),
      '$menubarHeight' => $params['height'] . 'px',
      '$breakMin' => $params['breakpoint'] . 'px',
      '$breakMax' => ($params['breakpoint'] - 1) . 'px',
      '$menubarColor' => $menubarColor,
      '$menuItemColor' => $params['menuItemColor'] ?? $menubarColor,
      '$highlightColor' => $params['highlightColor'] ?? CRM_Utils_Color::getHighlight($menubarColor),
      '$textColor' => $params['textColor'] ?? CRM_Utils_Color::getContrast($menubarColor, '#333', '#ddd'),
    ];
    $vars['$highlightTextColor'] = $params['highlightTextColor'] ?? CRM_Utils_Color::getContrast($vars['$highlightColor'], '#333', '#ddd');
    $e->content = str_replace(array_keys($vars), array_values($vars), $content);
  }

  /**
   * Provide a list of available entityRef filters.
   *
   * @return array
   */
  public static function getEntityRefMetadata() {
    $data = [
      'filters' => [],
      'links' => [],
    ];
    $config = CRM_Core_Config::singleton();

    $disabledComponents = [];
    $dao = CRM_Core_DAO::executeQuery("SELECT name, namespace FROM civicrm_component");
    while ($dao->fetch()) {
      if (!in_array($dao->name, $config->enableComponents)) {
        $disabledComponents[$dao->name] = $dao->namespace;
      }
    }

    foreach (CRM_Core_DAO_AllCoreTables::daoToClass() as $entity => $daoName) {
      // Skip DAOs of disabled components
      foreach ($disabledComponents as $nameSpace) {
        if (strpos($daoName, $nameSpace) === 0) {
          continue 2;
        }
      }
      $baoName = str_replace('_DAO_', '_BAO_', $daoName);
      if (class_exists($baoName)) {
        $filters = $baoName::getEntityRefFilters();
        if ($filters) {
          $data['filters'][$entity] = $filters;
        }
        if (is_callable([$baoName, 'getEntityRefCreateLinks'])) {
          $createLinks = $baoName::getEntityRefCreateLinks();
          if ($createLinks) {
            $data['links'][$entity] = $createLinks;
          }
        }
      }
    }

    CRM_Utils_Hook::entityRefFilters($data['filters'], $data['links']);

    return $data;
  }

  /**
   * Determine the minified file name.
   *
   * @param string $ext
   * @param string $file
   * @return string
   *   An updated $fileName. If a minified version exists and is supported by
   *   system policy, the minified version will be returned. Otherwise, the original.
   */
  public function filterMinify($ext, $file) {
    if (CRM_Core_Config::singleton()->debug && strpos($file, '.min.') !== FALSE) {
      $nonMiniFile = str_replace('.min.', '.', $file);
      if ($this->getPath($ext, $nonMiniFile)) {
        $file = $nonMiniFile;
      }
    }
    return $file;
  }

  /**
   * @param string $url
   * @return string
   */
  public function addCacheCode($url) {
    $hasQuery = strpos($url, '?') !== FALSE;
    $operator = $hasQuery ? '&' : '?';

    return $url . $operator . 'r=' . $this->cacheCode;
  }

  /**
   * Checks if the given URL is fully-formed
   *
   * @param string $url
   *
   * @return bool
   */
  public static function isFullyFormedUrl($url) {
    return (substr($url, 0, 4) === 'http') || (substr($url, 0, 1) === '/');
  }

  /**
   * @param string|NULL $region
   *   Optional request for a specific region. If NULL/omitted, use global default.
   * @return \CRM_Core_Region
   */
  private function getSettingRegion($region = NULL) {
    $region = $region ?: (self::isAjaxMode() ? 'ajax-snippet' : 'html-header');
    return CRM_Core_Region::instance($region);
  }

}

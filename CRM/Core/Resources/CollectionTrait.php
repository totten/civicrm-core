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

/**
 * Class CRM_Core_Resources_CollectionTrait
 *
 * This is a building-block for creating classes which maintain a list of resources.
 *
 * The class is generally organized in two sections: First, we have core
 * bit that manages a list of '$snippets'. Second, we have a set of helper
 * functions which add some syntactic sugar for the snippets.
 */
trait CRM_Core_Resources_CollectionTrait {

  /**
   * @var int
   */
  protected $defaultWeight = 1;

  /**
   * List of snippets to inject within region.
   *
   * e.g. $this->_snippets[3]['type'] = 'template';
   *
   * @var array
   */
  protected $snippets = [];

  /**
   * Whether the snippets array has been sorted
   *
   * @var bool
   */
  protected $isSorted = TRUE;

  /**
   * Whitelist of supported types.
   *
   * @var array
   */
  protected $types = [];

  /**
   * Add an item to the collection. For example, when working with 'page-header' collection:
   *
   * ```
   * CRM_Core_Region::instance('page-header')->add(array(
   *   'markup' => '<div style="color:red">Hello!</div>',
   * ));
   * CRM_Core_Region::instance('page-header')->add(array(
   *   'script' => 'alert("Hello");',
   * ));
   * CRM_Core_Region::instance('page-header')->add(array(
   *   'template' => 'CRM/Myextension/Extra.tpl',
   * ));
   * CRM_Core_Region::instance('page-header')->add(array(
   *   'callback' => 'myextension_callback_function',
   * ));
   * ```
   *
   * Note: This function does not perform any extra encoding of markup, script code, or etc. If
   * you're passing in user-data, you must clean it yourself.
   *
   * @param array $snippet
   *   Array; keys:.
   *   - type: string (auto-detected for markup, template, callback, script, scriptUrl, jquery, style, styleUrl)
   *   - name: string, optional
   *   - weight: int, optional; default=1
   *   - disabled: int, optional; default=0
   *   - markup: string, HTML; required (for type==markup)
   *   - template: string, path; required (for type==template)
   *   - callback: mixed; required (for type==callback)
   *   - arguments: array, optional (for type==callback)
   *   - script: string, Javascript code
   *   - scriptUrl: string, URL of a Javascript file
   *   - jquery: string, Javascript code which runs inside a jQuery(function($){...}); block
   *   - settings: array, list of static values to convey.
   *   - style: string, CSS code
   *   - styleUrl: string, URL of a CSS file
   *
   * @return array
   *   The full/computed snippet (with defaults applied).
   */
  public function add($snippet) {
    $snippet += ['disabled' => FALSE];
    if (!isset($snippet['weight'])) {
      $snippet['weight'] = $this->defaultWeight;
    }

    if (!isset($snippet['type'])) {
      foreach ($this->types as $type) {
        // auto-detect
        if (isset($snippet[$type])) {
          $snippet['type'] = $type;
          break;
        }
      }
    }
    elseif (!in_array($snippet['type'], $this->types)) {
      throw new \RuntimeException("Unsupported snippet type: " . $snippet['type']);
    }
    if (!isset($snippet['name'])) {
      $snippet['name'] = count($this->snippets);
    }

    $this->snippets[$snippet['name']] = $snippet;
    $this->isSorted = FALSE;
    return $snippet;
  }

  /**
   * @param string $name
   * @param $snippet
   */
  public function update($name, $snippet) {
    $this->snippets[$name] = array_merge($this->snippets[$name], $snippet);
    $this->isSorted = FALSE;
  }

  /**
   * Get snippet.
   *
   * @param string $name
   * @return array|NULL
   */
  public function &get($name) {
    return $this->snippets[$name];
  }

  /**
   * Get a list of all snippets in this collection.
   *
   * @return array
   */
  public function getAll() {
    $this->sort();
    return $this->snippets;
  }

  /**
   * Alter the contents of the collection.
   *
   * @param callable $callback
   *   The callback is invoked once for each member in the collection.
   *   The callback may return one of three values:
   *   - TRUE: The item is OK and belongs in the collection.
   *   - FALSE: The item is not OK and should be omitted from the collection.
   *   - Array: The item should be revised (using the returned value).
   * @return static
   */
  public function filter($callback) {
    $this->sort();
    $names = array_keys($this->snippets);
    foreach ($names as $name) {
      $ret = $callback($this->snippets[$name]);
      if ($ret === TRUE) {
        // OK
      }
      elseif ($ret === FALSE) {
        unset($this->snippets[$name]);
      }
      elseif (is_array($ret)) {
        $this->snippets[$name] = $ret;
        $this->isSorted = FALSE;
      }
      else {
        throw new \RuntimeException("CollectionTrait::filter() - Callback returned invalid value");
      }
    }
    return $this;
  }

  /**
   * Find all snippets which match the given criterion.
   *
   * @param callable $callback
   * @return array
   *   List of matching snippets.
   */
  public function find($callback) {
    $r = [];
    $this->sort();
    foreach ($this->snippets as $name => $snippet) {
      if ($callback($snippet)) {
        $r[$name] = $snippet;
      }
    }
    return $r;
  }

  /**
   * Ensure that the collection is sorted.
   *
   * @return static
   */
  protected function sort() {
    if (!$this->isSorted) {
      uasort($this->snippets, [__CLASS__, '_cmpSnippet']);
      $this->isSorted = TRUE;
    }
    return $this;
  }

  /**
   * @param $a
   * @param $b
   *
   * @return int
   */
  public static function _cmpSnippet($a, $b) {
    if ($a['weight'] < $b['weight']) {
      return -1;
    }
    if ($a['weight'] > $b['weight']) {
      return 1;
    }
    // fallback to name sort; don't really want to do this, but it makes results more stable
    if ($a['name'] < $b['name']) {
      return -1;
    }
    if ($a['name'] > $b['name']) {
      return 1;
    }
    return 0;
  }

  // -----------------------------------------------

  /**
   * Add a JavaScript file to the current page using <SCRIPT SRC>.
   *
   * @param string $code
   *   JavaScript source code.
   * @param int $weight
   *   relative weight within a given region.
   * @return static
   */
  public function addScript($code, $weight = NULL) {
    $this->add([
      'type' => 'script',
      'script' => $code,
      'weight' => $weight,
    ]);
    return $this;
  }

  /**
   * Add a JavaScript file to the current page using <SCRIPT SRC>.
   *
   * @param string $url
   * @param int $weight
   *   relative weight within a given region.
   * @return static
   */
  public function addScriptUrl($url, $weight = NULL) {
    $this->add([
      'name' => $url,
      'type' => 'scriptUrl',
      'scriptUrl' => $url,
      'weight' => $weight,
    ]);
    return $this;
  }

  /**
   * Add a CSS content to the current page using <STYLE>.
   *
   * @param string $code
   *   CSS source code.
   * @param int|NULL $weight
   *   relative weight within a given region.
   * @return static
   */
  public function addStyle($code, $weight = NULL) {
    $this->add([
      'type' => 'style',
      'style' => $code,
      'weight' => $weight,
    ]);
    return $this;
  }

  /**
   * Add a CSS file to the current page using <LINK HREF>.
   *
   * @param string $url
   * @param int $weight
   *   relative weight within a given region.
   * @return static
   */
  public function addStyleUrl($url, $weight = NULL) {
    $this->add([
      'name' => $url,
      'type' => 'styleUrl',
      'styleUrl' => $url,
      'weight' => $weight,
    ]);
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
   * @return static
   */
  public function addVars($nameSpace, $vars, $region = NULL) {
    $s = &$this->findCreateSettingSnippet($region);
    $s['settings']['vars'][$nameSpace] = $this->mergeSettings(
      $s['settings']['vars'][$nameSpace] ?? [],
      $vars
    );
    return $this;
  }

  /**
   * Add JavaScript variables to the root of the CRM object.
   * This function is usually reserved for low-level system use.
   * Extensions and components should generally use addVars instead.
   *
   * @param array $settings
   * @return static
   */
  public function addSetting($settings) {
    $s = &$this->findCreateSettingSnippet();
    $s['settings'] = $this->mergeSettings($s['settings'], $settings);
    return $this;
  }

  /**
   * Add JavaScript variables to the global CRM object via a callback function.
   *
   * @param callable $callable
   * @return static
   */
  public function addSettingsFactory($callable) {
    $s = &$this->findCreateSettingSnippet();
    $s['settingsFactories'][] = $callable;
    return $this;
  }

  /**
   * Get a fully-formed/altered list of settings, including the results of
   * any callbacks/listeners.
   *
   * @return array
   */
  public function getSettings() {
    $s = &$this->findCreateSettingSnippet();
    $result = $s['settings'];
    foreach ($s['settingsFactories'] as $callable) {
      $result = $this->mergeSettings($result, $callable());
    }
    CRM_Utils_Hook::alterResourceSettings($result);
    return $result;
  }

  /**
   * @param array $settings
   * @param array $additions
   * @return array
   *   combination of $settings and $additions
   */
  private function mergeSettings($settings, $additions) {
    foreach ($additions as $k => $v) {
      if (isset($settings[$k]) && is_array($settings[$k]) && is_array($v)) {
        $v += $settings[$k];
      }
      $settings[$k] = $v;
    }
    return $settings;
  }

  /**
   * @return array
   */
  private function &findCreateSettingSnippet() {
    $snippet = &$this->get('settings');
    if ($snippet !== NULL) {
      return $snippet;
    }

    $this->add([
      'name' => 'settings',
      'type' => 'settings',
      'settings' => [],
      'settingsFactories' => [],
      'weight' => -100000,
    ]);
    return $this->get('settings');
  }

}

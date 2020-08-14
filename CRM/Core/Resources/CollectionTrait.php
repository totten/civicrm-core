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
 */
trait CRM_Core_Resources_CollectionTrait {

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
    $defaults = [
      'weight' => 1,
      'disabled' => FALSE,
    ];
    $snippet += $defaults;
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

}

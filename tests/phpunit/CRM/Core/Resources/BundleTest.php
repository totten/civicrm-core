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
 * Tests for manipulating bundles
 * @group headless
 * @group resources
 */
class CRM_Core_Resources_BundleTest extends CiviUnitTestCase {

  public function getSnippetExamples() {
    $es = [];

    /**
     * Private helper to generate several similar examples.
     * @param array $callbacks
     *   List of callbacks which can be used to add a resource to the bundle.
     * @param array $expect
     *   The fully formed resource that should be created as a result.
     */
    $addCases = function($callbacks, $expect) use (&$es) {
      foreach ($callbacks as $key => $callback) {
        if (isset($es[$key])) {
          throw new \RuntimeException("Cannot prepare examples: Case \"$key\" defined twice");
        }
        $es[$key] = [$callback, $expect];
      }
    };

    $addCases(
      // List of equivalent method calls
      [
        'add(scriptUrl): dfl' => ['add', ['scriptUrl' => 'http://example.com/foo.js']],
        'addScriptUrl(): dfl' => ['addScriptUrl', 'http://example.com/foo.js'],
        'addScriptUrl(): pos dfl-wgt' => ['addScriptUrl', 'http://example.com/foo.js', 1],
      ],
      // Fully-formed result expected for this call
      [
        'name' => 'http://example.com/foo.js',
        'disabled' => FALSE,
        'weight' => 1,
        'sortId' => 1,
        'type' => 'scriptUrl',
        'scriptUrl' => 'http://example.com/foo.js',
      ]
    );

    $addCases(
      [
        'add(scriptUrl): wgt' => ['add', ['scriptUrl' => 'http://example.com/foo.js', 'weight' => 100]],
        'addScriptUrl(): arr wgt' => ['addScriptUrl', 'http://example.com/foo.js', ['weight' => 100]],
        'addScriptUrl(): pos wgt' => ['addScriptUrl', 'http://example.com/foo.js', 100],
      ],
      [
        'name' => 'http://example.com/foo.js',
        'disabled' => FALSE,
        'weight' => 100,
        'sortId' => 1,
        'type' => 'scriptUrl',
        'scriptUrl' => 'http://example.com/foo.js',
      ]
    );

    $addCases(
      [
        'add(styleUrl)' => ['add', ['styleUrl' => 'http://example.com/foo.css']],
        'addStyleUrl()' => ['addStyleUrl', 'http://example.com/foo.css'],
      ],
      [
        'name' => 'http://example.com/foo.css',
        'disabled' => FALSE,
        'weight' => 1,
        'sortId' => 1,
        'type' => 'styleUrl',
        'styleUrl' => 'http://example.com/foo.css',
      ]
    );

    $addCases(
      [
        'add(styleFile)' => ['add', ['styleFile' => ['civicrm', 'css/civicrm.css']]],
        'addStyleFile()' => ['addStyleFile', 'civicrm', 'css/civicrm.css'],
      ],
      [
        'name' => 'civicrm:css/civicrm.css',
        'disabled' => FALSE,
        'weight' => 1,
        'sortId' => 1,
        'type' => 'styleFile',
        'styleFile' => ['civicrm', 'css/civicrm.css'],
        'styleFileUrls' => [
          Civi::paths()->getUrl('[civicrm.root]/css/civicrm.css?r=XXXX'),
        ],
      ]
    );

    $basicFooJs = [
      'name' => 'civicrm:js/foo.js',
      'disabled' => FALSE,
      'sortId' => 1,
      'type' => 'scriptFile',
      'scriptFile' => ['civicrm', 'js/foo.js'],
      'scriptFileUrls' => [
        Civi::paths()->getUrl('[civicrm.root]/js/foo.js?r=XXXX'),
      ],
    ];

    $addCases(
      [
        'add(scriptFile): dfl' => ['add', ['scriptFile' => ['civicrm', 'js/foo.js']]],
        'addScriptFile(): dfl' => ['addScriptFile', 'civicrm', 'js/foo.js'],
        'addScriptFile(): dfl pos-wgt' => ['addScriptFile', 'civicrm', 'js/foo.js', 1],
      ],
      $basicFooJs + ['weight' => 1, 'translate' => TRUE]
    );

    $addCases(
      [
        'add(scriptFile): wgt-rgn' => ['add', ['scriptFile' => ['civicrm', 'js/foo.js'], 'weight' => 100, 'region' => 'zoo']],
        'addScriptFile(): arr wgt-rgn' => ['addScriptFile', 'civicrm', 'js/foo.js', ['weight' => 100, 'region' => 'zoo']],
        'addScriptFile(): pos wgt-rgn' => ['addScriptFile', 'civicrm', 'js/foo.js', 100, 'zoo'],
        'addScriptFile(): pos wgt-rgn-trn' => ['addScriptFile', 'civicrm', 'js/foo.js', 100, 'zoo', TRUE],
      ],
      $basicFooJs + ['weight' => 100, 'region' => 'zoo', 'translate' => TRUE]
    );

    $addCases(
      [
        'add(scriptFile): wgt-rgn-trnOff' => ['add', ['scriptFile' => ['civicrm', 'js/foo.js'], 'weight' => -200, 'region' => 'zoo', 'translate' => FALSE]],
        'addScriptFile(): arr wgt-rgn-trnOff' => ['addScriptFile', 'civicrm', 'js/foo.js', ['weight' => -200, 'region' => 'zoo', 'translate' => FALSE]],
        'addScriptFile(): pos wgt-rgn-trnOff' => ['addScriptFile', 'civicrm', 'js/foo.js', -200, 'zoo', FALSE],
      ],
      $basicFooJs + ['weight' => -200, 'region' => 'zoo', 'translate' => FALSE]
    );

    $addCases(
      [
        'add(script)' => ['add', ['script' => 'window.alert("Boo!");']],
        'addScript()' => ['addScript', 'window.alert("Boo!");'],
      ],
      [
        'name' => 1,
        'disabled' => FALSE,
        'weight' => 1,
        'sortId' => 1,
        'type' => 'script',
        'script' => 'window.alert("Boo!");',
      ]
    );

    return $es;
  }

  /**
   * Add a snippet with some method and ensure that it's actually added.
   *
   * @param array $callbackArgs
   *   Ex: ['addScriptUrl', 'http://example.com/foo.js'].
   * @param array $expectSnippet
   * @dataProvider getSnippetExamples
   */
  public function testAddDefaults($callbackArgs, $expectSnippet) {
    if ($callbackArgs === NULL) {
      return;
    }
    $method = array_shift($callbackArgs);

    $b = new CRM_Core_Resources_Bundle();
    $result = call_user_func_array([$b, $method], $callbackArgs);

    // Check direct result.
    if ($method === 'add') {
      $this->assertSameSnippet($expectSnippet, $result);
    }
    else {
      $this->assertTrue($b === $result);
    }

    // Check side-effect of registering snippet.
    $count = 0;
    foreach ($b->getAll() as $getSnippet) {
      $this->assertSameSnippet($expectSnippet, $getSnippet, 'getAll() method should return snippet with properly computed defaults');
      $count++;
    }
    $this->assertEquals(1, $count, 'Expect one registered snippet');
  }

  /**
   * Create two bundles (parent, child) - and merge the child into the parent.
   */
  public function testMergeBundles() {
    $child = new CRM_Core_Resources_Bundle();
    $parent = new CRM_Core_Resources_Bundle();

    $child->addScriptUrl('http://example.com/child.js');
    $child->addStyleUrl('http://example.com/child.css');
    $child->addSetting(['child' => ['schoolbooks']]);
    $this->assertCount(3, $child->getAll());

    $parent->addScriptUrl('http://example.com/parent.js');
    $parent->addStyleUrl('http://example.com/parent.css');
    $parent->addSetting(['parent' => ['groceries']]);
    $this->assertCount(3, $parent->getAll());

    $parent->merge($child->getAll());
    $this->assertCount(5, $parent->getAll());

    $expectSettings = [
      'child' => ['schoolbooks'],
      'parent' => ['groceries'],
    ];
    $this->assertEquals($expectSettings, $parent->getSettings());
    $this->assertEquals('http://example.com/child.js', $parent->get('http://example.com/child.js')['scriptUrl']);
    $this->assertEquals('http://example.com/child.css', $parent->get('http://example.com/child.css')['styleUrl']);
    $this->assertEquals('http://example.com/parent.js', $parent->get('http://example.com/parent.js')['scriptUrl']);
    $this->assertEquals('http://example.com/parent.css', $parent->get('http://example.com/parent.css')['styleUrl']);
  }

  /**
   * Create two bundles (parent, child) - and merge the child into the parent.
   */
  public function testMergeIntoRegion() {
    $bundle = new CRM_Core_Resources_Bundle();
    $region = CRM_Core_Region::instance(__FUNCTION__);

    $bundle->addScriptUrl('http://example.com/bundle.js');
    $bundle->addStyleUrl('http://example.com/bundle.css');
    $bundle->addSetting(['child' => ['schoolbooks']]);
    $this->assertCount(3, $bundle->getAll());

    $region->addScriptUrl('http://example.com/region.js');
    $region->addStyleUrl('http://example.com/region.css');
    $region->addSetting(['region' => ['groceries']]);
    $this->assertCount(3 + 1 /* default */, $region->getAll());

    $region->merge($bundle->getAll());
    $this->assertCount(5 + 1 /* default */, $region->getAll());

    $expectSettings = [
      'child' => ['schoolbooks'],
      'region' => ['groceries'],
    ];
    $this->assertEquals($expectSettings, $region->getSettings());
    $this->assertEquals('http://example.com/bundle.js', $region->get('http://example.com/bundle.js')['scriptUrl']);
    $this->assertEquals('http://example.com/bundle.css', $region->get('http://example.com/bundle.css')['styleUrl']);
    $this->assertEquals('http://example.com/region.js', $region->get('http://example.com/region.js')['scriptUrl']);
    $this->assertEquals('http://example.com/region.css', $region->get('http://example.com/region.css')['styleUrl']);
  }

  /**
   * Assert that two snippets are equivalent.
   *
   * @param array $expect
   * @param array $actual
   * @param string $message
   */
  public function assertSameSnippet($expect, $actual, $message = '') {
    $normalizeUrl = function($url) {
      // If there is a cache code (?r=XXXX), then replace random value with constant XXXX.
      return preg_replace(';([\?\&]r=)([a-zA-Z0-9_\-]+);', '\1XXXX', $url);
    };

    $normalizeSnippet = function ($snippet) use ($normalizeUrl) {
      // Any URLs in 'styleFileUrls' or '
      foreach (['styleUrl', 'scriptUrl'] as $field) {
        if (isset($snippet[$field])) {
          $snippet[$field] = $normalizeUrl($snippet[$field]);
        }
      }
      foreach (['styleFileUrls', 'scriptFileUrls'] as $field) {
        if (isset($snippet[$field])) {
          $snippet[$field] = array_map($normalizeUrl, $snippet[$field]);
        }
      }
      ksort($snippet);
      return $snippet;
    };

    $expect = $normalizeSnippet($expect);
    $actual = $normalizeSnippet($actual);
    $this->assertEquals($expect, $actual, $message);
  }

}

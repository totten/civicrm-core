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
 */
class CRM_Core_Resources_BundleTest extends CiviUnitTestCase {

  public function getSnippetExamples() {
    $es = [];

    // Each example is a snippet, in three equivalent formulations:
    // 0: The "snippet" as a minimal array passed to `add()`.
    // 1: The "snippet" as a callback to the `addFoo(...)` method.
    // 2: The "snippet" with all defaults/computed values.

    $es['scriptUrl_1'] = [
      ['scriptUrl' => 'http://example.com/foo.js'],
      ['addScriptUrl', 'http://example.com/foo.js'],
      [
        'name' => 'http://example.com/foo.js',
        'disabled' => FALSE,
        'weight' => 1,
        'type' => 'scriptUrl',
        'scriptUrl' => 'http://example.com/foo.js',
      ],
    ];

    $es['styleUrl_1'] = [
      ['styleUrl' => 'http://example.com/foo.css'],
      ['addStyleUrl', 'http://example.com/foo.css'],
      [
        'name' => 'http://example.com/foo.css',
        'disabled' => FALSE,
        'weight' => 1,
        'type' => 'styleUrl',
        'styleUrl' => 'http://example.com/foo.css',
      ],
    ];

    $es['styleFile_1'] = [
      ['styleFile' => ['civicrm', 'css/civicrm.css']],
      ['addStyleFile', 'civicrm', 'css/civicrm.css'],
      [
        'name' => 'civicrm:css/civicrm.css',
        'disabled' => FALSE,
        'weight' => 1,
        'type' => 'styleFile',
        'styleFile' => ['civicrm', 'css/civicrm.css'],
        'styleFileUrls' => [
          Civi::paths()->getUrl('[civicrm.root]/css/civicrm.css?r=XXXX'),
        ],
      ],
    ];

    $es['scriptFile_1'] = [
      ['scriptFile' => ['civicrm', 'js/foo.js']],
      ['addScriptFile', 'civicrm', 'js/foo.js'],
      [
        'name' => 'civicrm:js/foo.js',
        'disabled' => FALSE,
        'weight' => 1,
        'type' => 'scriptFile',
        'scriptFile' => ['civicrm', 'js/foo.js'],
        'scriptFileUrls' => [
          Civi::paths()->getUrl('[civicrm.root]/js/foo.js?r=XXXX'),
        ],
        'translate' => TRUE,
      ],
    ];

    return $es;
  }

  /**
   * Add a snippet with the generic "add($array)".
   *
   * @param string $inputSnippet
   *   Ex: ['scriptUrl' => 'http://example.com/foo.js']
   * @param mixed $ignore
   * @param array $expectSnippet
   * @dataProvider getSnippetExamples
   */
  public function testAddArrayDefaults($inputSnippet, $ignore, $expectSnippet) {
    $b = new CRM_Core_Resources_Bundle();
    $createdSnippet = $b->add($inputSnippet);
    $this->assertSameSnippet($expectSnippet, $createdSnippet, 'add() method should return snippet with properly computed defaults');
    $count = 0;
    foreach ($b->getAll() as $getSnippet) {
      $this->assertSameSnippet($expectSnippet, $getSnippet, 'getAll() method should return snippet with properly computed defaults');
      $count++;
    }
    $this->assertEquals(1, $count, 'Expect one registered snippet');
  }

  /**
   * Add a snippet with the richer `addFoo($value)` helper.
   *
   * @param mixed $ignore
   * @param array $callbackArgs
   *   Ex: ['addScriptUrl', 'http://example.com/foo.js'].
   * @param array $expectSnippet
   * @dataProvider getSnippetExamples
   */
  public function testAddFunctionDefaults($ignore, $callbackArgs, $expectSnippet) {
    $method = array_shift($callbackArgs);

    $b = new CRM_Core_Resources_Bundle();
    call_user_func_array([$b, $method], $callbackArgs);
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

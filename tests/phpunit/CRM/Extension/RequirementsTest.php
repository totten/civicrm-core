<?php

/**
 * @group headless
 */
class CRM_Extension_RequirementsTest extends \CiviUnitTestCase {

  protected function setUp(): void {
    parent::setUp();
    global $_requirements_test;
    $_requirements_test = [];
    $orig = NULL;
    $orig = set_error_handler(function(...$args) use (&$orig) {
      if (E_DEPRECATED & $args[0]) {
        // Whatever. We want syntax that's version-specific.
      }
      else {
        $orig(...$args);
      }
    });
  }

  protected function tearDown(): void {
    restore_error_handler();
    parent::tearDown();
  }

  public function getPhpVersionChecks() {
    $isPhp = function ($gte, $lt) {
      return version_compare(PHP_VERSION, $gte, '>=') && version_compare(PHP_VERSION, $lt, '<');
    };

    $exs = [];
    // [0 => string $extensionName, 1 => bool $expectInstallable]
    $exs[] = ['php7test', $isPhp('7', '8')];
    $exs[] = ['php8test', $isPhp('8', '9')];
    // $exs[] = ['php78test', $isPhp('7', '9')];
    return $exs;
  }

  /**
   * @param string $extName
   * @param bool $expectInstallable
   * @return void
   * @dataProvider getPhpVersionChecks
   */
  public function testPhpVersionValid(string $extName, bool $expectInstallable): void {
    $extKey = "test.extension.manager.$extName";
    $manager = \CRM_Extension_System::singleton()->getManager();
    $this->assertEquals(\CRM_Extension_Manager::STATUS_UNINSTALLED, $manager->getStatus($extKey));

    if (!$expectInstallable) {
      $this->markTestSkipped('Not applicable to local PHP environment');
    }

    $this->assertEquals(NULL, \Civi::settings()->get($extName));
    $manager->install([$extKey]);
    $this->assertEquals('ok', \Civi::settings()->get($extName));
    $manager->disable([$extKey]);
    $manager->uninstall([$extKey]);
    $this->assertHookCounts($extName, [
      'install' => 1,
      'enable' => 1,
      'disable' => 1,
      'uninstall' => 1,
    ]);
  }

  /**
   * @param string $extName
   * @param bool $expectInstallable
   * @return void
   * @dataProvider getPhpVersionChecks
   */
  public function testPhpVersionInvalid(string $extName, bool $expectInstallable): void {
    $extKey = "test.extension.manager.$extName";
    $manager = \CRM_Extension_System::singleton()->getManager();
    $this->assertEquals(\CRM_Extension_Manager::STATUS_UNINSTALLED, $manager->getStatus($extKey));

    if ($expectInstallable) {
      $this->markTestSkipped('Not applicable to local PHP environment');
    }

    try {
      $manager->install(["test.extension.manager.$extName"]);
      $this->fail("Expected extension $extName to be blocked");
    }
    catch (CRM_Extension_Exception_RequirementsException $e) {
      $this->assertMatchesRegularExpression(';Requirements have not been satisfied;', $e->getMessage());
    }
    $this->assertHookCounts($extName, [
      'install' => 0,
      'enable' => 0,
      'disable' => 0,
      'uninstall' => 0,
    ]);
  }

  public function assertHookCounts($module, $counts) {
    global $_requirements_test;
    foreach ($counts as $key => $expected) {
      $actual = $_requirements_test[$module][$key] ?? 0;
      $this->assertSame($expected, $actual,
        sprintf('Expected %d call(s) to hook_civicrm_%s -- found %d', $expected, $key, $actual)
      );
    }
  }

}

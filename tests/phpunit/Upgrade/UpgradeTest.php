<?php

namespace Upgrade;

/**
 * Load various example databases and try to perform upgrades (using different mechanisms).
 *
 * Bootstrap: The test-environment is booted with `cv php:boot --level=settings` (per `bootstrap.php`).
 *
 * Requirements: The command `civicrm-upgrade-examples` must be available on the PATH. It is
 *   defined by `civicrm/upgrade-test` and included with buildkit.
 *
 * Options: You may focus the upgrade-test on specific examples by setting an env-var, eg
 *   UPGRADE_TEST_FILTER='5.39*'
 */
class UpgradeTest extends \PHPUnit\Framework\TestCase {

  protected function setUp(): void {
    parent::setUp();
    $this->assertNotEquals('UnitTests', CIVICRM_UF);
  }

  public function testExamplesAvailable() {
    $this->assertNotEmpty($this->getExamples(), 'Upgrade testing depends on "civicrm-upgrade-examples" (package "civicrm/upgrade-test"). The command did not return any examples. Is it installed properly?');
  }

  public function testFoo() {
    $this->assertTrue(FALSE, 'foo! filter=' . $this->getExampleFilter());
  }

  /**
   * @param string $exampleFile
   * @dataProvider getExamples
   */
  public function testCvUpgrade(string $exampleFile) {
    $this->assertFileExists($exampleFile);
    $cmd = 'bzcat ' . escapeshellarg($exampleFile);
    exec($cmd, $exampleSql, $exitCode);
    $this->assertEquals(0, $exitCode, "Failed to decode $exampleFile with bzip2");
    $this->assertTrue(!empty($exampleSql), "Failed to read data from $exampleFile");

    // \Civi\Test::schema()->dropAll();
    // \Civi\Test::execute(implode("\n", $exampleSql));
    // FIXME: load $exampleFile
  }

  public static function getExamples(): array {
    static $cache;
    if ($cache === NULL) {
      $descriptorSpec = array(0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => STDERR);
      $process = proc_open('civicrm-upgrade-examples ' . escapeshellarg(static::getExampleFilter()), $descriptorSpec, $pipes, __DIR__);
      fclose($pipes[0]);
      $result = stream_get_contents($pipes[1]);
      fclose($pipes[1]);
      if (proc_close($process) !== 0) {
        $cache = [];
      }
      else {
        $cache = [];
        foreach (preg_grep('/./', explode("\n", $result)) as $file) {
          $cache[basename($file)] = [$file];
        }
      }
    }
    return $cache;
  }

  public static function getExampleFilter(): string {
    if (getenv('UPGRADE_TEST_FILTER')) {
      return getenv('UPGRADE_TEST_FILTER');
    }
    $lowVersion = '4.6.12';
    $highVersion = \CRM_Utils_System::version();
    $maxCount = 10;
    return "@{$lowVersion}..{$highVersion}:{$maxCount}";
  }

}

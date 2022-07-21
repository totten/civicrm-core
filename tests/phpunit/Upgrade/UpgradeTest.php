<?php

namespace Upgrade;

class UpgradeTest extends \PHPUnit\Framework\TestCase {

  public function testFoo() {
    $this->assertTrue(FALSE, 'foo! filter=' . $this->getExampleFilter());
  }

  public function testBar() {
    $this->assertTrue(TRUE, 'bar!');
  }

  public static function getExamples(): array {
    static $cache;
    if ($cache === NULL) {

      $cache = [];

      $descriptorSpec = array(0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => STDERR);
      $process = proc_open("civicrm-upgrade-examples @{$version}:10", $descriptorSpec, $pipes, __DIR__);
      fclose($pipes[0]);
      $result = stream_get_contents($pipes[1]);
      fclose($pipes[1]);
      if (proc_close($process) !== 0) {
        $cache = [];
      }
      else {
        $cache = preg_grep('/./', explode("\n", $result));
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

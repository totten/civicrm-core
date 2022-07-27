<?php

// Generate the "upgrader-lib@1.x" mixin by copying `CRM/Extension/Upgrader/*`.
//
// Note: The versioning for `CRM/Extension/Upgrader/*` is different from the versioning for the mixin.
// There is a subjective decision and when/whether to propagate the update to the mixin and set a new version.
//
// Usage: php build.php MAJOR.MINOR
// Example: php build.php 1.2

if (!(php_sapi_name() == 'cli' || (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0))) {
  header("HTTP/1.0 404 Not Found");
  return;
}

ini_set('display_errors', 1);
require_once dirname(__DIR__, 2) . '/tools/mixin/src/Mixbuild.php';

class UpgraderMixin extends Mixbuild {

  public static function main(array $argv): void {
    $builder = new UpgraderMixin();
    $builder->majorMinor = $argv[1] ?? NULL;

    if (empty($builder->majorMinor)) {
      throw new \RuntimeException('Missing argument MAJOR.MINOR');
    }

    // Generate one variant (statically linked)
    file_put_contents(__DIR__ . '/mixin.php', Mixbuild::capture([$builder, 'buildStaticMixin']));

    // Generate two variants (statically and dynamically linked)
    // file_put_contents(__DIR__ . '/mixin.backport.php', Mixbuild::capture([$builder, 'buildStaticMixin']));
    // file_put_contents(__DIR__ . '/mixin.php', Mixbuild::capture([$builder, 'buildAliasMixin']));
  }

  /**
   * @var string
   */
  public $namespace = 'Civi\Mixin\UpgraderLibV1';

  /**
   * @var string
   *   Ex: '1.0'
   *   (Loaded from CLI args.)
   */
  public $majorMinor;

  public $headers = [
    'mixinName' => 'upgrader-lib',
    'mixinVersion' => 'AUTO-REPLACE',
    'since' => '5.52',
  ];

  public $classMap = [
    'Base' => 'CRM_Extension_Upgrader_Base',
    'IdentityTrait' => 'CRM_Extension_Upgrader_IdentityTrait',
    'UpgraderInterface' => 'CRM_Extension_Upgrader_Interface',
    'QueueTrait' => 'CRM_Extension_Upgrader_QueueTrait',
    'RevisionsTrait' => 'CRM_Extension_Upgrader_RevisionsTrait',
    'SchemaTrait' => 'CRM_Extension_Upgrader_SchemaTrait',
    'TasksTrait' => 'CRM_Extension_Upgrader_TasksTrait',
  ];

  /**
   * Generate a statically-linked mixin, in which ever source-class has been
   * copied to the mixin namespace.
   *
   * To wit: "Copy CRM_Extension_Upgrader_* to \Civi\Mixin\UpgraderLibV1\*".
   */
  public function buildStaticMixin(): void {
    $headers = $this->headers;
    $headers['mixinVersion'] = $this->majorMinor . '.0';

    $this->printHeader();
    foreach ($this->classMap as $newClass => $srcClass) {
      $this->printFilteredClass($srcClass, function(string $line) {
        return strtr($line, array_flip($this->classMap));
      });
    }
    $this->printDocblock('Upgrader Base Class', $headers);
    $this->printEmptyMixin();
  }

  /**
   * Generate a dynamic mixin, in which the mixin namespace contains aliases for
   * each source-classes.
   *
   * To wit: "class_alias('\Civi\Mixin\UpgraderLibV1\SchemaTrait', 'CRM_Extension_Upgrader_SchemaTrait')".
   */
  public function buildAliasMixin(): void {
    $headers = $this->headers;
    $headers['mixinVersion'] = $this->majorMinor . '.1';

    $fullAliasClasses = [];
    foreach ($this->classMap as $newClass => $srcClass) {
      $fullAliasClasses[$this->namespace . '\\' . $newClass] = $srcClass;
    }

    $this->printHeader();
    $this->printFunction(new ReflectionMethod('Mixbuild', 'registerClassAliases'));
    printf("registerClassAliases(%s);\n", Mixbuild::capture(['Mixbuild', 'printData'], $fullAliasClasses, '  '));
    $this->printDocblock('Upgrader Base Class', $headers);
    $this->printEmptyMixin();
  }

  protected function printHeader(): void {
    $me = str_replace(realpath(Mixbuild::findCivicrmRoot()) . DIRECTORY_SEPARATOR, '', realpath(__FILE__));

    printf("<" . "?php\n");
    printf("// Generated via \"%s %s\"\n", $me, $this->majorMinor);
    printf("namespace %s;\n", $this->namespace);
    printf("\n");
  }

}

UpgraderMixin::main($argv);

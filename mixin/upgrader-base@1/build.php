<?php

// Generate the "upgrade-base@1.x" mixin by concatentating files from `CRM/Upgrade/*`.
//
// Usage: php build.php MAJOR.MINOR
// Example: php build.php 1.2

if (!(php_sapi_name() == 'cli' || (is_numeric($_SERVER['argc']) && $_SERVER['argc'] > 0))) {
  header("HTTP/1.0 404 Not Found");
  return;
}

ini_set('display_errors', 1);
require_once dirname(__DIR__, 2) . '/tools/mixin/src/Mixbuild.php';
main($argv);

function main($argv) {
  $mixinNamespace = 'Civi\Mixin\UpgraderBaseV1';
  $majorMinor = $argv[1] ?? NULL;

  if (empty($majorMinor)) {
    throw new \RuntimeException('Missing argument MAJOR.MINOR');
  }

  $headers = [
    'mixinName' => 'upgrader-base',
    'mixinVersion' => 'AUTO-REPLACE',
    'since' => '5.52',
  ];
  $classMap = [
    'Base' => 'CRM_Extension_Upgrader_Base',
    'IdentityTrait' => 'CRM_Extension_Upgrader_IdentityTrait',
    'UpgraderInterface' => 'CRM_Extension_Upgrader_Interface',
    'QueueTrait' => 'CRM_Extension_Upgrader_QueueTrait',
    'RevisionsTrait' => 'CRM_Extension_Upgrader_RevisionsTrait',
    'SchemaTrait' => 'CRM_Extension_Upgrader_SchemaTrait',
    'TasksTrait' => 'CRM_Extension_Upgrader_TasksTrait',
  ];

  // Generate two versions of the mixin:
  // - `mixin.backport.php` is a statically linked and has lower version# (eg `1.2.0`).
  // - `mixin.php` relies on autoloader to get latest class and has higher version# (eg `1.2.1`).
  file_put_contents(__DIR__ . '/mixin.backport.php', Mixbuild::capture(function() use ($majorMinor, $mixinNamespace, $headers, $classMap) {
    $mainFile = str_replace(realpath(Mixbuild::findCivicrmRoot()) . DIRECTORY_SEPARATOR, '', realpath(__FILE__));
    $headers['mixinVersion'] = $majorMinor . '.0';

    Mixbuild::printFileHeader($mixinNamespace, "Generated via \"$mainFile $majorMinor\"");
    foreach ($classMap as $newClass => $srcClass) {
      Mixbuild::printFilteredClass($srcClass, function(string $line) use ($classMap) {
        return strtr($line, array_flip($classMap));
      });
    }
    Mixbuild::printDocblock('Upgrader Base Class', $headers);
    MixBuild::printEmptyMixin();
  }));

  file_put_contents(__DIR__ . '/mixin.php', Mixbuild::capture(function() use ($majorMinor, $mixinNamespace, $headers, $classMap) {
    $mainFile = str_replace(realpath(Mixbuild::findCivicrmRoot()) . DIRECTORY_SEPARATOR, '', realpath(__FILE__));
    $headers['mixinVersion'] = $majorMinor . '.1';

    $fullAliasClasses = [];
    foreach ($classMap as $newClass => $srcClass) {
      $fullAliasClasses[$mixinNamespace . '\\' . $newClass] = $srcClass;
    }

    Mixbuild::printFileHeader($mixinNamespace, "Generated via \"$mainFile $majorMinor\"");
    Mixbuild::printFunction(new ReflectionMethod('Mixbuild', 'registerClassAliases'));
    printf("registerClassAliases(%s);\n", Mixbuild::capture(['Mixbuild', 'printData'], $fullAliasClasses, '  '));
    Mixbuild::printDocblock('Upgrader Base Class', $headers);
    MixBuild::printEmptyMixin();
  }));
}


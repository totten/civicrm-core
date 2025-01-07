#!/usr/bin/env php
<?php

if (PHP_SAPI !== 'cli') {
  die("civicrm-npm/export.php can only be run from command line.");
}

define('PACKAGE_LOCK', __DIR__ . '/package-lock.json');
define('COMPOSER_JSON', dirname(__DIR__,  2) . '/composer.json');

global $exportConfig;
$exportConfig = [
  'exclude-packages' => [
    '@ranfdev/deepobj',
    'commander',
    'crossfilter2',
    'delaunator',
    'd3',
    '/^d3-(array|axis|brush|chord|color|contour|delaunay|dispatch|drag|dsv|ease|fetch|force|format|geo|hierarchy|interpolate|path|polygon|quadtree|random|scale|scale-chromatic|selection|shape|time|time-format|timer|transition|zoom)/',
    'iconv-lite',
    'internmap',
    'lodash.result',
    'jquery-ui-dist',
    'rw',
    '/^(argparse|esprima|sprintf-js)$/', /* js-yaml */
    'safer-buffer',
  ],
];

/**
 * Read packages from 'package-lock.json'. Add them to 'composer.json' under the 'downloads' section.
 */
function main() {
  $packageLock = json_decode(file_get_contents(PACKAGE_LOCK), TRUE);
  $composerJson = json_decode(file_get_contents(COMPOSER_JSON), TRUE);
  $config = $GLOBALS['exportConfig'];

  foreach ($packageLock['dependencies'] as $name => $spec) {
    foreach ($config['exclude-packages'] as $exclude) {
      if ($name === $exclude || ($exclude[0] === '/' && preg_match($exclude, $name))) {
        continue 2;
      }
    }

    if (empty($spec['resolved'])) {
      warn("Package $name was nor resolved to a download URL! Skipping.");
      continue;
    }

    $composerJson['extra']['downloads'][$name]['url'] = $spec['resolved'];
  }

  $rendered = encodeJson(NULL, $composerJson) . "\n";
  echo $rendered;
  file_put_contents(COMPOSER_JSON . '.new', $rendered);
}

function encodeJson($id, $data, int $indent = 0, bool $startMidLine = FALSE) {
  $ALWAYS_SINGLE_LINE = ['ignore'];
  $ALWAYS_MULTILINE = ['platform', 'psr-4'];

  if (!is_array($data)) {
    return json_encode($data, JSON_UNESCAPED_SLASHES);
  }

  $prefix = function() use (&$indent) {
    return $indent > 0 ? str_repeat(' ',  2 * $indent) : '';
  };
  $isSequential = array_keys($data) === range(0, count($data) - 1);

  $singleLine = FALSE;
  if (in_array($id, $ALWAYS_SINGLE_LINE)) {
    $singleLine = TRUE;
  }
  // elseif (!in_array($id, $ALWAYS_MULTILINE) &&  !isset($data['url']) && !isset($data['path']) && (count($data) < 2)) {
  elseif ($isSequential && (count($data) <= 3) && !in_array($id, $ALWAYS_MULTILINE)) {
    $singleLine = json_encode($data, JSON_UNESCAPED_SLASHES);
    if (strlen($singleLine) < 40) {
      $singleLine = TRUE;
    }
  }

  if ($singleLine) {
    $buf = '[';
    $buf .= implode(', ', array_map(fn($i) => json_encode($i, JSON_UNESCAPED_SLASHES), $data));
    $buf .= ']';
    return $buf;
  }

  $isFirst = TRUE;
  $buf = ($startMidLine ? '' : $prefix()) . ($isSequential ? "[\n" : "{\n");
  $indent++;
  foreach ($data as $key => $value) {
    if (!$isFirst) {
      $buf .= ",\n";
    }
    $buf .= $prefix();

    if ($isSequential) {
      $buf .= encodeJson($key, $value, $indent, TRUE);
    }
    else {
      $buf .= json_encode((string) $key, JSON_UNESCAPED_SLASHES) . ': ';
      $buf .= encodeJson($key, $value, $indent, TRUE);
    }

    $isFirst = FALSE;
  }
  $indent--;
  $buf .= "\n" . $prefix() . ($isSequential ? "]" : "}");
  return $buf;
}

function warn(string $message) {
  fwrite(STDERR, "$message\n");
}

main();

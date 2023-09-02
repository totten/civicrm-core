<?php

function _php8test_increment($hook) {
  global $_requirements_test;
  $ext = 'php8test';
  $_requirements_test[$ext][$hook] = 1 + ($_requirements_test[$ext][$hook] ?? 0);
}

function php8test_civicrm_install() {
  _php8test_increment('install');
}

function php8test_civicrm_enable() {
  _php8test_increment('enable');

  // Statement valid in PHP 8 but not PHP 7
  Civi::settings()->set('php8test', match ('php8') {
    'php8' => 'ok',
    'php7' => 'oops',
  });
  // NOTE: This may not pass linter (`php -l`) in all environments.
  // I don't see a good to be a thorough test and get through it. I suggest ignoring it.
}

function php8test_civicrm_disable() {
  _php8test_increment('disable');
  Civi::settings()->revert('php8test');
}

function php8test_civicrm_uninstall() {
  _php8test_increment('uninstall');
}

<?php

// At some point, you may look back and say: "PHP 7? That's so oldentime. Let's remove this file!"
// Great. That's a perfect to consider adding a PHP 9 case (and re-tuning the PHP 8 case).

function _php7test_increment($hook) {
  global $_requirements_test;
  $ext = 'php7test';
  $_requirements_test[$ext][$hook] = 1 + ($_requirements_test[$ext][$hook] ?? 0);
}

function php7test_civicrm_install() {
  _php7test_increment('install');
}

function php7test_civicrm_enable() {
  _php7test_increment('enable');

  // Statement valid in PHP 7 but not PHP 8
  $string = 'okay';
  Civi::settings()->set('php7test', $string{0} . $string{1});
  // NOTE: This may not pass linter (`php -l`) in all environments.
  // I don't see a good to be a thorough test and get through it. I suggest ignoring it.
}

function php7test_civicrm_disable() {
  _php7test_increment('disable');
  Civi::settings()->revert('php7test');
}

function php7test_civicrm_uninstall() {
  _php7test_increment('uninstall');
}

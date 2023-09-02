<?php

function _php78test_increment($hook) {
  global $_requirements_test;
  $ext = 'php78test';
  $_requirements_test[$ext][$hook] = 1 + ($_requirements_test[$ext][$hook] ?? 0);
}

function php78test_civicrm_install() {
  _php78test_increment('install');
}

function php78test_civicrm_enable() {
  _php78test_increment('enable');
  Civi::settings()->set('php78test', 'ok');
}

function php78test_civicrm_disable() {
  _php78test_increment('disable');
  Civi::settings()->revert('php78test');
}

function php78test_civicrm_uninstall() {
  _php78test_increment('uninstall');
}

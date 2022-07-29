<?php
// This file declares an Angular module which can be autoloaded
// in CiviCRM. See also:
// \https://docs.civicrm.org/dev/en/latest/hooks/hook_civicrm_angularModules/n
return [
  'js' => [
    'ang/afInlay.js',
    'ang/afInlay/*.js',
    'ang/afInlay/*/*.js',
  ],
  'css' => [
    'ang/afInlay.css',
  ],
  'partials' => [
    'ang/afInlay',
  ],
  'requires' => [
    'crmUi',
    'crmUtil',
    'ngRoute',
  ],
  'settings' => [],
];

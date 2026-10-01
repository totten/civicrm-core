<?php
return [
  'js' => [
    'ang/crmSearchKitFts.module.js',
    'ang/crmSearchKitFts/*.js',
    'ang/crmSearchKitFts/*/*.js',
  ],
  'partials' => [
    'ang/crmSearchKitFts',
  ],
  'basePages' => ['civicrm/admin/search'],
  'requires' => ['crmSearchAdmin', 'crmUi', 'crmUtil'],
];

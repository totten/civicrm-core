<?php
return array(
  'ext' => 'civicrm',
  'js' => [
    'bower_components/monaco-editor/min/vs/loader.js',
    'ang/crmMonaco.js',
    //    'ang/crmMonaco/*.js',
    //    'ang/crmMonaco/*/*.js',
  ],
  'css' => ['ang/crmMonaco.css'],
  // 'partials' => ['ang/crmMonaco'],
  'requires' => ['crmUi', 'crmUtil'],
  'settings' => [
    'paths' => [
      'vs' => Civi::paths()->getUrl('[civicrm.bower]/monaco-editor/min/vs'),
    ],
  ],
  'basePages' => [],
  'exports' => [
    'crm-monaco' => 'A',
  ],
);

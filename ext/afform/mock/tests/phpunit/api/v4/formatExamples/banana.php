<?php

return [
  'html' => '<div><strong>New text!</strong><af-field name="do_not_sms" defn="{label: \'Do not do any of the emailing\'}"></af-field></div>',
  'shallow' => [
    '#tag' => 'div',
    '#children' => [
      ['#tag' => 'strong', '#children' => ['New text!']],
      ['#tag' => 'af-field', 'name' => 'do_not_sms', 'defn' => "{label: 'Do not do any of the emailing'}"],
    ],
  ],
  'deep' => [
    '#tag' => 'div',
    '#children' => [
      ['#tag' => 'strong', '#children' => ['New text!']],
      ['#tag' => 'af-field', 'name' => 'do_not_sms', 'defn' => ['label' => 'Do not do any of the emailing']],
    ],
  ],
];
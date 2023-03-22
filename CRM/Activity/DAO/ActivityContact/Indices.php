<?php
return [
  'UI_activity_contact' => [
    'name' => 'UI_activity_contact',
    'field' => [
      0 => 'contact_id',
      1 => 'activity_id',
      2 => 'record_type_id',
    ],
    'localizable' => FALSE,
    'unique' => TRUE,
    'sig' => 'civicrm_activity_contact::1::contact_id::activity_id::record_type_id',
  ],
  'index_record_type' => [
    'name' => 'index_record_type',
    'field' => [
      0 => 'activity_id',
      1 => 'record_type_id',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_activity_contact::0::activity_id::record_type_id',
  ],
];

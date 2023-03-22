<?php
return [
  'UI_user_contact_operation' => [
    'name' => 'UI_user_contact_operation',
    'field' => [
      0 => 'user_id',
      1 => 'contact_id',
      2 => 'operation',
    ],
    'localizable' => FALSE,
    'unique' => TRUE,
    'sig' => 'civicrm_acl_contact_cache::1::user_id::contact_id::operation',
  ],
];

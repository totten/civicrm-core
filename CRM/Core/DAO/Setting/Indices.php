<?php
return [
  'index_domain_contact_name' => [
    'name' => 'index_domain_contact_name',
    'field' => [
      0 => 'domain_id',
      1 => 'contact_id',
      2 => 'name',
    ],
    'localizable' => FALSE,
    'unique' => TRUE,
    'sig' => 'civicrm_setting::1::domain_id::contact_id::name',
  ],
];

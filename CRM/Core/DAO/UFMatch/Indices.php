<?php
return [
  'I_civicrm_uf_match_uf_id' => [
    'name' => 'I_civicrm_uf_match_uf_id',
    'field' => [
      0 => 'uf_id',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_uf_match::0::uf_id',
  ],
  'UI_uf_name_domain_id' => [
    'name' => 'UI_uf_name_domain_id',
    'field' => [
      0 => 'uf_name',
      1 => 'domain_id',
    ],
    'localizable' => FALSE,
    'unique' => TRUE,
    'sig' => 'civicrm_uf_match::1::uf_name::domain_id',
  ],
  'UI_contact_domain_id' => [
    'name' => 'UI_contact_domain_id',
    'field' => [
      0 => 'contact_id',
      1 => 'domain_id',
    ],
    'localizable' => FALSE,
    'unique' => TRUE,
    'sig' => 'civicrm_uf_match::1::contact_id::domain_id',
  ],
];

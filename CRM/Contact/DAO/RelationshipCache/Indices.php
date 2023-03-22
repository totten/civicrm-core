<?php
return [
  'UI_relationship' => [
    'name' => 'UI_relationship',
    'field' => [
      0 => 'relationship_id',
      1 => 'orientation',
    ],
    'localizable' => FALSE,
    'unique' => TRUE,
    'sig' => 'civicrm_relationship_cache::1::relationship_id::orientation',
  ],
  'index_nearid_nearrelation' => [
    'name' => 'index_nearid_nearrelation',
    'field' => [
      0 => 'near_contact_id',
      1 => 'near_relation',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_relationship_cache::0::near_contact_id::near_relation',
  ],
  'index_nearid_farrelation' => [
    'name' => 'index_nearid_farrelation',
    'field' => [
      0 => 'near_contact_id',
      1 => 'far_relation',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_relationship_cache::0::near_contact_id::far_relation',
  ],
  'index_near_relation' => [
    'name' => 'index_near_relation',
    'field' => [
      0 => 'near_relation',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_relationship_cache::0::near_relation',
  ],
];

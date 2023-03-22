<?php
return [
  'index_role' => [
    'name' => 'index_role',
    'field' => [
      0 => 'acl_role_id',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_acl_entity_role::0::acl_role_id',
  ],
  'index_entity' => [
    'name' => 'index_entity',
    'field' => [
      0 => 'entity_table',
      1 => 'entity_id',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_acl_entity_role::0::entity_table::entity_id',
  ],
];

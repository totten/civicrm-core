<?php
return [
  'index_entity' => [
    'name' => 'index_entity',
    'field' => [
      0 => 'entity_table',
      1 => 'entity_id',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_entity_batch::0::entity_table::entity_id',
  ],
  'UI_batch_entity' => [
    'name' => 'UI_batch_entity',
    'field' => [
      0 => 'batch_id',
      1 => 'entity_id',
      2 => 'entity_table',
    ],
    'localizable' => FALSE,
    'unique' => TRUE,
    'sig' => 'civicrm_entity_batch::1::batch_id::entity_id::entity_table',
  ],
];

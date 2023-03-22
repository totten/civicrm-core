<?php
return [
  'index_entity' => [
    'name' => 'index_entity',
    'field' => [
      0 => 'entity_table',
      1 => 'entity_id',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_discount::0::entity_table::entity_id',
  ],
  'index_entity_option_id' => [
    'name' => 'index_entity_option_id',
    'field' => [
      0 => 'entity_table',
      1 => 'entity_id',
      2 => 'price_set_id',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_discount::0::entity_table::entity_id::price_set_id',
  ],
];

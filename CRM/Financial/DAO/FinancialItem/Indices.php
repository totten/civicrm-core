<?php
return [
  'IX_created_date' => [
    'name' => 'IX_created_date',
    'field' => [
      0 => 'created_date',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_financial_item::0::created_date',
  ],
  'IX_transaction_date' => [
    'name' => 'IX_transaction_date',
    'field' => [
      0 => 'transaction_date',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_financial_item::0::transaction_date',
  ],
  'index_entity_id_entity_table' => [
    'name' => 'index_entity_id_entity_table',
    'field' => [
      0 => 'entity_id',
      1 => 'entity_table',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_financial_item::0::entity_id::entity_table',
  ],
];

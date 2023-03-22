<?php
return [
  'UI_contrib_payment_instrument_id' => [
    'name' => 'UI_contrib_payment_instrument_id',
    'field' => [
      0 => 'payment_instrument_id',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_contribution::0::payment_instrument_id',
  ],
  'index_total_amount_receive_date' => [
    'name' => 'index_total_amount_receive_date',
    'field' => [
      0 => 'total_amount',
      1 => 'receive_date',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_contribution::0::total_amount::receive_date',
  ],
  'index_source' => [
    'name' => 'index_source',
    'field' => [
      0 => 'source',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_contribution::0::source',
  ],
  'UI_contrib_trxn_id' => [
    'name' => 'UI_contrib_trxn_id',
    'field' => [
      0 => 'trxn_id',
    ],
    'localizable' => FALSE,
    'unique' => TRUE,
    'sig' => 'civicrm_contribution::1::trxn_id',
  ],
  'UI_contrib_invoice_id' => [
    'name' => 'UI_contrib_invoice_id',
    'field' => [
      0 => 'invoice_id',
    ],
    'localizable' => FALSE,
    'unique' => TRUE,
    'sig' => 'civicrm_contribution::1::invoice_id',
  ],
  'index_contribution_status' => [
    'name' => 'index_contribution_status',
    'field' => [
      0 => 'contribution_status_id',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_contribution::0::contribution_status_id',
  ],
  'received_date' => [
    'name' => 'received_date',
    'field' => [
      0 => 'receive_date',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_contribution::0::receive_date',
  ],
  'check_number' => [
    'name' => 'check_number',
    'field' => [
      0 => 'check_number',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_contribution::0::check_number',
  ],
  'index_creditnote_id' => [
    'name' => 'index_creditnote_id',
    'field' => [
      0 => 'creditnote_id',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_contribution::0::creditnote_id',
  ],
];

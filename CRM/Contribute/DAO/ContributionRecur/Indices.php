<?php
return [
  'UI_contrib_trxn_id' => [
    'name' => 'UI_contrib_trxn_id',
    'field' => [
      0 => 'trxn_id',
    ],
    'localizable' => FALSE,
    'unique' => TRUE,
    'sig' => 'civicrm_contribution_recur::1::trxn_id',
  ],
  'UI_contrib_invoice_id' => [
    'name' => 'UI_contrib_invoice_id',
    'field' => [
      0 => 'invoice_id',
    ],
    'localizable' => FALSE,
    'unique' => TRUE,
    'sig' => 'civicrm_contribution_recur::1::invoice_id',
  ],
  'index_contribution_status' => [
    'name' => 'index_contribution_status',
    'field' => [
      0 => 'contribution_status_id',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_contribution_recur::0::contribution_status_id',
  ],
  'UI_contribution_recur_payment_instrument_id' => [
    'name' => 'UI_contribution_recur_payment_instrument_id',
    'field' => [
      0 => 'payment_instrument_id',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_contribution_recur::0::payment_instrument_id',
  ],
];

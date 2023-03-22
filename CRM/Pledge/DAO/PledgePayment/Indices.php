<?php
return [
  'index_contribution_pledge' => [
    'name' => 'index_contribution_pledge',
    'field' => [
      0 => 'contribution_id',
      1 => 'pledge_id',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_pledge_payment::0::contribution_id::pledge_id',
  ],
  'index_status' => [
    'name' => 'index_status',
    'field' => [
      0 => 'status_id',
    ],
    'localizable' => FALSE,
    'sig' => 'civicrm_pledge_payment::0::status_id',
  ],
];

<?php
return [
  'UI_contribution_membership' => [
    'name' => 'UI_contribution_membership',
    'field' => [
      0 => 'contribution_id',
      1 => 'membership_id',
    ],
    'localizable' => FALSE,
    'unique' => TRUE,
    'sig' => 'civicrm_membership_payment::1::contribution_id::membership_id',
  ],
];

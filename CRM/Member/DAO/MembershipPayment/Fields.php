<?php
return [
  'id' => [
    'name' => 'id',
    'type' => CRM_Utils_Type::T_INT,
    'title' => ts('Membership Payment ID'),
    'required' => TRUE,
    'where' => 'civicrm_membership_payment.id',
    'table_name' => 'civicrm_membership_payment',
    'entity' => 'MembershipPayment',
    'bao' => 'CRM_Member_BAO_MembershipPayment',
    'localizable' => 0,
    'html' => [
      'type' => 'Number',
    ],
    'readonly' => TRUE,
    'add' => '1.5',
  ],
  'membership_id' => [
    'name' => 'membership_id',
    'type' => CRM_Utils_Type::T_INT,
    'title' => ts('Membership ID'),
    'description' => ts('FK to Membership table'),
    'required' => TRUE,
    'where' => 'civicrm_membership_payment.membership_id',
    'table_name' => 'civicrm_membership_payment',
    'entity' => 'MembershipPayment',
    'bao' => 'CRM_Member_BAO_MembershipPayment',
    'localizable' => 0,
    'FKClassName' => 'CRM_Member_DAO_Membership',
    'html' => [
      'label' => ts("Membership"),
    ],
    'add' => '1.5',
  ],
  'contribution_id' => [
    'name' => 'contribution_id',
    'type' => CRM_Utils_Type::T_INT,
    'title' => ts('Contribution ID'),
    'description' => ts('FK to contribution table.'),
    'where' => 'civicrm_membership_payment.contribution_id',
    'table_name' => 'civicrm_membership_payment',
    'entity' => 'MembershipPayment',
    'bao' => 'CRM_Member_BAO_MembershipPayment',
    'localizable' => 0,
    'FKClassName' => 'CRM_Contribute_DAO_Contribution',
    'html' => [
      'label' => ts("Contribution"),
    ],
    'add' => '2.0',
  ],
  ];
  

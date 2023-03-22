<?php
return [
  'id' => [
    'name' => 'id',
    'type' => CRM_Utils_Type::T_INT,
    'title' => ts('Address Format ID'),
    'description' => ts('Address Format ID'),
    'required' => TRUE,
    'where' => 'civicrm_address_format.id',
    'table_name' => 'civicrm_address_format',
    'entity' => 'AddressFormat',
    'bao' => 'CRM_Core_DAO_AddressFormat',
    'localizable' => 0,
    'html' => [
      'type' => 'Number',
    ],
    'readonly' => TRUE,
    'add' => '3.2',
  ],
  'format' => [
    'name' => 'format',
    'type' => CRM_Utils_Type::T_TEXT,
    'title' => ts('Address Format'),
    'description' => ts('The format of an address'),
    'where' => 'civicrm_address_format.format',
    'table_name' => 'civicrm_address_format',
    'entity' => 'AddressFormat',
    'bao' => 'CRM_Core_DAO_AddressFormat',
    'localizable' => 0,
    'add' => '3.2',
  ],
  ];
  

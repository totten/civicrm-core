<?php
return [
  'id' => [
    'name' => 'id',
    'type' => CRM_Utils_Type::T_INT,
    'title' => ts('World Region ID'),
    'description' => ts('Country ID'),
    'required' => TRUE,
    'where' => 'civicrm_worldregion.id',
    'table_name' => 'civicrm_worldregion',
    'entity' => 'WorldRegion',
    'bao' => 'CRM_Core_DAO_Worldregion',
    'localizable' => 0,
    'html' => [
      'type' => 'Number',
    ],
    'readonly' => TRUE,
    'add' => '1.8',
  ],
  'world_region' => [
    'name' => 'name',
    'type' => CRM_Utils_Type::T_STRING,
    'title' => ts('World Region'),
    'description' => ts('Region name to be associated with countries'),
    'maxlength' => 128,
    'size' => CRM_Utils_Type::HUGE,
    'where' => 'civicrm_worldregion.name',
    'export' => TRUE,
    'table_name' => 'civicrm_worldregion',
    'entity' => 'WorldRegion',
    'bao' => 'CRM_Core_DAO_Worldregion',
    'localizable' => 0,
    'add' => '1.8',
  ],
  ];
  

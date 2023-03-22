<?php
return [
  'event_in_cart_id' => [
    'name' => 'id',
    'type' => CRM_Utils_Type::T_INT,
    'title' => ts('Event In Cart'),
    'description' => ts('Event In Cart ID'),
    'required' => TRUE,
    'where' => 'civicrm_events_in_carts.id',
    'table_name' => 'civicrm_events_in_carts',
    'entity' => 'EventInCart',
    'bao' => 'CRM_Event_Cart_BAO_EventInCart',
    'localizable' => 0,
    'html' => [
      'type' => 'Number',
    ],
    'readonly' => TRUE,
    'add' => '4.1',
  ],
  'event_id' => [
    'name' => 'event_id',
    'type' => CRM_Utils_Type::T_INT,
    'title' => ts('Event ID'),
    'description' => ts('FK to Event ID'),
    'where' => 'civicrm_events_in_carts.event_id',
    'table_name' => 'civicrm_events_in_carts',
    'entity' => 'EventInCart',
    'bao' => 'CRM_Event_Cart_BAO_EventInCart',
    'localizable' => 0,
    'FKClassName' => 'CRM_Event_DAO_Event',
    'html' => [
      'label' => ts("Event"),
    ],
    'add' => '4.1',
  ],
  'event_cart_id' => [
    'name' => 'event_cart_id',
    'type' => CRM_Utils_Type::T_INT,
    'title' => ts('Event Cart ID'),
    'description' => ts('FK to Event Cart ID'),
    'where' => 'civicrm_events_in_carts.event_cart_id',
    'table_name' => 'civicrm_events_in_carts',
    'entity' => 'EventInCart',
    'bao' => 'CRM_Event_Cart_BAO_EventInCart',
    'localizable' => 0,
    'FKClassName' => 'CRM_Event_Cart_DAO_Cart',
    'html' => [
      'label' => ts("Event In Cart"),
    ],
    'add' => '4.1',
  ],
  ];
  

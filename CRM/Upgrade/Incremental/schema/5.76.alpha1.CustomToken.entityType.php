<?php

return [
  'name' => 'CustomToken',
  'table' => 'civicrm_custom_token',
  'class' => 'CRM_Core_DAO_CustomToken',
  'getInfo' => fn() => [
    'title' => ts('Custom Token'),
    'title_plural' => ts('Custom Tokens'),
    'description' => ts('Customizable tokens.'),
    'add' => 5.76,
    'log' => TRUE,
    'icon' => 'fa-code',
  ],
  'getPaths' => fn() => [
    'add' => 'civicrm/admin/customtoken/edit?action=add&reset=1',
    'update' => 'civicrm/admin/customtoken/edit?action=update&id=[id]&reset=1',
    'browse' => 'civicrm/admin/customtoken?action=browse&id=[id]&reset=1',
  ],
  'getFields' => fn() => [
    'id' => [
      'title' => ts('Custom Token ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'Number',
      'required' => TRUE,
      'primary_key' => TRUE,
      'auto_increment' => TRUE,
    ],
    'name' => [
      'title' => ts('Token Name'),
      'sql_type' => 'varchar(64)',
      'input_type' => 'Text',
      'description' => ts('Token string, e.g. {custom_token.[name]}'),
      'input_attrs' => [
        'maxlength' => 64,
      ],
    ],
    'label' => [
      'title' => ts('Token Label'),
      'sql_type' => 'varchar(255)',
      'input_type' => 'Text',
      'description' => ts('User-visible label in token UI'),
      'input_attrs' => [
        'label' => ts('Label'),
        'maxlength' => 255,
      ],
    ],
    'body_html' => [
      'title' => ts('Token Value (HTML)'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Value of the token in html format.'),
      'input_attrs' => [
        'rows' => 8,
        'cols' => 80,
      ],
    ],
    'body_text' => [
      'title' => ts('Token Value (Text)'),
      'sql_type' => 'text',
      'input_type' => 'TextArea',
      'description' => ts('Value of the token in text format.'),
      'input_attrs' => [
        'rows' => 8,
        'cols' => 80,
        'label' => ts('Body in Text Format'),
      ],
    ],
    'is_active' => [
      'title' => ts('Token Is Active?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this token active?'),
      'default' => TRUE,
      'input_attrs' => [
        'label' => ts('Enabled'),
      ],
    ],
    'is_reserved' => [
      'title' => ts('Token Is Reserved?'),
      'sql_type' => 'boolean',
      'input_type' => 'CheckBox',
      'required' => TRUE,
      'description' => ts('Is this token reserved?'),
      'default' => FALSE,
      'input_attrs' => [
        'label' => ts('Reserved'),
      ],
    ],
    'created_id' => [
      'title' => ts('Created By Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => 'EntityRef',
      'description' => ts('FK to contact table.'),
      'add' => '4.3',
      'input_attrs' => [
        'label' => ts('Created By'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'modified_id' => [
      'title' => ts('Modified By Contact ID'),
      'sql_type' => 'int unsigned',
      'input_type' => NULL,
      'readonly' => TRUE,
      'description' => ts('FK to contact table.'),
      'add' => '4.5',
      'input_attrs' => [
        'label' => ts('Modified By'),
      ],
      'entity_reference' => [
        'entity' => 'Contact',
        'key' => 'id',
        'on_delete' => 'SET NULL',
      ],
    ],
    'modified_date' => [
      'title' => ts('Modified Date'),
      'sql_type' => 'timestamp',
      'input_type' => 'Select Date',
      'readonly' => TRUE,
      'description' => ts('When the token was created or modified or deleted.'),
      'add' => '4.7',
      'unique_name' => 'custom_token_modified_date',
      'default' => 'CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
      'usage' => [
        'export',
      ],
      'input_attrs' => [
        'label' => ts('Modified Date'),
      ],
    ],
  ],
];

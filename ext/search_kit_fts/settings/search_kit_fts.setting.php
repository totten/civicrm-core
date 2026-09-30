<?php
use CRM_SearchKitFts_ExtensionUtil as E;

return [
  'fts_solr_url' => [
    'group_name' => 'FTS Settings',
    'group' => 'search_kit_fts',
    'name' => 'fts_solr_url',
    'type' => 'String',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => '',
    'is_domain' => 1,
    'is_contact' => 0,
    'add' => '1.0',
    'title' => E::ts('Solr Server URL'),
  ],
  'fts_typesense_url' => [
    'group_name' => 'FTS Settings',
    'group' => 'search_kit_fts',
    'name' => 'fts_typesense_url',
    'type' => 'String',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => '',
    'is_domain' => 1,
    'is_contact' => 0,
    'add' => '1.0',
    'title' => E::ts('TypeSense Server URL'),
  ],
  'fts_elastic_url' => [
    'group_name' => 'FTS Settings',
    'group' => 'search_kit_fts',
    'name' => 'fts_elastic_url',
    'type' => 'String',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => '',
    'is_domain' => 1,
    'is_contact' => 0,
    'add' => '1.0',
    'title' => E::ts('ElasticSearch Server URL'),
  ],
];

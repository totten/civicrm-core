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
  'fts_solr_index' => [
    'group_name' => 'FTS Settings',
    'group' => 'search_kit_fts',
    'name' => 'fts_solr_index',
    'type' => 'String',
    'html_type' => 'text',
    'quick_form_type' => 'Element',
    'default' => '[mysql.db]',
    'is_domain' => 1,
    'is_contact' => 0,
    'add' => '1.0',
    'title' => E::ts('Solr Collection'),
    // We label "fts_solr_index" as "Solr Collection" because that will be the most common case -- it is easiest to auto-provision a "Collection".
    // However, if the sysadmin sorts or provisioning, then we can work with any kind of index ("Core" or "Collection").
    'help_text' => [
      E::ts('In SearchKit FTS, a Solr "Collection" is analogous to a MySQL database. Each CiviCRM instance should use a separate "Collection".'),
      E::ts('You may assign a specific name or calculate the name based on variables:'),
      '- [mysql.db]',
      '- [search_display.id]',
      '- [search_display.name]',
      E::ts('When necessary and permitted, CiviCRM will auto-create a "Collection" using SolrCloud APIs.'),
      E::ts('Alternatively, you MAY manually configure a "Collection" or "Core".'),
    ],


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

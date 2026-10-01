<?php

return [
  [
    'name' => 'SearchDisplayType:fts',
    'entity' => 'OptionValue',
    'cleanup' => 'always',
    'update' => 'always',
    'params' => [
      'version' => 4,
      'values' => [
        'option_group_id.name' => 'search_display_type',
        'value' => 'fts',
        'name' => 'search-admin-display-fts',
        'label' => 'FTS Entity',
        'description' => 'Creates a Full-Text Search index (MySQL, Solr, TypeSense, ElasticSearch) accessible via APIv4.',
        'icon' => 'fa-search',
        'is_reserved' => FALSE,
        'is_active' => TRUE,
        'grouping' => 'non-viewable',
      ],
      'match' => ['option_group_id', 'value'],
    ],
  ],
];

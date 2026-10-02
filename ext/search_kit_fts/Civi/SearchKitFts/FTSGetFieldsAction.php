<?php

namespace Civi\SearchKitFts;

use Civi\Api4\Generic\BasicGetFieldsAction;

class FTSGetFieldsAction extends BasicGetFieldsAction {

  public function getRecords() {
    $display = array_find(\_search_kit_fts_displays(),
      fn($d) => $d['entityName'] === $this->_entityName
    );

    $fields = [
      [
        'name' => 'fts',
        'title' => 'Fulltext Search',
        'description' => 'Fulltext search query across indexed columns',
        'type' => 'Field',
        'data_type' => 'String',
        'operators' => ['CONTAINS'],
      ],
    ];

    foreach ($display['settings']['columns'] ?? [] as $col) {
      $colName = $col['spec']['name'] ?? $col['key'] ?? NULL;
      if ($colName) {
        $fields[] = [
          'name' => $colName,
          'title' => $col['spec']['label'] ?? $colName,
          'type' => 'Field',
          'data_type' => $col['spec']['data_type'] ?? 'String',
          'operators' => ['=', '!=', 'CONTAINS', 'LIKE'],
        ];
      }
    }

    return $fields;
  }

}

<?php

namespace Civi\SearchKitFts;

use Civi\Api4\Generic\BasicGetFieldsAction;

class FTSGetFieldsAction extends BasicGetFieldsAction {

  public function getRecords() {
    $displayName = preg_replace('/^FTS_/', '', $this->_entityName);

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

    $displays = \Civi\SearchKitFts\Event\Subscriber\FTSEntitySubscriber::getFtsDisplays();
    foreach ($displays as $d) {
      if ($d['name'] === $displayName) {
        foreach ($d['settings']['columns'] ?? [] as $col) {
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
        break;
      }
    }

    return $fields;
  }

}

<?php

namespace Civi\SearchKitFts\Service\Spec\Provider;

use Civi\Api4\Service\Spec\FieldSpec;
use Civi\Api4\Service\Spec\Provider\Generic\SpecProviderInterface;
use Civi\Api4\Service\Spec\RequestSpec;
use Civi\Core\Service\AutoService;
use Civi\Search\Meta;

/**
 * @service
 * @internal
 */
class FTSEntitySpecProvider extends AutoService implements SpecProviderInterface {

  /**
   * Add fields to FTS_* APIv4 virtual entities.
   */
  public function modifySpec(RequestSpec $spec): void {
    $entityName = $spec->getEntity();
    if (!str_starts_with($entityName, 'FTS_')) {
      return;
    }

    $displayName = substr($entityName, 4);
    $displays = \_search_kit_fts_displays();

    $display = NULL;
    foreach ($displays as $d) {
      if ($d['name'] === $displayName) {
        $display = $d;
        break;
      }
    }

    if (!$display) {
      return;
    }

    // Add 'fts' fulltext search field
    $ftsField = new FieldSpec('fts', $entityName, 'String');
    $ftsField->setLabel('Fulltext Search')
      ->setTitle('Fulltext Search')
      ->setDescription('Fulltext search query across indexed columns');
    $spec->addFieldSpec($ftsField);

    // Add search column fields
    foreach ($display['settings']['columns'] ?? [] as $col) {
      $colName = $col['spec']['name'] ?? Meta::createSqlName($col['key'])[0] ?? NULL;
      if (!$colName) {
        continue;
      }
      $dataType = $col['spec']['data_type'] ?? 'String';
      $field = new FieldSpec($colName, $entityName, $dataType);
      $field->setLabel($col['spec']['label'] ?? $colName)
        ->setTitle($col['spec']['label'] ?? $colName);
      $spec->addFieldSpec($field);
    }
  }

  /**
   * Applies to entities starting with FTS_
   */
  public function applies($entity, $action): bool {
    return is_string($entity) && str_starts_with($entity, 'FTS_');
  }

}

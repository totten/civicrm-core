<?php

namespace Civi\Api4\Action\SKEntity;

use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;
use Civi\Search\SKEntityGenerator;

/**
 * Store the results of a SearchDisplay as a SQL table.
 *
 * For displays of type `entity` which save to a DB table
 * rather than outputting anything to the user.
 *
 * @package Civi\Api4\Action\SKEntity
 */
class Refresh extends AbstractAction {

  /**
   * @param \Civi\Api4\Generic\Result $result
   * @throws \CRM_Core_Exception
   */
  public function _run(Result $result) {
    [, $displayName] = explode('_', $this->getEntityName(), 2);
    $display = \Civi\Api4\SearchDisplay::get(FALSE)
      ->setSelect(['id', 'settings', 'saved_search_id.api_entity', 'saved_search_id.api_params'])
      ->addWhere('type', '=', 'entity')
      ->addWhere('name', '=', $displayName)
      ->execute()->single();

    $dataMode = $display['settings']['data_mode'] ?? 'table';
    if ($dataMode === 'view') {
      return;
    }

    $dataModes = \Civi\Search\SKEntity\SKEntityUtil::getDataModes();
    $backendClass = $dataModes[$dataMode]['backend'] ?? NULL;

    if ($backendClass) {
      // Only one process should actually refresh this entity (at a given time).
      $lock = \Civi::lockManager()->acquire("data.skentity." . $display['id'], 1);
      if (!$lock->isAcquired()) {
        throw new \Civi\Search\Exception\RefreshInProgressException(sprintf('Refresh (%s) is already in progress', $this->getEntityName()));
      }
      $releaseLock = \CRM_Utils_AutoClean::with([$lock, 'release']);

      /** @var \Civi\Search\AbstractBackend $backend */
      $backend = new $backendClass(
        $this->getEntityName(),
        $display['saved_search_id.api_entity'],
        $display['saved_search_id.api_params'],
        $display['settings']
      );
      $backend->clearData();
      $backend->fillData();
    }

    // All done
    $result[] = [
      'refresh_date' => \CRM_Core_DAO::singleValueQuery("SELECT NOW()"),
    ];
  }

}

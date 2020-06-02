<?php
/*
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC. All rights reserved.                        |
 |                                                                    |
 | This work is published under the GNU AGPLv3 license with some      |
 | permitted exceptions and without any warranty. For full license    |
 | and copyright information, see https://civicrm.org/licensing       |
 +--------------------------------------------------------------------+
 */

namespace Civi\Api4\Event\Subscriber;

use Civi\API\Event\PrepareEvent;
use Civi\Api4\Generic\AbstractQueryAction;

/**
 * Class IsOperatorSubscriber
 * @package Civi\Api4\Event\Subscriber
 *
 * The "IS" operator is a pseudo-operator that accepts symbolic values. The
 * values are stand-ins for more sophisticated lookups.
 */
class IsOperatorSubscriber extends Generic\AbstractPrepareSubscriber {

  public function onApiPrepare(PrepareEvent $event) {
    /** @var \Civi\Api4\Generic\AbstractQueryAction $action */
    $action = $event->getApiRequest();
    if ($action['version'] !== 4 || !($action instanceof AbstractQueryAction)) {
      return;
    }

    $fields = $action->entityFields();
    $wheres = $action->getWhere();
    $relDateFilters = \CRM_Core_OptionGroup::values('relative_date_filters');

    foreach ($wheres as &$where) {
      // TODO: Recurse for 'OR' operator.
      $fieldName = $where[0];
      if (!isset($fields[$fieldName])) {
        continue;
      }

      if (strtolower($where[1]) !== 'is') {
        continue;
      }

      $matchValue = strtolower($where[2]);

      if ($matchValue === 'null') {
        $where = [$fieldName, 'IS NULL'];
        continue;
      }

      if (isset($relDateFilters[$matchValue])) {
        list ($from, $to) = \CRM_Utils_Date::getFromTo($matchValue, NULL, NULL);
        $where = [$fieldName, 'BETWEEN', [$from, $to]];
        continue;
      }

      throw new \API_Exception("The operator \'IS\' has an unrecognized value ($matchValue).");
    }

    $action->setWhere($wheres);
  }

}

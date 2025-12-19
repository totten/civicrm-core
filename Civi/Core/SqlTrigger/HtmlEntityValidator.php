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

namespace Civi\Core\SqlTrigger;

use Civi\Schema\EntityRepository;
use CRM_Core_DAO;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * @service html_entity_validator
 *
 * Many fields in CiviCRM are stored with an unusual encoding -- `htmlentities($value)`. Consequently, it
 * is illegal for these fields to store `<`. This implements a strict SQL-level validator.
 *
 * Setup:
 *
 * - cv -v ev 'Civi::service("html_entity_validator")->createUpdateProcedure();'
 * - cv -v flush -I triggers
 *
 */
class HtmlEntityValidator extends \Civi\Core\Service\AutoService implements EventSubscriberInterface {

  const PROCEDURE = 'civicrm_validate_html_text';

  public static function getSubscribedEvents() {
    return [
      'civi.core.install' => ['onInstall'],
      '&hook_civicrm_triggerInfo' => ['onTriggerInfo'],
    ];
  }

  public function onInstall(): void {
    // FIXME: This probably isn't a very good placement, but it'll with a quick-and-dirty check to see if things generally work.
    $this->createUpdateProcedure();
  }

  /**
   * @see CRM_Utils_Hook::triggerInfo()
   */
  public function onTriggerInfo(&$info, $tableFilter = NULL) {
    if (!$this->checkProcedureExists(static::PROCEDURE)) {
      return;
    }

    $entities = EntityRepository::getEntities();
    foreach ($entities as $entity) {
      if (empty($entity['table']) || empty($entity['getFields'])) {
        continue;
      }
      $fields = $entity['getFields']();
      foreach ($fields as $fieldName => $field) {
        if (preg_match('/^varchar/', $field['sql_type']) &&  strtolower($field['input_type'] ?? '') === 'text' && !\CRM_Core_HTMLInputCoder::isSkippedField($fieldName)) {
          // fprintf(STDERR, "%s.%s: %s, %s\n", $entity['table'], $fieldName, $field['sql_type'], $field['input_type']);
          $info[] = [
            'table' => $entity['table'],
            'when' => 'BEFORE',
            'event' => ['INSERT', 'UPDATE'],
            'sql' => sprintf('CALL %s(NEW.%s);', static::PROCEDURE, $fieldName),
          ];
        }
      }
    }
  }

  public function checkProcedureExists(string $name): bool {
    $r = CRM_Core_DAO::executeQuery('
        SELECT ROUTINE_NAME FROM information_schema.ROUTINES
        WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_NAME = %1 AND ROUTINE_TYPE = "PROCEDURE"',
      [
        1 => [$name, 'String'],
      ]
    );
    $v = $r->fetchAll();
    $result = !empty($v);
    return $result;
  }

  public function createUpdateProcedure($force = FALSE): string {
    $key = 'sql_' . static::PROCEDURE;
    // $getRevision = fn() => \Civi::settings()->get($key);
    // $setRevision = fn($value)  => \Civi::settings()->set($key, $value);
    // (new HtmlEntityValidator())->createUpdateProcedure(TRUE);
    if ($force) {
      $getRevision = fn() => NULL;
      $setRevision = fn($value) => TRUE;
    }
    else {
      $getRevision = fn() => \Civi::cache('long')->get($key);
      $setRevision = fn($value)  => \Civi::cache('long')->set($key, $value, 10 * 365 * 24 * 60 * 60);
    }

    $newCode = $this->getProcedureCode();
    $newRevision = hash('sha256', $newCode);
    $isUpdate = FALSE;
    if ($this->checkProcedureExists(static::PROCEDURE)) {
      if ($getRevision() === $newRevision) {
        return 'current';
      }
      else {
        $isUpdate = TRUE;
        CRM_Core_DAO::executeQuery('DROP PROCEDURE IF EXISTS ' . static::PROCEDURE);
        $setRevision('none');
      }
    }

    // \CRM_Utils_File::runSqlQuery(NULL, $newCode);
    CRM_Core_DAO::executeQuery($newCode);
    $setRevision($newRevision);
    return $isUpdate ? 'updated' : 'created';
  }

  public function getProcedureCode(): string {
    $name = static::PROCEDURE;
    return <<<EOPROC
CREATE PROCEDURE $name(IN input_value VARCHAR(255))
BEGIN
    IF input_value LIKE '%<%' THEN
        SIGNAL SQLSTATE '45000'
        SET MESSAGE_TEXT = 'Validation Error: Field contains forbidden character "<"';
    END IF;
END;
EOPROC;
  }

}

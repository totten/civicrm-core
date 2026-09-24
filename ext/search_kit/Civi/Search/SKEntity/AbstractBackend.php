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

namespace Civi\Search\SKEntity;

abstract class AbstractBackend {

  public function __construct(
    protected string $skEntity,
    protected string $realEntity,
    protected array $realParams,
    protected array $settings
  ) {}

  /**
   * Prepare storage space (for this particular SKEntity).
   *
   * Ex: If the backend stores a materialized view in SQL, this would run "CREATE TABLE".
   *
   * This executes whenever (a) new SKEntity is created or (b) SKEntity changes to a different backend or (c) SKEntity adds or drops columns.
   */
  abstract public function initialize(): void;

  /**
   * Clear all data (for this particular SKEntity).
   *
   * Ex: If the backend stores a materialized view in SQL, this would run "TRUNCATE TABLE".
   */
  abstract public function clearData(): void;

  /**
   * Perform the full search and store the data (for this particular SKEntity).
   *
   * Ex: If the backend stores a materialized view in SQL, this would run "INSERT INTO ... SELECT ... FROM $realEntity...".
   */
  abstract public function fillData(): void;

  /**
   * Destroy all data+metadata (for this particular SKEntity).
   *
   * Ex: If the backend stores a materialized view in SQL, this would run "DROP TABLE".
   *
   * This executes whenever (a) SKEntity is deleted or (b) SKEntity changes to a different backend or (c) SKEntity adds or drops columns.
   */
  abstract public function destroy(): void;

}

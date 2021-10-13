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

/**
 * What happens if an array is banished from the land of the living, but
 * fails to achieve True Nothingness? It may wander the Earth for some time as a ghost.
 *
 * Create a GhostArray if you want a stub that replaces an old/defunct array.
 * Any access to the array will cause a warning to be logged.
 */
class CRM_Utils_GhostArray implements ArrayAccess, IteratorAggregate, Countable {

  /**
   * @var string
   */
  private $warn;

  /**
   * @param callable $warn
   *   Whenever some pokes a ghost, the ghost says "Boo!"
   *   Ex: function($op, $field) { trigger_error(ts('Support for FOO has been removed. Cannot access field %1. Please convert to BAR.', [1 => $field])); }
   */
  public function __construct(callable $warn) {
    $this->warn = $warn;
  }

  public function offsetGet($offset) {
    ($this->warn)('get', $offset);
    return NULL;
  }

  public function offsetSet($offset, $value) {
    ($this->warn)('set', $offset, $value);
  }

  public function offsetUnset($offset) {
    ($this->warn)('unset', $offset);
  }

  public function offsetExists($offset) {
    return FALSE;
  }

  public function getIterator() {
    return new ArrayIterator([]);
  }

  public function count() {
    return 0;
  }

}

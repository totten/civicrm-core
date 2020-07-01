<?php
namespace Civi\Session;

/**
 * Class OverlayArray
 * @package Civi\Session
 *
 * An overlay-array is built as a layer on top of another array. Each field
 * may follow one of three policies:
 *
 * - passthru: Reads+writes will pass through to the parent
 * - isolate: The variable is stored in the overlay. Values in the parent are never used or sync'd.
 * - fork: The initial value may be read from the parent. Thereafter, any changes are isolated.
 */
class OverlayArray implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable {

  /**
   * Reference to the parent array.
   *
   * @var mixed
   *   The parent is conceptually an `array`. However, it could be
   *   a Traversable ArrayAccess.
   */
  private $parent;

  /**
   * @var array
   */
  private $localValues = [];

  /**
   * @var array
   *   List of potential keys - and how to handle them.
   *   Ex: ['field_a' => 'passthru', 'field_b' => 'fork']
   */
  private $schema = [];

  /**
   * @var array
   *   List of extant keys - and how to handle them.
   *   Ex: ['field_a' => 'passthru', 'field_c' => 'isolate']
   */
  private $activeKeyModes = NULL;

  /**
   * OverlayArray constructor.
   *
   * @param mixed $parent
   *   The parent is conceptually an `array`. However, it could be
   *   a Traversable ArrayAccess.
   * @param array $policies
   *   Ex: [
   *     'passthru' => ['field'_a', 'field_b'],
   *     'fork' => ['field_a', 'field_b'],
   *     'isolate' => ['field_a', 'field_b', '*'],
   *   ]
   *
   *   The special field name '*' is a wildcard matching all other/unnamed fields.
   *   If omitted, the default is to 'fork'.
   */
  public function __construct(&$parent, $policies) {
    $this->parent = &$parent;

    foreach (['passthru', 'fork', 'isolate'] as $mode) {
      foreach ($policies[$mode] ?? [] as $field) {
        $this->schema[$field] = $mode;
      }
    }

    if (!isset($this->schema['*'])) {
      $this->schema['*'] = 'fork';
    }

    $this->init();
  }

  public function &offsetGet($offset) {
    $this->init();
    switch ($this->activeKeyModes[$offset] ?? NULL) {
      case NULL:
        return NULL;

      case 'passthru':
        return $this->parent[$offset];

      default:
        return $this->localValues[$offset];
    }
  }

  public function offsetSet($offset, $value) {
    $this->init();
    $this->activeKeyModes[$offset] = $this->getMode($offset);
    switch ($this->activeKeyModes[$offset]) {
      case 'passthru':
        $this->parent[$offset] = $value;
        break;

      default:
        $this->localValues[$offset] = $value;
        break;
    }
  }

  public function offsetUnset($offset) {
    $this->init();
    switch ($this->activeKeyModes[$offset] ?? NULL) {
      case 'passthru':
        unset($this->parent[$offset]);
        break;

      default:
        unset($this->localValues[$offset]);
        break;
    }

    unset($this->activeKeyModes[$offset]);
  }

  public function offsetExists($offset) {
    $this->init();
    return isset($this->activeKeyModes[$offset]);
  }

  /**
   * @return int
   */
  public function count() {
    $this->init();
    return count($this->activeKeyModes);
  }

  /**
   * @return array
   */
  private function toArray() {
    $this->init();
    $array = [];
    foreach ($this->activeKeyModes as $key => $mode) {
      $array[$key] = $this->offsetGet($key);
    }
    return $array;
  }

  /**
   * @return \Generator
   */
  public function getIterator() {
    yield from $this->toArray();
  }

  public function jsonSerialize() {
    return $this->toArray();
  }

  /**
   * @param string $offset
   * @return string
   *   Ex: 'passthru', 'fork', 'isolate'
   */
  private function getMode($offset) {
    return $this->schema[$offset] ?? $this->schema['*'];
  }

  /**
   * If necessary, examine the $parent and $schema; initialize the $localValues and $activeKeyModes.
   */
  private function init() {
    if ($this->activeKeyModes !== NULL) {
      return;
    }

    $this->activeKeyModes = [];
    foreach ($this->parent as $pField => $pValue) {
      switch ($this->getMode($pField)) {
        case 'passthru':
          $this->activeKeyModes[$pField] = 'passthru';
          break;

        case 'fork':
          $this->activeKeyModes[$pField] = 'fork';
          $this->localValues[$pField] = $pValue;
          break;
      }
    }
  }

}
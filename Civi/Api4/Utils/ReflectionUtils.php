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

namespace Civi\Api4\Utils;

use Civi\Api4\Service\Spec\FieldSpec;

/**
 * Just another place to put static functions...
 */
class ReflectionUtils {

  /**
   * @param \Reflector|\ReflectionClass $reflection
   * @param string $type
   *   If we are not reflecting the class itself, specify "Method", "Property", etc.
   * @param array $vars
   *   Variable substitutions to perform in the docblock
   * @return array
   */
  public static function getCodeDocs($reflection, $type = NULL, $vars = []) {
    $comment = $reflection->getDocComment();
    foreach ($vars as $key => $val) {
      $comment = str_replace('$' . strtoupper(\CRM_Utils_String::pluralize($key)), \CRM_Utils_String::pluralize($val), $comment);
      $comment = str_replace('$' . strtoupper($key), $val, $comment);
    }
    $docs = self::parseDocBlock($comment);

    // Recurse into parent functions
    if (isset($docs['inheritDoc']) || isset($docs['inheritdoc'])) {
      unset($docs['inheritDoc'], $docs['inheritdoc']);
      $newReflection = NULL;
      try {
        if ($type) {
          $name = $reflection->getName();
          $reflectionClass = $reflection->getDeclaringClass()->getParentClass();
          if ($reflectionClass) {
            $getItem = "get$type";
            $newReflection = $reflectionClass->$getItem($name);
          }
        }
        else {
          $newReflection = $reflection->getParentClass();
        }
      }
      catch (\ReflectionException $e) {
      }
      if ($newReflection) {
        // Mix in
        $additionalDocs = self::getCodeDocs($newReflection, $type, $vars);
        if (!empty($docs['comment']) && !empty($additionalDocs['comment'])) {
          $docs['comment'] .= "\n\n" . $additionalDocs['comment'];
        }
        $docs += $additionalDocs;
      }
    }
    return $docs;
  }

  /**
   * @param string $comment
   * @return array
   */
  public static function parseDocBlock($comment) {
    $info = [];
    $param = NULL;
    foreach (preg_split("/((\r?\n)|(\r\n?))/", $comment) as $num => $line) {
      if (!$num || strpos($line, '*/') !== FALSE) {
        continue;
      }
      $line = ltrim(trim($line), '*');
      if (strlen($line) && $line[0] === ' ') {
        $line = substr($line, 1);
      }
      if (strpos(ltrim($line), '@') === 0) {
        $words = explode(' ', ltrim($line, ' @'));
        $key = array_shift($words);
        $param = NULL;
        if ($key == 'var') {
          $info['type'] = explode('|', $words[0]);
        }
        elseif ($key == 'return') {
          $info['return'] = explode('|', $words[0]);
        }
        elseif ($key == 'options' || $key == 'ui_join_filters') {
          $val = str_replace(', ', ',', implode(' ', $words));
          $info[$key] = explode(',', $val);
        }
        elseif ($key == 'throws' || $key == 'see') {
          $info[$key][] = implode(' ', $words);
        }
        elseif ($key == 'param' && $words) {
          $type = $words[0][0] !== '$' ? explode('|', array_shift($words)) : NULL;
          $param = rtrim(array_shift($words), '-:()/');
          $info['params'][$param] = [
            'type' => $type,
            'description' => $words ? ltrim(implode(' ', $words), '-: ') : '',
            'comment' => '',
          ];
        }
        else {
          // Unrecognized annotation, but we'll duly add it to the info array
          $val = implode(' ', $words);
          $info[$key] = strlen($val) ? $val : TRUE;
        }
      }
      elseif ($param) {
        $info['params'][$param]['comment'] .= $line . "\n";
      }
      elseif ($num == 1) {
        $info['description'] = ucfirst($line);
      }
      elseif (!$line) {
        if (isset($info['comment'])) {
          $info['comment'] .= "\n";
        }
        else {
          $info['comment'] = NULL;
        }
      }
      // For multi-line description.
      elseif (count($info) === 1 && isset($info['description']) && substr($info['description'], -1) !== '.') {
        $info['description'] .= ' ' . $line;
      }
      else {
        $info['comment'] = isset($info['comment']) ? "{$info['comment']}\n$line" : $line;
      }
    }
    if (isset($info['comment'])) {
      $info['comment'] = rtrim($info['comment']);
    }
    return $info;
  }

  /**
   * List all traits used by a class and its parents.
   *
   * @param object|string $class
   * @return array
   */
  public static function getTraits($class) {
    $traits = [];
    // Get traits of this class + parent classes
    do {
      $traits = array_merge(class_uses($class), $traits);
    } while ($class = get_parent_class($class));
    // Get traits of traits
    foreach ($traits as $trait => $same) {
      $traits = array_merge(class_uses($trait), $traits);
    }
    return $traits;
  }

  /**
   * Examine a class, obtaining a full/parsed list of fields.
   *
   * TIP: This includes lightweight caching. If you only read properties on a few
   * business-classes (e.g. forms or entities) that are relevant/proportionate to
   * the current request, then it should be ample. Alternatively, if you need a broad
   * dataset (scanning many classes/forms/entities), then use a higher-level cache.
   *
   * @param string|\ReflectionClass $class
   * @param int $filter
   *   Ex: \ReflectionProperty::IS_PUBLIC ,\ReflectionProperty::IS_PRIVATE
   * @param string $fieldSpecClass
   *   Ex: '\Civi\WorkflowMessage\FieldSpec' or 'Civi\Api4\Service\Spec\FieldSpec'
   * @return array
   *   Several instances of $fieldSpecType.
   * @throws \ReflectionException
   */
  public static function getFields($class, int $filter, string $fieldSpecClass = FieldSpec::class) {
    $className = $class instanceof \ReflectionClass ? $class->getName() : $class;
    $cacheKey = $className . '::' . $filter . '::' . $fieldSpecClass;

    if (!isset(\Civi::$statics[__CLASS__][$cacheKey])) {
      $fields = [];
      $classObj = $class instanceof \ReflectionClass ? $class : new \ReflectionClass($class);
      // WISHLIST: Detect '@property' annotations in the class-level.
      foreach ($classObj->getProperties($filter) as $property) {
        if ($property->isStatic() || $property->getName()[0] === '_') {
          continue;
        }
        $parsed = ReflectionUtils::getCodeDocs($property, 'Property');
        $field = new $fieldSpecClass();
        $field->setName($property->getName())->loadArray($parsed);
        $fields[$field->getName()] = $field;
      }
      \Civi::$statics[__CLASS__][$cacheKey] = $fields;
    }

    return \Civi::$statics[__CLASS__][$cacheKey];
  }

  /**
   * Find any methods in this class which match the given prefix.
   *
   * @param string|\ReflectionClass $class
   * @param string $prefix
   * @param int|null $filter
   *   Ex: \ReflectionMethod::IS_PUBLIC, \ReflectionMethod::IS_PRIVATE
   * @return \ReflectionMethod[]
   * @throws \ReflectionException
   */
  public static function findMethodPrefix($class, string $prefix, ?int $filter = NULL): array {
    $clazz = is_string($class) ? new \ReflectionClass($class) : $class;

    $methods = array_filter(
      $clazz->getMethods($filter),
      function(\ReflectionMethod $m) use ($prefix) {
        return \CRM_Utils_String::startsWith($m->getName(), $prefix);
      }
    );
    usort($methods, function ($a, $b) {
      return strnatcmp($a->getName(), $b->getName());
    });
    return $methods;
  }

}

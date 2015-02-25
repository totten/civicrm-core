<?php

/**
 * Conver the SQL format of timestamp columns.
 */
class TimestampAdapter {
  /**
   * @param string $oldMode
   *   SQL column type (TIMESTAMP or DATETIME)
   * @param string $newMode
   *   SQL column type (TIMESTAMP or DATETIME)
   * @param array $metadata
   *   Setting descriptor.
   */
  public function update($oldMode, $newMode, $metadata) {
    foreach ($tables as $table) {
      foreach ($fields as $field) {
        if ($field->pseudotimestamp) {
          $currentType = "desc $table , $field";
          if ($currentType != $targetType) {
          }
            "alter table $table $field to $targetType";
          }
      }
    }
  }

}

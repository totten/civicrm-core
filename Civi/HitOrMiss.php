<?php
namespace Civi;

class HitOrMiss {

  public static $counts = [];

  public static function inc(string $name): void {
    static::$counts[$name] = (static::$counts[$name] ?? 0) + 1;
  }

}

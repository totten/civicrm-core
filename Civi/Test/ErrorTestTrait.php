<?php

namespace Civi\Test;

trait ErrorTestTrait {

  /**
   * Call some method. Capture and return PHP errors that arise during the method call.
   *
   * Examples:
   *   [$log] = $this->captureErrors(E_USER_DEPRECATED, function() { doStuff(); doMore(); doLess(); });
   *   [$log, $result] = $this->captureErrors(E_USER_DEPRECATED, 'my_func', 'my_value');
   *
   * @param int $severityMask
   *   Mask identifying which errors to capture.
   *   Ex: E_USER_DEPRECATED|E_USER_WARNING
   * @param callable $runMethod
   *   A callable function. We will only capture errors while running this function.
   * @param array $runArgs
   *   Optionally, pass-through arguments to the $runMethod.
   * @return array
   *   Tuple of [$log, $runResult] - ie the error-message-log and the result of the $runMethod.
   *   It is likely that many caller will ignore the
   */
  protected function captureErrors(int $severityMask, $runMethod, ...$runArgs): array {
    $log = [];
    $oldHandler = set_error_handler(function ($errorNum, $errorMsg) use (&$oldHandler, &$log, $severityMask) {
      if ($errorNum & $severityMask) {
        $log[] = $errorMsg;
      }
      else {
        $oldHandler(...func_get_args());
      }
    });

    try {
      $runResult = $runMethod(...$runArgs);
    }
    finally {
      restore_error_handler();
    }
    return [$log, $runResult];
  }

}

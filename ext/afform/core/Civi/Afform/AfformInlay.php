<?php

namespace Civi\Afform;

use Civi\Inlay\ApiRequest;

class AfformInlay extends \Civi\Inlay\Type {

  public static $typeName = 'Afform';

  public static $machineName = 'afform';

  public static $defaultConfig = [
    'formName' => '',
  ];

  public function getInitData(): array {
    return [];
  }

  public function processRequest(ApiRequest $request): array {
    return [];
  }

  public function getExternalScript(): string {
    return '';
  }

}

<?php

namespace Civi\Afform;

use Civi\Angular\AngularLoader;
use Civi\Inlay\ApiRequest;

class AfformInlay extends \Civi\Inlay\Type {

  public static $typeName = 'Afform';

  public static $machineName = 'afform';

  public static $defaultConfig = [
    'formName' => '',
  ];

  public function getInitData(): array {
    return [
      'init' => $this->getInitFunc(),
    ];
  }

  public function processRequest(ApiRequest $request): array {
    return [];
  }

  public function getExternalScript(): string {
    $result = '';

    $writeln = function($msg, ...$args) use (&$result) {
      $result .= sprintf("document.write(%s);\n", json_encode(
        sprintf($msg, ...$args) . "<br/>\n"
      ));
    };

    $writeln('TODO: Render form "%s"', $this->config['formName']);

    $regionName = 'af-inlay-' . $this->config['formName'];
    $region = \CRM_Core_Region::instance($regionName);

    $loader = new AngularLoader();
    $loader->setRegion($regionName);
    $loader->setPageName($this->config['formName'] . '/inlay');
    $loader->addModules($this->config['formName']);
    // $loader->useApp();
    \Civi::dispatcher()->addListener('civi.region.render', [$loader, 'onRegionRender']);

    $region->render('', FALSE);
    foreach ($region->getAll() as $snippet) {
      $writeln("TODO: Load resource \"%s\" (%s)", $snippet['name'], $snippet['type']);
    }

    $region->clear();
    return sprintf('window.%s = window.%s || function(){ %s };', $this->getInitFunc(), $this->getInitFunc(), $result);
  }

  protected function getInitFunc(): string {
    // If you have multiple inlays in the same page, each should have a different init function.
    return 'init_' . $this->instanceData['public_id'];
  }

}

<?php

namespace Civi\Afform;

use Civi\Angular\AngularLoader;
use Civi\Inlay\ApiRequest;
use CRM_Afform_ExtensionUtil as E;

class AfformInlay extends \Civi\Inlay\Type {

  public static $typeName = 'Afform';

  public static $machineName = 'afform';

  public static $defaultConfig = [
    'formName' => '',
  ];

  public function getInitData(): array {
    return [
      'init' => $this->getInitFunc(),
      'resources' => iterator_to_array($this->filterResources($this->getResources($this->config['formName']))),
    ];
  }

  public function processRequest(ApiRequest $request): array {
    return [];
  }

  public function getExternalScript(): string {
    $result = '';
    $result .= file_get_contents(E::path('js/resource-loader.js'));
    $result .= "const ldr = new CrmResourceLoader();";
    $result .= "ldr.addResources(inlay.initData.resources);";
    $result .= "inlay.script.insertAdjacentElement('afterend', ldr.uiRoot);\n";
    return sprintf('window.%s = window.%s || function(inlay){ console.log("inlay", inlay); %s };', $this->getInitFunc(), $this->getInitFunc(), $result);
  }

  protected function filterResources(iterable $snippets): iterable {
    yield from [];
    foreach ($snippets as $snippet) {
      switch ($snippet['type']) {
        case 'markup':
        case 'styleUrl':
        case 'scriptUrl':
          yield ['t' => ucfirst($snippet['type']), 'u' => $snippet[$snippet['type']]];
          break;

        case 'styleFile':
        case 'scriptFile':
          yield ['t' => 'Markup', 'u' => sprintf('<p>FIXME: Map scriptFile/styleFile to scriptUrl/styleUrl for %s</p>', htmlentities($snippet['name']))];
          // yield [$snippet['type'] => $snippet[$snippet['type']]];
          break;

        default:
          // $writeln("TODO: Load resource \"%s\" (%s)", htmlentities($snippet['name']), htmlentities($snippet['type']));
          break;
      }
    }
  }

  protected function getResources(string $moduleName): array {
    $defaultMarkup = sprintf('<p>Render form "%s"</p>', $this->config['formName']);

    $regionName = 'af-inlay-' . $moduleName;
    $region = \CRM_Core_Region::instance($regionName);

    $loader = new AngularLoader();
    $loader->setRegion($regionName);
    $loader->setPageName('inlay/' . $moduleName);
    $loader->addModules($moduleName);
    // $loader->useApp();
    \Civi::dispatcher()->addListener('civi.region.render', [$loader, 'onRegionRender']);
    $region->render($defaultMarkup, FALSE);
    $all = (array) $region->getAll();

    $region->clear();
    return $all;
  }

  protected function getInitFunc(): string {
    // If you have multiple inlays in the same page, each should have a different init function.
    return 'init_' . $this->instanceData['public_id'];
  }

}

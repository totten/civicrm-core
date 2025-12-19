<?php

/**
 * Read `js/component-bundle.json`. The file is a listing of logical bundles.
 *
 * ## Example: Create different bundles for admin (backend) widgets and user (frontend) widgets.
 * {
 *   backend: {components: ['foo-admin', 'bar-admin', 'admin-helper-widget']},
 *   frontend: {components: ['foo-widget', 'bar-widget']}
 * }
 *
 * ## Example: Put all components into one bundle
 * FIXME: Need wildcard or regex
 *
 * If asset-caching is disabled, then serve files directly.
 *
 * @mixinName component-bundle-json
 * @mixinVersion 1.0.0
 * @since 6.11
 *
 * Note: Requires component-js@1
 * Note: To actually use bundling, this requires CiviCRM 6.11. However, it will
 * degrade gracefully on earlier versions. (You just don't get the optimization.)
 */

namespace Civi\Mixin\ComponentBundleJsV1;

use Civi;

class ComponentBundles {

  protected \CRM_Extension_MixInfo $mixInfo;

  public ?array $config;

  public static function instance(\CRM_Extension_MixInfo $mixInfo): ?ComponentBundles {
    if (!isset(Civi::$statics[__CLASS__][$mixInfo->longName])) {
      Civi::$statics[__CLASS__][$mixInfo->longName] = new static($mixInfo);
    }
    $v = Civi::$statics[__CLASS__][$mixInfo->longName];
    return $v->isActive() ? $v : NULL;
  }

  public function __construct(\CRM_Extension_MixInfo $mixInfo) {
    $this->mixInfo = $mixInfo;
    $configFile = $this->mixInfo->getPath('js/component-bundle.json');
    if (file_exists($configFile)) {
      // If the file exists, then let's go ahead and do some basic validation (even if you're debugging).
      $this->config = json_decode(file_get_contents($configFile), TRUE);
      if ($this->config === NULL) {
        throw new \CRM_Core_Exception("Malformed bundle configuration: $configFile");
      }
    }
  }

  public function isActive(): bool {
    return $this->mixInfo->isActive()
      && (!empty($this->config))
      && Civi::service('asset_builder')->isCacheEnabled()
      && method_exists(Civi\Esm\ImportMap::class, 'addPrefixUrl');
  }

  protected function getLogicalUrl(string $bundleName): string {
    return $this->mixInfo->longName . '/js/component/_bundle-' . $bundleName . '.js';
  }

  protected function getPhysicalUrl(string $bundleName): string {
    return (string) Civi::url('assetBuilder://component-bundle.js')->addQuery([
      'ext' => $this->mixInfo->longName,
      'bundle' => $bundleName,
    ]);
  }

  /**
   * @return array
   *   Ex: ['my-tag' => 'org.myextension/js/component/_bundle-NAME.js']
   */
  public function createTagMap(): array {
    $tags = [];
    foreach ($this->config as $bundleName => $bundleConfig) {
      $logicalUrl = $this->getLogicalUrl($bundleName);
      foreach ($bundleConfig['customElements'] ?? [] as $tagName) {
        $tags[$tagName] = $logicalUrl;
      }
    }
    return $tags;
  }

  /**
   * @return array
   *   Ex: ['org.myextension/js/component/_bundle-NAME.js' => '/public/assets/component-bundle.YYY.json']
   */
  public function createImportMap(): array {
    $importMap = [];
    foreach ($this->config as $bundleName => $bundleConfig) {
      $importMap[$this->getLogicalUrl($bundleName)] = $this->getPhysicalUrl($bundleName);
    }
    return $importMap;
  }

  public function render(string $bundleName): string {
    $tagNames = $this->config[$bundleName]['customElements'] ?? [];
    $buf = [];
    foreach ($tagNames as $tagName) {
      $tagFile = $this->mixInfo->getPath('js/component/' . $tagName . '.js');
      if (file_exists($tagFile)) {
        $buf[] = file_get_contents($tagFile);
      }
    }
    return implode("\n", $buf);
  }

}

/**
 * @param \CRM_Extension_MixInfo $mixInfo
 * @param \CRM_Extension_BootCache $bootCache
 */
return function ($mixInfo, $bootCache) {

  Civi::dispatcher()->addListener('&hook_civicrm_componentJsPaths', function(array &$components) use ($mixInfo) {
    if (!($bundles = ComponentBundles::instance($mixInfo))) {
      return;
    }

    foreach ($bundles->createTagMap() as $tag => $logicalUrl) {
      $components[$tag] = $logicalUrl;
    }
  }, 500);

  Civi::dispatcher()->addListener('&hook_civicrm_esmImportMap', function(\Civi\Esm\ImportMap $importMap) use ($mixInfo) {
    if (!($bundles = ComponentBundles::instance($mixInfo))) {
      return;
    }

    foreach ($bundles->createImportMap() as $logicalUrl => $physicalUrl) {
      $importMap->addPrefixUrl($logicalUrl, $physicalUrl);
    }
  }, 500);

  Civi::dispatcher()->addListener('&hook_civicrm_buildAsset', function($asset, $params, &$mimeType, &$content) use ($mixInfo) {
    // Most assets aren't ours... skip them without even instantiating the ComponentBundles helper.
    if ($asset !== 'component-bundle.js' || $params['ext'] !== $mixInfo->longName) {
      return;
    }

    if (!($bundles = ComponentBundles::instance($mixInfo))) {
      return;
    }

    $mimeType = 'application/javascript';
    $content = $bundles->render($params['bundle']);
  }, 500);

};

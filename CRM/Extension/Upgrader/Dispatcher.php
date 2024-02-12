<?php

/**
 * @link https://gist.github.com/totten/be871a6682ab02116a2728002f5eee2c
 * // TODO: The official core version should implement CRM_Extension_Upgrader_Interface
 */
class _TODO_CRM_Extension_Upgrader_Dispatcher {

  /**
   * @var string|null
   */
  private $key;

  /**
   * Optionally delegate to "CRM_Myext_Upgrader" or "Civi\Myext\Upgrader".
   *
   * @var \CRM_Extension_Upgrader_Interface|null
   */
  private $delegate;

  public function init(array $params) {
    $this->key = $params['key'];
    if ($info = $this->getInfo()) {
      if ($class = $this->getDelegateUpgraderClass($info)) {
        $this->delegate = new $class();
        $this->delegate->init($params);
      }
    }
  }

  public function notify(string $event, array $params = []) {
    $info = $this->getInfo();
    if (!$info) {
      return;
    }

    // Use $info->mixins and the $funcFiles,$mixInfos to find the *.lifecycle.php sibligs

    // Then delegate $event to each *.lifecycle.php and to getDefaultUpgrader()

    if ($this->delegate) {
      $this->delegate->notify($event, $params);
    }
  }

  public function getInfo(): ?CRM_Extension_Info {
    try {
      return CRM_Extension_System::singleton()->getMapper()->keyToInfo($this->key);
    }
    catch (CRM_Extension_Exception_ParseException $e) {
      Civi::log()->error("Parse error in extension " . $this->key . ": " . $e->getMessage());
      return NULL;
    }
  }

  public function getMixins() {
    // For v5.71-ish, we wouldn't be loading this file. We'd use the core version.
    if (class_exists('CRM_Extension_MixinScanner')) {
      // (v5.45-5.71-ish) Get current mixin
      $system = CRM_Extension_System::singleton();
      [$funcFiles, $mixInfos] = (new CRM_Extension_MixinScanner($system->getMapper(), $system->getManager(), TRUE))->build();
      $lifecycleFiles = preg_replace(';mixin\.php$;', 'lifecycle.php', $funcFiles);
      $lifecycleFiles = array_filter($lifecycleFiles, 'CRM_Utils_File::isIncludable');
      $mixInfo = $mixInfos[$this->key];

    }
    else {
      // (v5.38-v5.44) As in the polyfill loader, simply load bundled mixins.
      $baseDir = CRM_Extension_System::singleton()->getMapper()->keyToPath($this->key);
      $files = (array) glob("$baseDir/*@*.lifecycle.php");
    }

    // foreach ($funcFiles as $funcFile) {
    //   $f = require $funcFile;
    //   $ref = new ReflectionFunction($f);
    //   $comment = $ref->getDocComment();
    //   printf("%s\n\n", $comment);
    // }

    print_r([
      'lifecycleFiles' => $lifecycleFiles,
      'mixInfo' => $mixInfo,
    ]);

  }

  public function getDelegateUpgraderClass(CRM_Extension_Info $info): ?string {
    $candidates = [];

    if (!empty($info->civix['namespace'])) {
      $namespace = $info->civix['namespace'];
      $candidates[] = sprintf('%s_Upgrader', str_replace('/', '_', $namespace));
      $candidates[] = sprintf('%s\\Upgrader', str_replace('/', '\\', $namespace));
    }

    foreach ($candidates as $candidate) {
      if (class_exists($candidate)) {
        return $candidate;
      }
    }

    return NULL;
  }

}

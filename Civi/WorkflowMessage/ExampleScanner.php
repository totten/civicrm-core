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

namespace Civi\WorkflowMessage;

use Civi\Test\Invasive;

/**
 * @internal
 */
class ExampleScanner {

  /**
   * @var \CRM_Utils_Cache_Interface
   */
  protected $cache;

  /**
   * @var string
   */
  protected $cacheKey;

  /**
   * ExampleScanner constructor.
   * @param \CRM_Utils_Cache_Interface|NULL $cache
   */
  public function __construct(?\CRM_Utils_Cache_Interface $cache = NULL) {
    $this->cache = $cache ?: \Civi::cache('short' /* long */);
    $this->cacheKey = \CRM_Utils_String::munge(__CLASS__);
  }

  /**
   * @return array
   * @throws \ReflectionException
   */
  public function findAll(): array {
    $all = $this->cache->get($this->cacheKey);
    if ($all === NULL) {
      $all = [];
      // FIXME
      $wfClasses = Invasive::call([WorkflowMessage::class, 'getWorkflowNameClassMap']);
      foreach ($wfClasses as $workflow => $class) {
        $classFile = (new \ReflectionClass($class))->getFileName();
        $classDir = preg_replace('/\.php$/', '', $classFile);
        if (is_dir($classDir)) {
          $files = (array) glob($classDir . "/*.ex.json");
          foreach ($files as $file) {
            $name = $workflow . '_' . preg_replace('/\.ex.json/', '', basename($file));
            $data = \GuzzleHttp\json_decode(file_get_contents($file), 1);
            $tags = !empty($data['tags']) ? \CRM_Utils_Array::implodePadded($data['tags']) : '';

            $all[$name] = [
              'name' => $name,
              'title' => $name,
              'workflow' => $workflow,
              'tags' => $tags,
              'file' => $file,
              // ^^ relativize?
            ];
          }
        }
      }
    }
    return $all;
  }

}

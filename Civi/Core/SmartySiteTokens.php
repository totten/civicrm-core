<?php

namespace Civi\Core;

use Civi\Core\Service\AutoService;
use CRM_Core_Smarty;

/**
 * @service civi.siteTokens.smarty
 */
class SmartySiteTokens extends AutoService implements HookInterface {

  const PREFIX = '_render_';

  const STUB = '{content}';

  private function renderBlock(array $block, $params, $content, &$smarty, &$repeat) {
    if ($repeat) {
      return NULL;
    }
    $format = 'body_html'; // FIXME: Detect type

    return str_replace(static::STUB, $content, $block[$format]);
  }

  public function hook_civicrm_config(&$config, ?array $flags = NULL) {
    if (empty($flags['civicrm'])) {
      return;
    }
    foreach ($this->getBlockNames() as $block) {
      $this->registerBlock($block);
    }
  }

  private function registerBlock(string $name): void {
    $smarty = CRM_Core_Smarty::singleton();
    $impl = get_class($this) . '::' . static::PREFIX . $name;
    if ($smarty->getVersion() <= 2) {
      $smarty->register_block($name, $impl, FALSE);
    }
    else {
      $smarty->registerPlugin('block', $name, $impl, FALSE);
    }
  }


  /**
   * List of names for custom blocks.
   *
   * @return string[]
   */
  private function getBlockNames(): array {
    return ['myPage'];
    // $result = \Civi\Api4\SiteToken::get(FALSE)
    //   // ->addWhere('token_type_id:name', '=', 'Block')
    //   ->execute();
    // ensure $results are well-formed names
    // // Debatable: maybe this belongs in a cache
  }

  private function getBlock(string $name): array {
    // $result = \Civi\Api4\SiteToken::get(FALSE)
    //   ->addWhere('token_type_id:name', '=', 'Block')
    //   ->addWhere('name', '=', $name)
    //   ->execute()
    //   ->single();

    return [
      'name' => 'myPage',
      'label' => 'My Page Wrapper',
      'body_html' => '<HTML><BODY>\n<b>DOLPHIN ACTION LEAGUE!</b><br/>\n{content}\n</BODY></HTML>',
      'body_text' => "DOLPHIN ACTION LEAGUE!\n{content}",
      'is_reserved' => 0,
      'is_active' => 0,
    ];
  }

  public static function __callStatic(string $name, array $args) {
    if (!str_starts_with($name, static::PREFIX)) {
      return;
    }
    $name = substr($name, strlen(static::PREFIX));
    $service = \Civi::service('civi.siteTokens.smarty');
    $block = $service->getBlock($name);
    return $service->renderBlock($block, ...$args);
  }

}

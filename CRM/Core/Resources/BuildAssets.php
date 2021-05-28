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

use Civi\Core\Event\GenericHookEvent;

/**
 * This file defines some commonly used dynamic assets.
 */
class CRM_Core_Resources_BuildAssets implements \Symfony\Component\EventDispatcher\EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      'hook_civicrm_buildAsset::crm-menubar.css' => 'renderMenubarStylesheet',
      'hook_civicrm_buildAsset::crm-l10n.js' => 'renderL10nJs',
    ];
  }

  /**
   * @param \Civi\Core\Event\GenericHookEvent $e
   * @see \CRM_Utils_Hook::buildAsset()
   */
  public static function renderMenubarStylesheet(GenericHookEvent $e) {
    $e->mimeType = 'text/css';
    $content = '';
    $config = CRM_Core_Config::singleton();
    $cms = strtolower($config->userFramework);
    $cms = $cms === 'drupal' ? 'drupal7' : $cms;
    $items = [
      'bower_components/smartmenus/dist/css/sm-core-css.css',
      'css/crm-menubar.css',
      "css/menubar-$cms.css",
    ];
    foreach ($items as $item) {
      $content .= file_get_contents(CRM_Core_Resources::singleton()->getPath('civicrm', $item));
    }
    $params = $e->params;
    // "color" is deprecated in favor of the more specific "menubarColor"
    $menubarColor = $params['color'] ?? $params['menubarColor'];
    $vars = [
      '$resourceBase' => rtrim($config->resourceBase, '/'),
      '$menubarHeight' => $params['height'] . 'px',
      '$breakMin' => $params['breakpoint'] . 'px',
      '$breakMax' => ($params['breakpoint'] - 1) . 'px',
      '$menubarColor' => $menubarColor,
      '$menuItemColor' => $params['menuItemColor'] ?? $menubarColor,
      '$highlightColor' => $params['highlightColor'] ?? CRM_Utils_Color::getHighlight($menubarColor),
      '$textColor' => $params['textColor'] ?? CRM_Utils_Color::getContrast($menubarColor, '#333', '#ddd'),
    ];
    $vars['$highlightTextColor'] = $params['highlightTextColor'] ?? CRM_Utils_Color::getContrast($vars['$highlightColor'], '#333', '#ddd');
    $e->content = str_replace(array_keys($vars), array_values($vars), $content);
  }

  /**
   * Create dynamic script for localizing js widgets.
   */
  public static function renderL10nJs(GenericHookEvent $e) {
    $e->mimeType = 'application/javascript';
    $params = $e->params;
    $params += [
      'contactSearch' => json_encode($params['includeEmailInName'] ? ts('Search by name/email or id...') : ts('Search by name or id...')),
      'otherSearch' => json_encode(ts('Enter search term or id...')),
      'entityRef' => \CRM_Core_Resources::getEntityRefMetadata(),
    ];
    $e->content = CRM_Core_Smarty::singleton()->fetchWith('CRM/common/l10n.js.tpl', $params);
  }

}

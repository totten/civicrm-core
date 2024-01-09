<?php

/**
 * Apply a full range of `List-Unsubscribe` header options.
 *
 * Supports both Flexmailer-delivery and BAO-Delivery, so it's a little ugly.
 *
 * @service civi.mailing.listUnsubscribe
 * @link https://datatracker.ietf.org/doc/html/rfc8058
 */
class CRM_Mailing_Service_ListUnsubscribe extends \Civi\Core\Service\AutoService implements \Symfony\Component\EventDispatcher\EventSubscriberInterface {

  public static function getSubscribedEvents() {
    return [
      '&hook_civicrm_alterMailParams' => ['alterMailParams', 1000],
    ];
  }

  /**
   * @see \CRM_Utils_Hook::alterMailParams()
   */
  public function alterMailParams(&$params, $context = NULL): void {
    if (!in_array($context, ['civimail', 'flexmailer'])) {
      return;
    }

    // Quick and dirty policy -- always use One-Click
    if (preg_match(';^<mailto:u\.(\d+)\.(\d+)\.[^>]*>$;', $params['List-Unsubscribe'], $m)) {
      $mailto = $params['List-Unsubscribe'];
      $eventQueueId = $m[2];
      $url = '<' . Civi::url('civicrm/mailing/one-click')->addQuery([
        'jwt' => Civi::service('crypto.jwt')->encode([
          'scope' => 'civi.unsubscribe',
          'qid' => $eventQueueId,
        ]),
      ]) . '>';
      $params['headers']['List-Unsubscribe-Post'] = 'List-Unsubscribe=One-Click';
      $params['headers']['List-Unsubscribe'] = "$mailto $url";
      unset($params['List-Unsubscribe']);
    }
    else {
      \Civi::log()->warning('Failed to set final value of List-Unsubscribe');
    }
  }

}

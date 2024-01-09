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

/**
 * Implement a "one-click unsubscribe" end-point (civicrm/mailing/one-click)
 * for CiviMail per RFC 8058.
 *
 * This end-point receives an HTTP POST with one URI query parameter
 * (`$_GET['jwt']` or `$_REQUEST['jwt']`). The parameter is a JSON Web Token
 * indicating the contact and mailing-list.
 *
 * @link https://datatracker.ietf.org/doc/html/rfc8058
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Mailing_Page_OneClick extends CRM_Core_Page {

  const TTL = '+1 year';

  // /**
  //  * Generate a token for use by the unsubscribe endpoint.
  //  *
  //  * @param int $contactID
  //  * @param int $mailingID
  //  * @param int $groupID
  //  * @return string
  //  * @throws \Civi\Crypto\Exception\CryptoException
  //  */
  // public static function createToken(int $contactID, int $mailingID, int $groupID): string {
  //   $jwt = Civi::service('crypto.jwt');
  //   return $jwt->encode([
  //     'scope' => 'civi.unsubscribe',
  //     'cid' => $contactID,
  //     'mid' => $mailingID,
  //     'gid' => $groupID,
  //     'exp' => CRM_Utils_Time::strtotime(static::TTL),
  //   ]);
  // }

  public function run() {
    $token = $_REQUEST['jwt'] ?? '';
    // $token = static::createToken(1, 1, 1); // FIXME

    try {
      $claims = Civi::service('crypto.jwt')->decode($token);
    }
    catch (Throwable $t) {
      $this->respond(500, 'Invalid JWT. ' . $t->getMessage());
    }

    if ($claims['scope'] === 'civi.unsubscribe' && isset($claims['qid'])) {
      $this->unsubscribeByQueueId($claims['qid']);
      $this->respond(200, 'OK');
    }

    $this->respond(500, 'Invalid JWT. Claims did not describe the action to take.');
  }

  protected function respond(int $status, string $message): void {
    $response = new \GuzzleHttp\Psr7\Response($status, ['Content-Type' => 'text/plain'], $message . "\n");
    CRM_Utils_System::sendResponse($response);
  }

  /**
   * @param array $claims
   *   Validated JWT claims
   */
  public function unsubscribeByQueueId(int $queueId) {
    $records = CRM_Core_DAO::executeQuery('SELECT job_id, hash FROM civicrm_mailing_event_queue WHERE id = %1', [
      1 => [$queueId, 'Positive'],
    ])->fetchAll();
    foreach ($records as $record) {
      $groups = CRM_Mailing_Event_BAO_MailingEventUnsubscribe::unsub_from_mailing($record['job_id'], $queueId, $record['hash']);
      if (!empty($groups)) {
        CRM_Mailing_Event_BAO_MailingEventUnsubscribe::send_unsub_response($queueId, $groups, FALSE, $record['job_id']);
      }
    }
  }

}

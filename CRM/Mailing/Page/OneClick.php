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
 * (`$_REQUEST['jwt']`). The parameter is a JSON Web Token describing
 * the action, eg
 *
 *   - Unsubscribe based on a CiviMail event-queue ID.
 *     jwt->encode(['scope' => 'civi.unsubscribe', 'qid' => 100])
 *   - Unsubscribe based on ContactID and GroupID.
 *     jwt->encode(['scope' => 'civi.unsubscribe', 'cid' => 200, 'gid' => 300, 'msgtpl' => 400])
 *
 * @link https://datatracker.ietf.org/doc/html/rfc8058
 *
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Mailing_Page_OneClick extends CRM_Core_Page {

  // \CRM_Utils_Time::time() + (\Civi::settings()->get('checksum_timeout') * 24 * 60 * 60)
  const TTL = '+1 year';

  public function run() {
    $token = $_REQUEST['jwt'] ?? '';

    try {
      $claims = Civi::service('crypto.jwt')->decode($token);

      if ($claims['scope'] === 'civi.unsubscribe' && isset($claims['qid'])) {
        $this->unsubscribeByQueueId($claims['qid']);
        $this->respond(200, 'OK');
      }

      if ($claims['scope'] === 'civi.unsubscribe' && isset($claims['cid'], $claims['gid'])) {
        $this->unsubscribeByContactGroupId($claims['cid'], $claims['gid']);
        $this->respond(200, 'OK');
      }

      $this->respond(500, 'Invalid JWT. Claims did not describe the action to take.');
    }
    catch (CRM_Core_Exception_PrematureExitException $e) {
      // In unit-tests, responses are indicated via exception.
      throw $e;
    }
    catch (Throwable $t) {
      $this->respond(500, 'Failed to process request. ' . $t->getMessage());
    }
  }

  public function unsubscribeByQueueId(int $queueId): void {
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

  public function unsubscribeByContactGroupId(int $contactId, int $groupId): void {
    throw new \CRM_Core_Exception("Not implemented: unsubscribeByContactGroupId");
    // Not on critical path right (for CiviMail). May be useful of unsubs in transactional emails, scheduled-reminders, etc.
    // \Civi\Api4\GroupContact::delete(FALSE)
    //   ->addWhere('contact_id', '=', $contactId)
    //   ->addWhere('group_id', '=', $contactId)
    //   ->execute();
    // We should also send a confirmation message -- either a MessageTemplate or a MailingComponent (like send_unsub_response()).
    // We should probably have an input like $claims['msgtpl'] or $claims['mailCompId'].
    // But this may involve some other (REF) -- e.g. on send_unsub_response().
  }

  protected function respond(int $status, string $message): void {
    $response = new \GuzzleHttp\Psr7\Response($status, ['Content-Type' => 'text/plain'], $message . "\n");
    CRM_Utils_System::sendResponse($response);
  }

}

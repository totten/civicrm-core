<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
 +--------------------------------------------------------------------+
 | This file is a part of CiviCRM.                                    |
 |                                                                    |
 | CiviCRM is free software; you can copy, modify, and distribute it  |
 | under the terms of the GNU Affero General Public License           |
 | Version 3, 19 November 2007 and the CiviCRM Licensing Exception.   |
 |                                                                    |
 | CiviCRM is distributed in the hope that it will be useful, but     |
 | WITHOUT ANY WARRANTY; without even the implied warranty of         |
 | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.               |
 | See the GNU Affero General Public License for more details.        |
 |                                                                    |
 | You should have received a copy of the GNU Affero General Public   |
 | License and the CiviCRM Licensing Exception along                  |
 | with this program; if not, contact CiviCRM LLC                     |
 | at info[AT]civicrm[DOT]org. If you have questions about the        |
 | GNU Affero General Public License or the licensing of CiviCRM,     |
 | see the CiviCRM license FAQ at http://civicrm.org/licensing        |
 +--------------------------------------------------------------------+
 */
namespace Civi\FlexMailer;

use Civi\FlexMailer\Event\AlterBatchEvent;
use Civi\FlexMailer\Event\ComposeBatchEvent;
use Civi\FlexMailer\Event\RunEvent;
use Civi\FlexMailer\Event\SendBatchEvent;
use Civi\FlexMailer\Event\WalkBatchesEvent;
use Civi\Token\TokenProcessor;
use Civi\Token\TokenRow;

/**
 * Class DefaultEngine
 * @package Civi\FlexMailer
 *
 * The FlexMailer provides a more pluggable system for batch
 * mailings -- allowing third-parties to replace the batching, composition,
 * and delivery. The DefaultEngine provides the default handlers for every
 * part of the system. These are designed to be very similar to the
 * original MailingJob::deliver*() process.
 *
 * @see FlexMailer
 * @see \Civi\Core\Container::createEventDispatcher()
 */
class DefaultEngine {

  public function onRunInit(RunEvent $e) {
    // FIXME: This probably doesn't belong here...
    if (defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY) {
      \CRM_Core_Smarty::registerStringResource();
    }
  }

  /**
   * Abdicate; defer to the old system.
   *
   * The FlexMailer is in incubation -- it's a heavily reorganized version
   * of the old MailingJob::deliver*() functions. It hasn't been tested as
   * thoroughly and may not have perfect parity.
   *
   * During incubation, we want to mostly step-aside -- instead,
   * simply continue using the old system.
   *
   * @param \Civi\FlexMailer\Event\RunEvent $e
   */
  public function onRunAbdicate(RunEvent $e) {
    // Hidden setting: "experimentalFlexMailerEngine" (bool)
    // If TRUE, we will always use DefaultEngine.
    // Otherwise, we'll generally abdicate.
    if (\Civi::settings()->get('experimentalFlexMailerEngine')) {
      return; // OK, we'll continue running.
    }

    // Use FlexMailer for new-style email blasts (with `template_type`).
    $mailing = $e->getMailing();
    if ($mailing->template_type && !$mailing->sms_provider_id) {
      return; // OK, we'll continue running.
    }

    // Nope, we'll abdicate.
    $e->stopPropagation();
    $isDelivered = $e->getJob()->deliver(
      $e->context['deprecatedMessageMailer'],
      $e->context['deprecatedTestParams']
    );
    $e->setCompleted($isDelivered);
  }

  /**
   * Given a MailingJob (`$e->getJob()`), enumerate the recipients as
   * a batch of FlexMailerTasks and visit each (`$e->visit($tasks)`).
   *
   * @param \Civi\FlexMailer\Event\WalkBatchesEvent $e
   */
  public function onWalkBatches(WalkBatchesEvent $e) {
    $e->stopPropagation();

    $job = $e->getJob();

    // CRM-12376
    // This handles the edge case scenario where all the mails
    // have been delivered in prior jobs.
    $isDelivered = TRUE;

    // make sure that there's no more than $mailerBatchLimit mails processed in a run
    $mailerBatchLimit = \Civi::settings()->get('mailerBatchLimit');

    $eq = \CRM_Mailing_BAO_MailingJob::findPendingTasks($job->id, 'email');
    $tasks = array();
    while ($eq->fetch()) {
      if ($mailerBatchLimit > 0 && \CRM_Mailing_BAO_MailingJob::$mailsProcessed >= $mailerBatchLimit) {
        if (!empty($tasks)) {
          $e->visit($tasks);
        }
        $eq->free();
        $e->setCompleted(FALSE);
        return;
      }
      \CRM_Mailing_BAO_MailingJob::$mailsProcessed++;

      // FIXME: To support SMS, the address should be $eq->phone instead of $eq->email
      $tasks[] = new FlexMailerTask($eq->id, $eq->contact_id, $eq->hash,
        $eq->email);
      if (count($tasks) == \CRM_Mailing_BAO_MailingJob::MAX_CONTACTS_TO_PROCESS) {
        $isDelivered = $e->visit($tasks);
        if (!$isDelivered) {
          $eq->free();
          $e->setCompleted($isDelivered);
          return;
        }
        $tasks = array();
      }
    }

    $eq->free();

    if (!empty($tasks)) {
      $isDelivered = $e->visit($tasks);
    }
    $e->setCompleted($isDelivered);
  }

  /**
   * Given a mailing and a batch of recipients, prepare
   * the individual messages (headers and body) for each.
   *
   * @param \Civi\FlexMailer\Event\ComposeBatchEvent $e
   */
  public function onComposeBatch(ComposeBatchEvent $e) {
    $e->stopPropagation();

    $mailing = $e->getMailing();

    if (property_exists($mailing, 'language') && $mailing->language && $mailing->language != 'en_US') {
      $swapLang = \CRM_Utils_AutoClean::swap('global://dbLocale?getter', 'call://i18n/setLocale', $mailing->language);
    }

    // $message = $e->getMailing()->compose($e->getJob()->id, $task->getEventQueueId(), $task->getHash(), $task->getContactId(), $task->getAddress(), 'IGNORE', FALSE, 'IGNORE', $e->getAttachments(), FALSE, NULL, 'replyTo@email');

    $tp = $this->createTokenProcessor($e)->evaluate();
    foreach ($tp->getRows() as $row) {
      /** @var TokenRow $row */
      /** @var FlexMailerTask $task */
      $task = $row->context['flexMailerTask'];

      // Ugh, getVerpAndUrlsAndHeaders() is immensely silly.
      list($verp) = $mailing->getVerpAndUrlsAndHeaders(
        $e->getJob()->id, $task->getEventQueueId(), $task->getHash(), $task->getAddress());

      $mailParams = array();

      // Email headers
      $mailParams['Return-Path'] = $verp['bounce'];
      $mailParams['From'] = "\"{$mailing->from_name}\" <{$mailing->from_email}>";
      $mailParams['List-Unsubscribe'] = "<mailto:{$verp['unsubscribe']}>";
      \CRM_Mailing_BAO_Mailing::addMessageIdHeader($mailParams, 'm', $e->getJob()->id, $task->getEventQueueId(), $task->getHash());
      $mailParams['Subject'] = $row->render('subject');
      //if ($isForward) {$mailParams['Subject'] = "[Fwd:{$this->subject}]";}
      $mailParams['Precedence'] = 'bulk';
      $mailParams['X-CiviMail-Bounce'] = $verp['bounce'];
      $mailParams['Reply-To'] = $verp['reply'];
      if ($mailing->replyto_email && ($mailParams['From'] != $mailing->replyto_email)) {
        $mailParams['Reply-To'] = $mailing->replyto_email;
      }

      // Oddballs
      $mailParams['text'] = $row->render('body_text');
      $mailParams['html'] = $row->render('body_html');
      $mailParams['attachments'] = $e->getAttachments();
      $mailParams['toName'] = $row->render('toName');
      $mailParams['toEmail'] = $task->getAddress();
      $mailParams['job_id'] = $e->getJob()->id;

      $task->setMailParams($mailParams);
    }
  }

  public function onAlterBatch(AlterBatchEvent $e) {
    foreach ($e->getTasks() as $task) {
      /** @var FlexMailerTask $task */
      $mailParams = $task->getMailParams();
      if ($mailParams) {
        \CRM_Utils_Hook::alterMailParams($mailParams, 'flexmailer');
        $task->setMailParams($mailParams);
      }
    }
  }

  public function onSendBatch(SendBatchEvent $e) {
    static $smtpConnectionErrors = 0;

    $e->stopPropagation();

    $job = $e->getJob();
    $mailing = $e->getMailing();
    $job_date = \CRM_Utils_Date::isoToMysql($job->scheduled_date);
    $mailer = \Civi::service('pear_mail');

    $targetParams = $deliveredParams = array();
    $count = 0;

    foreach ($e->getTasks() as $key => $task) {
      /** @var FlexMailerTask $task */
      /** @var \Mail_mime $message */
      $message = $this->convertMailParamsToMime($task->getMailParams());

      if (empty($message)) {
        // lets keep the message in the queue
        // most likely a permissions related issue with smarty templates
        // or a bad contact id? CRM-9833
        continue;
      }

      // disable error reporting on real mailings (but leave error reporting for tests), CRM-5744
      if ($job_date) {
        $errorScope = \CRM_Core_TemporaryErrorScope::ignoreException();
      }

      $headers = $message->headers();
      $result = $mailer->send($headers['To'], $message->headers(), $message->get());

      if ($job_date) {
        unset($errorScope);
      }

      if (is_a($result, 'PEAR_Error')) {
        /** @var \PEAR_Error $result */
        // CRM-9191
        $message = $result->getMessage();
        if (
          strpos($message, 'Failed to write to socket') !== FALSE ||
          strpos($message, 'Failed to set sender') !== FALSE
        ) {
          // lets log this message and code
          $code = $result->getCode();
          \CRM_Core_Error::debug_log_message("SMTP Socket Error or failed to set sender error. Message: $message, Code: $code");

          // these are socket write errors which most likely means smtp connection errors
          // lets skip them
          $smtpConnectionErrors++;
          if ($smtpConnectionErrors <= 5) {
            continue;
          }

          // seems like we have too many of them in a row, we should
          // write stuff to disk and abort the cron job
          $job->writeToDB($deliveredParams, $targetParams, $mailing, $job_date);

          \CRM_Core_Error::debug_log_message("Too many SMTP Socket Errors. Exiting");
          \CRM_Utils_System::civiExit();
        }

        // Register the bounce event.

        $params = array(
          'event_queue_id' => $task->getEventQueueId(),
          'job_id' => $job->id,
          'hash' => $task->getHash(),
        );
        $params = array_merge($params,
          \CRM_Mailing_BAO_BouncePattern::match($result->getMessage())
        );
        \CRM_Mailing_Event_BAO_Bounce::create($params);
      }
      else {
        // Register the delivery event.
        $deliveredParams[] = $task->getEventQueueId();
        $targetParams[] = $task->getContactId();

        $count++;
        if ($count % \CRM_Mailing_Config::BULK_MAIL_INSERT_COUNT == 0) {
          $job->writeToDB($deliveredParams, $targetParams, $mailing, $job_date);
          $count = 0;

          // hack to stop mailing job at run time, CRM-4246.
          // to avoid making too many DB calls for this rare case
          // lets do it when we snapshot
          $status = \CRM_Core_DAO::getFieldValue(
            'CRM_Mailing_DAO_MailingJob',
            $job->id,
            'status',
            'id',
            TRUE
          );

          if ($status != 'Running') {
            $e->setCompleted(FALSE);
            return;
          }
        }
      }

      unset($result);

      // seems like a successful delivery or bounce, lets decrement error count
      // only if we have smtp connection errors
      if ($smtpConnectionErrors > 0) {
        $smtpConnectionErrors--;
      }

      // If we have enabled the Throttle option, this is the time to enforce it.
      $mailThrottleTime = \Civi::settings()->get('mailThrottleTime');
      if (!empty($mailThrottleTime)) {
        usleep((int ) $mailThrottleTime);
      }
    }

    $e->setCompleted($job->writeToDB(
      $deliveredParams,
      $targetParams,
      $mailing,
      $job_date
    ));
  }

  /**
   * Convert from "mail params" to PEAR's Mail_mime.
   *
   * The data-structure which represents a message for purposes of
   * hook_civicrm_alterMailParams does not match the data structure for
   * Mail_mime.
   *
   * @param array $mailParams
   * @return \Mail_mime
   * @see \CRM_Utils_Hook::alterMailParams
   */
  private function convertMailParamsToMime($mailParams) {
    // The general assumption is that key-value pairs in $mailParams should
    // pass through as email headers, but there are several special-cases
    // (e.g. 'toName', 'toEmail', 'text', 'html', 'attachments', 'headers').

    $message = new \Mail_mime("\n");

    // 1. Consolidate: 'toName' and 'toEmail' should be 'To'.
    $toName = trim($mailParams['toName']);
    $toEmail = trim($mailParams['toEmail']);
    if ($toName == $toEmail || strpos($toName, '@') !== FALSE) {
      $toName = NULL;
    }
    else {
      $toName = \CRM_Utils_Mail::formatRFC2822Name($toName);
    }
    unset($mailParams['toName']);
    unset($mailParams['toEmail']);
    $mailParams['To'] = "$toName <$toEmail>";

    // 2. Apply the other fields.
    foreach ($mailParams as $key => $value) {
      if (empty($value)) {
        continue;
      }

      switch ($key) {
        case 'text':
          $message->setTxtBody($mailParams['text']);
          break;

        case 'html':
          $message->setHTMLBody($mailParams['html']);
          break;

        case 'attachments':
          foreach ($mailParams['attachments'] as $fileID => $attach) {
            $message->addAttachment($attach['fullPath'],
              $attach['mime_type'],
              $attach['cleanName']
            );
          }
          break;

        case 'headers':
          $message->headers($value);
          break;

        case 'job_id':
          // Hrm, don't think this is supposed to be a header.
          break;

        default:
          $message->headers(array($key => $value), TRUE);
      }
    }

    \CRM_Utils_Mail::setMimeParams($message);

    return $message;
  }

  /**
   * Instantiate a TokenProcessor, filling in the appropriate templates
   * ("subject", "body_text", "body_html") as well the recipient metadata
   * ("contactId", "mailingJobId", etc).
   *
   * @param \Civi\FlexMailer\Event\ComposeBatchEvent $e
   * @return TokenProcessor
   */
  protected function createTokenProcessor(ComposeBatchEvent $e) {
    $templates = $e->getMailing()->getTemplates();

    // FIXME: This needs a better home...
    if (!empty($templates['html']) && $e->getMailing()->open_tracking) {
      $templates['html'] .= "\n{action.trackOpenHtml}";
    }

    $tp = new TokenProcessor(\Civi::service('dispatcher'), array(
      'controller' => '\Civi\FlexMailer\DefaultEngine',
      // FIXME: Use template_type, template_options
      'smarty' => defined('CIVICRM_MAIL_SMARTY') && CIVICRM_MAIL_SMARTY ? TRUE : FALSE,
      'mailingId' => $e->getMailing()->id,
    ));
    $tp->addMessage('toName', '{contact.display_name}', 'text/plain');
    $tp->addMessage('subject', $templates['subject'], 'text/plain');
    $tp->addMessage('body_text', isset($templates['text']) ? $templates['text'] : '', 'text/plain');
    $tp->addMessage('body_html', isset($templates['html']) ? $templates['html'] : '', 'text/html');

    foreach ($e->getTasks() as $key => $task) {
      /** @var FlexMailerTask $task */

      $tp->addRow()->context(array(
        'contactId' => $task->getContactId(),
        'mailingJobId' => $e->getJob()->id,
        'mailingActionTarget' => array(
          'id' => $task->getEventQueueId(),
          'hash' => $task->getHash(),
          'email' => $task->getAddress(),
        ),
        'flexMailerTask' => $task,
      ));
    }
    return $tp;
  }

}

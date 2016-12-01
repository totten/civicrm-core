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
use \Symfony\Component\EventDispatcher\EventDispatcherInterface;

class FlexMailer {

  const EVENT_RUN = 'civi.flexmailer.run';
  const EVENT_WALK = 'civi.flexmailer.walk';
  const EVENT_COMPOSE = 'civi.flexmailer.compose';
  const EVENT_ALTER = 'civi.flexmailer.alter';
  const EVENT_SEND = 'civi.flexmailer.send';

  /**
   * @var array
   *   An array which must define options:
   *     - mailing: \CRM_Mailing_BAO_Mailing
   *     - job: \CRM_Mailing_BAO_MailingJob
   *     - attachments: array
   *
   * Additional options may be passed. To avoid naming conflicts, use prefixing.
   */
  public $context;

  /**
   * @var EventDispatcherInterface
   */
  private $dispatcher;

  /**
   * FlexMailer constructor.
   * @param array $context
   *   An array which must define options:
   *     - mailing: \CRM_Mailing_BAO_Mailing
   *     - job: \CRM_Mailing_BAO_MailingJob
   *     - attachments: array
   * @param EventDispatcherInterface $dispatcher
   */
  public function __construct($context = array(), EventDispatcherInterface $dispatcher = NULL) {
    $this->context = $context;
    $this->dispatcher = $dispatcher ? $dispatcher : \Civi::service('dispatcher');
  }

  /**
   * @return bool
   *   TRUE if delivery completed.
   * @throws \CRM_Core_Exception
   */
  public function run() {
    $flexMailer = $this; // PHP 5.3

    if (count($this->validate()) > 0) {
      throw new \CRM_Core_Exception("FlexMailer cannot execute: invalid context");
    }

    $run = $this->fireRun();
    if ($run->isPropagationStopped()) {
      return $run->getCompleted();
    }

    $walkBatches = $this->fireWalkBatches(function ($tasks) use ($flexMailer) {
      $flexMailer->fireComposeBatch($tasks);
      $flexMailer->fireAlterBatch($tasks);
      $sendBatch = $flexMailer->fireSendBatch($tasks);
      return $sendBatch->getCompleted();
    });

    return $walkBatches->getCompleted();
  }

  /**
   * @return array
   *   List of error messages
   */
  public function validate() {
    $errors = array();
    if (empty($this->context['mailing'])) {
      $errors['mailing'] = 'Missing \"mailing\"';
    }
    if (empty($this->context['job'])) {
      $errors['job'] = 'Missing \"job\"';
    }
    return $errors;
  }

  /**
   * @return RunEvent
   */
  public function fireRun() {
    $event = new RunEvent($this->context);
    $this->dispatcher->dispatch(self::EVENT_RUN, $event);
    return $event;
  }

  /**
   * @param callable $onVisitBatch
   * @return WalkBatchesEvent
   */
  public function fireWalkBatches($onVisitBatch) {
    $event = new WalkBatchesEvent($this->context, $onVisitBatch);
    $this->dispatcher->dispatch(self::EVENT_WALK, $event);
    return $event;
  }

  /**
   * @param array<FlexMailerTask> $tasks
   * @return ComposeBatchEvent
   */
  public function fireComposeBatch($tasks) {
    $event = new ComposeBatchEvent($this->context, $tasks);
    $this->dispatcher->dispatch(self::EVENT_COMPOSE, $event);
    return $event;
  }

  /**
   * @param array<FlexMailerTask> $tasks
   * @return AlterBatchEvent
   */
  public function fireAlterBatch($tasks) {
    $event = new AlterBatchEvent($this->context, $tasks);
    $this->dispatcher->dispatch(self::EVENT_ALTER, $event);
    return $event;
  }

  /**
   * @param array<FlexMailerTask> $tasks
   * @return SendBatchEvent
   */
  public function fireSendBatch($tasks) {
    $event = new SendBatchEvent($this->context, $tasks);
    $this->dispatcher->dispatch(self::EVENT_SEND, $event);
    return $event;
  }

}

<?php

use Civi\Api4\Email;

/**
 *  Include dataProvider for tests
 * @group headless
 */
class CRM_Pledge_Form_PledgeTest extends CiviUnitTestCase {

  use \Civi\Test\ErrorTestTrait;

  /**
   * Test the post process function.
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function testPostProcess(): void {
    $mut = new CiviMailUtils($this);
    $loggedInUser = $this->createLoggedInUser();
    $this->swapMessageTemplateForInput('pledge_acknowledge', '{domain.name} {contact.first_name}');

    $this->submitExamplePledge($loggedInUser);
    $mut->checkAllMailLog(['Default Domain Name Anthony']);
    $mut->clearMessages();
    $this->revertTemplateToReservedTemplate('pledge_acknowledge');
  }

  /**
   * Test the post process function with old/invalid data.
   *
   * TODO: Remove this test after a few cycles. Say, circa 5.48+?
   *
   * @throws \API_Exception
   * @throws \CRM_Core_Exception
   */
  public function testDeprecated(): void {
    $mut = new CiviMailUtils($this);
    $loggedInUser = $this->createLoggedInUser();
    $this->swapMessageTemplateForInput('pledge_acknowledge', '({domain.name}) ({$contact.first_name})');

    [$log] = $this->captureErrors(E_USER_DEPRECATED, function() use ($loggedInUser) {
      $this->submitExamplePledge($loggedInUser);
    });
    $this->assertNotEmpty(preg_grep('/\$contact\.first_name.*is no longer supported/', $log));
    $mut->checkAllMailLog(['(Default Domain Name) ()']);
    $mut->clearMessages();
    $this->revertTemplateToReservedTemplate('pledge_acknowledge');
  }

  /**
   * @param int $loggedInUser
   * @throws \API_Exception
   * @throws \Civi\API\Exception\UnauthorizedException
   */
  protected function submitExamplePledge(int $loggedInUser): void {
    $form = $this->getFormObject('CRM_Pledge_Form_Pledge', [
      'amount' => 10,
      'installments' => 1,
      'contact_id' => $this->individualCreate(),
      'is_acknowledge' => 1,
      'start_date' => '2021-01-04',
      'create_date' => '2021-01-04',
      'from_email_address' => Email::get()
        ->addWhere('contact_id', '=', $loggedInUser)
        ->addSelect('id')->execute()->first()['id'],
    ]);
    $form->buildForm();
    $form->postProcess();
  }

}

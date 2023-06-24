<?php
namespace Civi\Api4;

use CRM_Standaloneusers_ExtensionUtil as E;
use Civi\Test\CiviEnvBuilder;
use Civi\Test\HeadlessInterface;
use Civi\Core\HookInterface;
use Civi\Test\TransactionalInterface;

/**
 * @group headless
 */
class UserTest extends \PHPUnit\Framework\TestCase implements HeadlessInterface, HookInterface, TransactionalInterface {

  public function setUpHeadless(): CiviEnvBuilder {
    return \Civi\Test::headless()
      ->install(['authx', 'org.civicrm.search_kit', 'org.civicrm.afform', 'standaloneusers'])
      // ->installMe(__DIR__) This causes failure, so we do                 ↑
      ->apply(FALSE);
  }

  public function testCreateUserWithAutoHashedPassword() {
    $name = 'user' . \CRM_Utils_String::createRandom(8, \CRM_Utils_String::ALPHANUMERIC);
    $pass = 'pass' . \CRM_Utils_String::createRandom(8, \CRM_Utils_String::ALPHANUMERIC);

    $user = \Civi\Api4\User::create()
      ->setValues([
        'username' => $name,
        'email' => $name . '@example.org',
        'password' => '@HASH:' . $pass,
      ])
      ->execute()
      ->first();

    $this->assertEquals($name, $user['username']);
    $this->assertNotEquals($pass, $user['password']);
    $this->assertRegExp(';^\$S\$;', $user['password']);
  }

}

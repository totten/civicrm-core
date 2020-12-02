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

namespace Civi\Angular;

/**
 * Test major use-cases of the 'crypto' service.
 */
class CryptoServiceTest extends \CiviUnitTestCase {

  const KEY_0 = ':b64:cGxlYXNlIHVzZSAzMiBieXRlcyBmb3IgYWVzLTI1NiE';
  const KEY_1 = 'aes-cbc:hkdf-sha256:abcd1234abcd1234';
  const KEY_2 = 'aes-ctr::abcd1234abcd1234';
  const KEY_3 = 'aes-cbc-hs::abcd1234abcd1234';

  protected $keys = [];

  /**
   * @var \Civi\Crypto\CryptoService
   */
  protected $crypto;

  protected function setUp() {
    parent::setUp();
    $this->crypto = clone \Civi::service('crypto');
    $this->keys[0] = $this->crypto->addSymmetricKey($this->crypto->parseKey(self::KEY_0) + [
      'tags' => ['UNIT-TEST'],
      'weight' => 10,
      'id' => 'asdf-key-0',
    ]);
    $this->keys[1] = $this->crypto->addSymmetricKey($this->crypto->parseKey(self::KEY_1) + [
      'tags' => ['UNIT-TEST'],
      'weight' => -10,
      'id' => 'asdf-key-1',
    ]);
    $this->keys[2] = $this->crypto->addSymmetricKey($this->crypto->parseKey(self::KEY_2) + [
      'tags' => ['UNIT-TEST'],
      'id' => 'asdf-key-2',
    ]);
    $this->keys[3] = $this->crypto->addSymmetricKey($this->crypto->parseKey(self::KEY_3) + [
      'tags' => ['UNIT-TEST'],
      'id' => 'asdf-key-3',
    ]);
  }

  public function testParseKey() {
    $key0 = $this->crypto->parseKey(self::KEY_0);
    $this->assertEquals("please use 32 bytes for aes-256!", $key0['key']);
    $this->assertEquals('aes-cbc', $key0['suite']);

    $key1 = $this->crypto->parseKey(self::KEY_1);
    $this->assertEquals(32, strlen($key1['key']));
    $this->assertEquals('aes-cbc', $key1['suite']);
    $this->assertEquals('0ao5eC7C/rwwk2qii4oLd6eG3KJq8ZDX2K9zWbvaLdo=', base64_encode($key1['key']));

    $key2 = $this->crypto->parseKey(self::KEY_2);
    $this->assertEquals(32, strlen($key2['key']));
    $this->assertEquals('aes-ctr', $key2['suite']);
    $this->assertEquals('0ao5eC7C/rwwk2qii4oLd6eG3KJq8ZDX2K9zWbvaLdo=', base64_encode($key2['key']));

    $key3 = $this->crypto->parseKey(self::KEY_3);
    $this->assertEquals(32, strlen($key3['key']));
    $this->assertEquals('aes-cbc-hs', $key3['suite']);
    $this->assertEquals('0ao5eC7C/rwwk2qii4oLd6eG3KJq8ZDX2K9zWbvaLdo=', base64_encode($key3['key']));
  }

  public function getExampleKeyIds() {
    return [
      ['hello world. can you see me', 'plain', '/^hello world. can you see me/', 27],
      ['hello world. i am secret.', 'UNIT-TEST', '/^.asdf-key-1./', 77],
      ['hello world. we b secret.', 'asdf-key-0', '/^.asdf-key-0./', 77],
      ['hello world. u ur secret.', 'asdf-key-1', '/^.asdf-key-1./', 77],
      ['hello world. he z secret.', 'asdf-key-2', '/^.asdf-key-2./', 69],
      ['hello world. whos secret.', 'asdf-key-3', '/^.asdf-key-3./', 121],
    ];
  }

  /**
   * @param string $inputText
   * @param string $inputKeyIdOrTag
   * @param string $expectTokenRegex
   * @param int $expectTokenLen
   *
   * @dataProvider getExampleKeyIds
   */
  public function testRoundtrip($inputText, $inputKeyIdOrTag, $expectTokenRegex, $expectTokenLen) {
    $token = $this->crypto->encrypt($inputText, $inputKeyIdOrTag);
    $this->assertRegExp($expectTokenRegex, $token);
    $this->assertEquals($expectTokenLen, strlen($token));
    $actualText = $this->crypto->decrypt($token);
    $this->assertEquals($inputText, $actualText);
  }

}

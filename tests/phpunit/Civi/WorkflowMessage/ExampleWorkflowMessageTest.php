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

namespace Civi\WorkflowMessage;

use Civi\Test\Invasive;

/**
 * Test the WorkflowMessage class
 *
 * @group headless
 */
class ExampleWorkflowMessageTest extends \CiviUnitTestCase {

  protected function setUp(): void {
    $this->useTransaction();
    parent::setUp();
  }

  /**
   * @return \Civi\WorkflowMessage\WorkflowMessageInterface
   */
  protected static function createExample() {
    return new class() extends WorkflowMessage {

      const WORKFLOW = 'my_example_wf';

      const GROUP = 'my_example_grp';

      /**
       * @var string
       * @mapping tplParams.my_public_string
       */
      public $myPublicString;

      /**
       * @var int
       * @mapping tplParams.my_int
       */
      protected $myProtectedInt;

      /**
       * @var string[]
       */
      protected $implicitStringArray;

      /**
       * @var string[]
       * @dataType Text
       * @serialize COMMA
       */
      protected $explicitStringArray;

      /**
       * @var int
       * @mapping tplParams.some.deep.thing
       */
      protected $deepValue;

      protected function exportArrayExtraTplParams(array &$export): void {
        $export['tplParams']['some_extra_tpl_stuff'] = 100;
      }

    };
  }

  /**
   * Assert that "getFields()" provides metadata from properties/docblocks.
   */
  public function testGetFields() {
    /** @var \Civi\WorkflowMessage\WorkflowMessageInterface $ex */
    $ex = static::createExample();
    $fields = $ex->getFields();
    /** @var \Civi\Schema\MappedFieldSpec[] $field */

    $field = $fields['myPublicString'];
    $this->assertEquals(['string'], $field->getType());
    $this->assertEquals('String', $field->getDataType());
    $this->assertEquals(NULL, $field->getSerialize());

    $field = $fields['implicitStringArray'];
    $this->assertEquals(['string[]'], $field->getType());
    $this->assertEquals('Blob', $field->getDataType());
    $this->assertEquals(\CRM_Core_DAO::SERIALIZE_JSON, $field->getSerialize());

    $field = $fields['explicitStringArray'];
    $this->assertEquals(['string[]'], $field->getType());
    $this->assertEquals('Text', $field->getDataType());
    $this->assertEquals(\CRM_Core_DAO::SERIALIZE_COMMA, $field->getSerialize());

    $field = $fields['myProtectedInt'];
    $this->assertEquals(['int'], $field->getType());
    $this->assertEquals('Integer', $field->getDataType());
    $this->assertEquals(NULL, $field->getSerialize());
  }

  /**
   * Assert that getters/setters work on class fields.
   */
  public function testGetSetClassFields() {
    /** @var \Civi\WorkflowMessage\WorkflowMessageInterface $ex */
    $ex = static::createExample();

    $ex->setMyProtectedInt(5);
    $this->assertEquals(5, $ex->getMyProtectedInt());
    $this->assertEquals(5, Invasive::get([$ex, 'myProtectedInt']));

    $ex->setMyPublicString('hello');
    $this->assertEquals('hello', $ex->getMyPublicString());
    $this->assertEquals('hello', $ex->myPublicString);
  }

  /**
   * Assert that import()/export() work on standard fields.
   */
  public function testImportExportStandardField() {
    /** @var \Civi\WorkflowMessage\WorkflowMessageInterface $ex */
    $ex = static::createExample();

    $ex->importArray([
      'tplParams' => [
        'my_public_string' => 'hello world',
        'my_int' => 10,
        'some' => ['deep' => ['thing' => 20]],
      ],
    ]);

    $this->assertEquals('hello world', $ex->getMyPublicString());
    $this->assertEquals(10, $ex->getMyProtectedInt());
    $this->assertEquals(20, $ex->getDeepValue());

    $ex->myPublicString .= ' and stuff';
    $ex->setDeepValue(22);

    $export = $ex->exportArray();
    $tpl = $export['tplParams'];

    $this->assertEquals('hello world and stuff', $tpl['my_public_string']);
    $this->assertEquals(10, $tpl['my_int']);
    $this->assertEquals(22, $tpl['some']['deep']['thing']);
    $this->assertEquals(100, $tpl['some_extra_tpl_stuff']);

    $this->assertEquals('my_example_wf', $export['valueName']);
    $this->assertEquals('my_example_grp', $export['groupName']);
  }

  /**
   * Assert that unrecognized fields are preserved in the round-trip from import=>export.
   */
  public function testImportExportExtraField() {
    /** @var \Civi\WorkflowMessage\WorkflowMessageInterface $ex */
    $ex = static::createExample();

    $ex->importArray([
      'tplParams' => [
        'my.st!er_y' => ['is not mentioned anywhere'],
      ],
    ]);

    $tpl = $ex->exportArray()['tplParams'];
    $this->assertEquals(['is not mentioned anywhere'], $tpl['my.st!er_y']);
  }

  /**
   * Assert that
   */
  public function testImportExportUnmappedField() {
    /** @var \Civi\WorkflowMessage\WorkflowMessageInterface $ex */
    $ex = static::createExample();

    $ex->importArray([
      'tplParams' => [
        'implicitStringArray' => ['is not mapped between class and tpl'],
      ],
    ]);

    $this->assertEquals(NULL, $ex->getImplicitStringArray());
    $ex->setImplicitStringArray(['this is the real class field']);

    $tpl = $ex->exportArray()['tplParams'];
    $this->assertEquals(['is not mapped between class and tpl'], $tpl['implicitStringArray']);

    $classData = $ex->exportArray()['modelProps'];
    $this->assertEquals(['this is the real class field'], $classData['implicitStringArray']);
  }

  /**
   * Create an impromptu instance of  `WorkflowMessage` for a new/unknown workflow.
   */
  public function testImpromptuImportExport() {
    /** @var \Civi\WorkflowMessage\WorkflowMessageInterface $ex */
    $ex = WorkflowMessage::create('some_impromptu_wf', [
      'from' => 'foo@example.com',
      'tokenContext' => ['contactId' => 123],
      'tplParams' => [
        'myImpromputInt' => 456,
        'impromptu_smarty_data' => ['is not mentioned anywhere'],
      ],
    ]);
    $this->assertTrue($ex instanceof GenericWorkflowMessage);

    $export = $ex->exportArray();

    $tpl = $export['tplParams'];
    $this->assertEquals(456, $tpl['myImpromputInt']);
    $this->assertEquals(['is not mentioned anywhere'], $tpl['impromptu_smarty_data']);
    $this->assertTrue(!isset($tpl['valueName']));

    $envelope = $export['envelope'];
    $this->assertEquals('some_impromptu_wf', $envelope['valueName']);
    $this->assertEquals('foo@example.com', $envelope['from']);
    $this->assertTrue(!isset($envelope['myProtectedInt']));

    $tokenCtx = $export['tokenContext'];
    $this->assertEquals(123, $tokenCtx['contactId']);
    $this->assertTrue(!isset($envelope['myProtectedInt']));
  }

  public function testImpromptuRender() {
    $rand = rand(0, 1000);
    $cid = $this->individualCreate(['first_name' => 'Foo', 'last_name' => 'Bar' . $rand, 'prefix_id' => NULL, 'suffix_id' => NULL]);
    /** @var \Civi\WorkflowMessage\GenericWorkflowMessage $ex */
    $ex = WorkflowMessage::create('some_impromptu_wf', [
      'tokenContext' => ['contactId' => $cid],
    ]);
    $rendered = $ex->renderTemplate([
      'messageTemplate' => [
        'msg_subject' => 'Hello {contact.display_name}',
      ],
    ]);
    $this->assertEquals('Hello Foo Bar' . $rand, $rendered['subject']);
  }

  public function testRenderStoredTemplate() {
    $rand = rand(0, 1000);
    $cid = $this->individualCreate(['first_name' => 'Foo', 'last_name' => 'Bar' . $rand, 'prefix_id' => NULL, 'suffix_id' => NULL]);
    /** @var \Civi\WorkflowMessage\GenericWorkflowMessage $ex */
    $ex = WorkflowMessage::create('petition_sign', [
      'tokenContext' => ['contactId' => $cid],
      'tplParams' => [
        'greeting' => 'Greetings yo',
        'petition' => ['title' => 'The Fake Petition'],
        'petitionTitle' => 'The Fake Petition',
        'survey_id' => NULL,
      ],
    ]);
    $rendered = $ex->renderTemplate();
    $this->assertStringContainsString('Foo Bar' . $rand, $rendered['subject']);
    $this->assertStringContainsString('Thank you for signing The Fake Petition', $rendered['html']);
    $this->assertStringContainsString('Thank you for signing The Fake Petition', $rendered['text']);
  }

  //public function testImpromptuTokens() {
  //  /** @var \Civi\WorkflowMessage\GenericWorkflowMessage $ex */
  //  $ex = WorkflowMessage::create('some_impromptu_wf', [
  //    'envelope' => [
  //      'contactId' => 123,
  //    ],
  //  ]);
  //  $tokens = $ex->getTokens();
  //  $this->assertEquals('First ZZName', $tokens['contact.first_name']['label']);
  //}

}

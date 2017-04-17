<?php

/**
 * Class CRM_Core_I18n_SchemaTest
 * @group headless
 */
class CRM_Core_I18n_SchemaTest extends CiviUnitTestCase {

  protected $originalLocale;

  /**
   * @inheritDoc
   */
  protected function setUp() {
    parent::setUp();
    global $dbLocale;
    $this->originalLocale = $dbLocale;
  }

  /**
   * @inheritDoc
   */
  protected function tearDown() {
    global $dbLocale;
    $dbLocale = $this->originalLocale;
    parent::tearDown();
  }

  public function getRewriteExamples() {
    $cases = array();

    //$cases[] = array(
    //  0 => 'activeLocale',
    //  1 => 'inputSql',
    //  2 => 'expectSql',
    //);

    $cases[] = array(
      // Table name at end.
      0 => 'fr_FR',
      1 => 'SELECT name FROM civicrm_option_value',
      2 => 'SELECT name FROM civicrm_option_valuefr_FR',
    );

    $cases[] = array(
      // Alternate verb, not SELECT.
      0 => 'fr_FR',
      1 => 'DELETE FROM civicrm_option_value',
      2 => 'DELETE FROM civicrm_option_valuefr_FR',
    );

    $cases[] = array(
      // Table name is part of a string
      0 => 'fr_FR',
      1 => 'SELECT "hello civicrm_option_value table"',
      2 => 'SELECT "hello civicrm_option_value table"',
    );

    $cases[] = array(
      // Table name is part of a string
      0 => 'fr_FR',
      1 => 'SELECT name FROM INFORMATION_SCHEMA.foobar WHERE fuz = "civicrm_option_value"',
      2 => 'SELECT name FROM INFORMATION_SCHEMA.foobar WHERE fuz = "civicrm_option_value"',
    );

    $cases[] = array(
      // Table name is part of a subquery (at end).
      0 => 'fr_FR',
      1 => 'SELECT * FROM civicrm_foo WHERE foo_id = (SELECT value FROM civicrm_option_value)',
      2 => 'SELECT * FROM civicrm_foo WHERE foo_id = (SELECT value FROM civicrm_option_valuefr_FR)',
    );

    $cases[] = array(
      // Table name is part of a subquery (in middle).
      0 => 'fr_FR',
      1 => 'SELECT * FROM civicrm_foo WHERE foo_id = (SELECT value FROM civicrm_option_value WHERE 0)',
      2 => 'SELECT * FROM civicrm_foo WHERE foo_id = (SELECT value FROM civicrm_option_valuefr_FR WHERE 0)',
    );

    $cases[] = array(
      // Table name is in the middle of a normal FROM clause.
      0 => 'fr_FR',
      1 => 'SELECT name FROM civicrm_option_value WHERE id = 123',
      2 => 'SELECT name FROM civicrm_option_valuefr_FR WHERE id = 123',
    );

    $cases[] = array(
      // Table name escaped. Also, Language is different.
      0 => 'de_DE',
      1 => 'SELECT name FROM `civicrm_option_value` WHERE id = 123',
      2 => 'SELECT name FROM `civicrm_option_valuede_DE` WHERE id = 123',
    );

    $cases[] = array(
      // Table name is part of a JOIN.
      0 => 'fr_FR',
      1 => 'SELECT civicrm_option_value.foo FROM civicrm_foozball INNER JOIN civicrm_option_value ON civicrm_foozball.id=civicrm_option_value.option_group_id',
      2 => 'SELECT civicrm_option_valuefr_FR.foo FROM civicrm_foozball INNER JOIN civicrm_option_valuefr_FR ON civicrm_foozball.id=civicrm_option_valuefr_FR.option_group_id',
    );

    $cases[] = array(
      // civicrm_event is translated, but civicrm_events_in_cart is not.
      0 => 'fr_FR',
      1 => 'SELECT name FROM civicrm_event WHERE id = 123',
      2 => 'SELECT name FROM civicrm_eventfr_FR WHERE id = 123',
    );

    $cases[] = array(
      // civicrm_event is translated, but civicrm_events_in_cart is not.
      0 => 'fr_FR',
      1 => 'SELECT name FROM civicrm_events_in_cart WHERE id = 123',
      2 => 'SELECT name FROM civicrm_events_in_cart WHERE id = 123',
    );

    return $cases;
  }

  /**
   * Check that a series of example SQL queries are correctly rewritten
   * in multi-lingual configuration.
   *
   * @param string $activeLocale
   *   Ex: 'fr_FR', 'en_US'.
   * @param string $inputSql
   *   The pristine, original SQL query.
   * @param string $expectSql
   *   The expected, rewritten SQL query.
   * @dataProvider getRewriteExamples
   */
  public function testRewrite($activeLocale, $inputSql, $expectSql) {
    global $dbLocale;
    $dbLocale = $activeLocale;
    $actualSql = CRM_Core_I18n_Schema::rewriteQuery($inputSql);
    $this->assertEquals($expectSql, $actualSql);
  }

}

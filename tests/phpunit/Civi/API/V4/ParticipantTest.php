<?php
namespace Civi\API\V4;

require_once 'CiviTest/CiviUnitTestCase.php';

/**
 */
class ParticipantTest extends \CiviUnitTestCase {

  public function testGet() {
    $params = array('limit' => 5);
    // Api4 calls returns an arrayObject
    // @see http://php.net/manual/en/class.arrayobject.php
    // You can 'foreach' it like a normal array and it also stores "extra" properties - perfect for api metadata
    // It looks like this:
    // $result = array(
    //   1 => array('id' => 123, 'event_id' => 12, 'contact_id' => 456... etc),
    //   2 => array('id' => ... etc),
    // )->version = 4
    //  ->entity = 'Participant'
    //  ->action = 'get'
    $result = \Civi::api4()->Participant('get', $params);

    // Check that the $result arrayObject knows what the inputs were
    $this->assertEquals('Participant', $result->entity);
    $this->assertEquals('get', $result->action);

    // TODO: The real api might merge in some defaults
    $this->assertEquals($params, $result->params);

    // Result object ought to know what version of the api we are using
    $this->assertEquals(4, $result->version);

    // Here's a convenient way to get the first result - maybe a replacement for getsingle
    // Rationale for ditching getsingle - it's an output format & not a real action
    // and output transformations would be better handled by the $result object.
    $firstResult = $result->first();
    $this->assertEquals(1, $firstResult['id']);

    // By default the $result arrayObject should be non-associative
    $this->assertEquals([0,1,2,3,4], array_keys((array) $result));

    // Let's re-index by id (in v3 "sequential => 0")
    // Ditching "sequential" keeps better separation between input params and output formats
    $result->indexBy('id');
    // Array should still contain 5 items after re-index
    $this->assertEquals(5, count($result));

    // All values should now be keyed by id
    // This demonstrates how the $results object can be treated like a normal array
    // Meta properties like entity and version will not be looped
    foreach ($result as $key => $values) {
      $this->assertEquals($values['id'], $key);
    }
  }

}

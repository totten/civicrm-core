<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2015                                |
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
namespace Civi\API\V4\Entity;
use Civi\API\V4\Entity;

/**
 * Participant entity.
 */
class Participant extends Entity {

  /**
   * This is not what a typical get function would look like, this is just to simulate the output
   *
   * @param \Civi\API\Result $result
   * @return \Civi\API\Result
   */
  public function get($result) {
    // Simulate the results we might get from this api call
    $result->exchangeArray([
      [
        "contact_id" => "173",
        "event_id" => "1",
        "status_id" => "1",
        "role_id" => "1",
        "register_date" => "2009-01-21 00:00:00",
        "source" => "Check",
        "fee_level" => "Single",
        "is_test" => "0",
        "is_pay_later" => "0",
        "fee_amount" => "50.00",
        "fee_currency" => "USD",
        "id" => "1",
      ],
      [
        "contact_id" => "58",
        "event_id" => "2",
        "status_id" => "2",
        "role_id" => "2",
        "register_date" => "2008-05-07 00:00:00",
        "source" => "Credit Card",
        "fee_level" => "Soprano",
        "is_test" => "0",
        "is_pay_later" => "0",
        "fee_amount" => "50.00",
        "fee_currency" => "USD",
        "id" => "2",
      ],
      [
        "contact_id" => "102",
        "event_id" => "3",
        "status_id" => "3",
        "role_id" => "3",
        "register_date" => "2008-05-05 00:00:00",
        "source" => "Credit Card",
        "fee_level" => "Tiny-tots (ages 5-8)",
        "is_test" => "0",
        "is_pay_later" => "0",
        "fee_amount" => "800.00",
        "fee_currency" => "USD",
        "id" => "3",
      ],
      [
        "contact_id" => "45",
        "event_id" => "1",
        "status_id" => "4",
        "role_id" => "4",
        "register_date" => "2008-10-21 00:00:00",
        "source" => "Direct Transfer",
        "fee_level" => "Single",
        "is_test" => "0",
        "is_pay_later" => "0",
        "fee_amount" => "50.00",
        "fee_currency" => "USD",
        "id" => "4",
      ],
      [
        "contact_id" => "26",
        "event_id" => "2",
        "status_id" => "1",
        "role_id" => "1",
        "register_date" => "2008-01-10 00:00:00",
        "source" => "Check",
        "fee_level" => "Soprano",
        "is_test" => "0",
        "is_pay_later" => "0",
        "fee_amount" => "50.00",
        "fee_currency" => "USD",
        "id" => "5",
      ],
    ]);
    return $result;
  }
}

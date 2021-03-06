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

namespace Civi\Authx;

/**
 * The "Authentication" class describes the main inputs and decisions
 * of the authentication process.
 *
 * @package Civi\Authx
 */
class Authentication {

  /**
   * The authentication-flow by which we received the credential.
   *
   * @var string
   *   Ex: 'param', 'header', 'xheader', 'auto'
   */
  public $flow;

  /**
   * @var bool
   */
  public $useSession;

  /**
   * The raw credential as submitted.
   *
   * @var string
   *   Ex: 'Basic AbCd123=' or 'Bearer xYz.321'
   */
  public $cred;

  /**
   * The raw site-key as submitted (if applicable).
   * @var string
   */
  public $siteKey;

  /**
   * (Authenticated) The type of credential.
   *
   * @var string
   *   Ex: 'pass', 'api_key', 'jwt'
   */
  public $credType;

  /**
   * (Authenticated) UF user ID
   *
   * @var int|string|null
   */
  public $userId;

  /**
   * (Authenticated) CiviCRM contact ID
   *
   * @var int|null
   */
  public $contactId;

  /**
   * (Authenticated) JWT claims (if applicable).
   *
   * @var array|null
   */
  public $jwt = NULL;

  /**
   * @param array $args
   * @return $this
   */
  public static function create($args = []) {
    return (new static())->set($args);
  }

  /**
   * @param array $args
   * @return $this
   */
  public function set($args) {
    foreach ($args as $k => $v) {
      $this->{$k} = $v;
    }
    return $this;
  }

  /**
   * Specify the authenticated principal for this request.
   *
   * @param array $args
   *   Mix of: 'userId', 'contactId', 'credType'
   *   It is valid to give 'userId' or 'contactId' - the missing one will be
   *   filled in via UFMatch (if available).
   * @return $this
   */
  public function setPrincipal($args) {
    if (empty($args['userId']) && empty($args['contactId'])) {
      throw new \InvalidArgumentException("Must specify principal by userId and/or contactId");
    }
    if (empty($args['credType'])) {
      throw new \InvalidArgumentException("Must specify the type of credential used to identify the principal");
    }
    if ($this->hasPrincipal()) {
      throw new \LogicException("Principal has already been specified");
    }

    if (empty($args['contactId']) && !empty($args['userId'])) {
      $args['contactId'] = \CRM_Core_BAO_UFMatch::getContactId($args['userId']);
    }
    if (empty($args['userId']) && !empty($args['contactId'])) {
      $args['userId'] = \CRM_Core_BAO_UFMatch::getUFId($args['contactId']);
    }

    return $this->set($args);
  }

  /**
   * @return bool
   */
  public function hasPrincipal(): bool {
    return !empty($this->userId) || !empty($this->contactId);
  }

}

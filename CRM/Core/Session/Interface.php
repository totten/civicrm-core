<?php

interface CRM_Core_Session_Interface {

  /**
   * Creates an array in the session.
   *
   * All variables now will be stored under this array.
   *
   * @param bool $isRead
   *   In "read" mode, any active sessions will be detected/used.
   *   However, if there is no active session, then reads will return NULL.
   *   A new session will only be initialized when there is a write.
   */
  public function initialize($isRead = FALSE);

  /**
   * Resets the session store.
   *
   * @param int $all
   *   - 1 (default): destroy all session data
   *   - 2: destroy CiviCRM-specific session data
   */
  public function reset($all = 1);

  /**
   * @return array|NULL
   */
  public function &getRef();
}

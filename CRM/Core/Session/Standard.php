<?php

class CRM_Core_Session_Standard implements CRM_Core_Session_Interface {

  /**
   * Key is used to allow the application to have multiple top
   * level scopes rather than a single scope. (avoids naming
   * conflicts). We also extend this idea further and have local
   * scopes within a global scope. Allows us to do cool things
   * like resetting a specific area of the session code while
   * keeping the rest intact
   *
   * @var string
   */
  protected $key = 'CiviCRM';

  /**
   * This is just a reference to the real session. Allows us to
   * debug this class a wee bit easier
   *
   * @var object
   */
  protected $session = NULL;

  /**
   * Current php Session ID : needed to detect if the session is changed
   *
   * @var string
   */
  protected $sessionID;

  public function initialize($isRead = FALSE) {
    // remove $_SESSION reference if session is changed
    if (($sid = session_id()) !== $this->sessionID) {
      $this->session = NULL;
      $this->sessionID = $sid;
    }

    // 0. if the session was previously init'd but changed, then reset
    // 1. if it's a "read" and the session already exists, then wire it up
    // 2. if it's a "read" and there is no session, leave it blank
    // 3. if it's a "write" and the session already exists, then wire it up
    // 4. if it's a "write" and there is no session, then start one

    // lets initialize the _session variable just before we need it
    // hopefully any bootstrapping code will actually load the session from the CMS
    if (!isset($this->session)) {
      // CRM-9483
      if (!isset($_SESSION) && PHP_SAPI !== 'cli') {
        if ($isRead) {
          return;
        }
        CRM_Core_Config::singleton()->userSystem->sessionStart();
      }
      $this->session =& $_SESSION;
    }

    if ($isRead) {
      return;
    }

    if (!isset($this->session[$this->key]) ||
      !is_array($this->session[$this->key])
    ) {
      $this->session[$this->key] = [];
    }
  }

  public function reset($all = 1) {
    if ($all != 1) {
      $this->initialize();

      // to make certain we clear it, first initialize it to empty
      $this->session[$this->key] = [];
      unset($this->session[$this->key]);
    }
    else {
      $this->session = [];
    }
  }

  public function &getRef() {
    return $this->session[$this->key] ?? NULL;
  }

}
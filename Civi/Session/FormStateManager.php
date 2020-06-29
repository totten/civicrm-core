<?php

namespace Civi\Session;

/**
 * For multi-screen, server-side applications (e.g. QuickForm), the Civi
 * Form State Manager coordinates loading/saving/destruction of short/mid-term data.
 *
 * == Background / Requirements ==
 *
 * Historically, this functionality has been tied into Civi's session-management.
 * There is some similarity and connection:
 *
 * - Data needs to be loaded at the start of the page-request -- and saved at the end
 *   of the page-request.
 * - In HTML_QuickForm_Controller, the default `container()` is stored in $_SESSION.
 * - Each session has a unique "privateKey" which is used for generating unique form-state names.
 *
 * However, sessions and form-states have distinct requirements:
 *
 * - A session lasts for the entire time that a user is logged-in. Depending
 *   on system policy and user behavior, that could be days or weeks.
 * - Form state can become quite large - e.g. if you're performing a bulk action
 *   that touches many contacts, the action may have bits of state for each contact.
 * - Many forms may be opened, used, and forgotten during the course of a single
 *   session.
 * - Within the backend UI, every page-request will require loading the session-data.
 *   However, most form-states are irrelevant to most page-requests.
 *
 * It may help to consider some examples:
 *
 * - When a user logs in, the *session* should have a small piece of data indicating
 *   the user's identity (contact ID/user ID). This identity is used for the rest
 *   of the session and shapes the behavior of every page-request.
 * - When a user visits the "Advanced Search", they enter a large number of search
 *   options. We must retain these search options as they proceed to view results,
 *   refine the search, run tasks, etc. However, if they open a new "Advanced Search"
 *   (e.g. in another tab), then that they need a new form with a clean slate.
 *
 * == Functionality ==
 *
 * When a form is opened, it may ask the FormStateManager (FSM) for a place
 * to store data.
 *
 *   $state = &Civi::service('form_state')->load($name);
 *   $state['foo'] = 'bar';
 *
 * During the course of the page-request, the FormStateManager will retain a
 * reference to any active `$state`s. At the end of the page-request, any/all
 * states will be saved via:
 *
 *   Civi::service('form_state')->onShutdown();
 */
class FormStateManager {

  /**
   * When store session/form state, how long should the data be retained?
   *
   * Default is Two days: 2*24*60*60
   *
   * @var int, number of second
   */
  const DEFAULT_TTL = 172800;

  /**
   * The "activeStates" are modifiable records that can be used by the form layer.
   *
   * @var array
   *
   * Ex: $activeStates['CRM_Foo_BarController_12345']['selected_fruit'] = 'apple';
   */
  protected $activeStates = [];

  /**
   * The "persistentStates" are snapshots/mirrors of the state that's in storage.
   *
   * @var array
   *
   * Ex: $originalStates['CRM_Foo_BarController_12345']['selected_fruit'] = 'apple';
   *
   * If there was no original state, then the value is NULL.
   */
  protected $persistentStates = [];

  /**
   * @var \Psr\SimpleCache\CacheInterface
   */
  protected $cache;

  /**
   * FormStateManager constructor.
   * @param \Psr\SimpleCache\CacheInterface $cache
   */
  public function __construct(\Psr\SimpleCache\CacheInterface $cache = NULL) {
    $this->cache = $cache;
  }

  /**
   * Get a reference to the form-state.
   *
   * This is like get(), but it will initialize a new/non-existent form-state.
   *
   * @param string $name
   *   Ex: 'CRM_Foo_BarController_12345'
   * @param array $default
   *   If a new form-state must be created, fill it with $default values.
   * @return array|\ArrayAccess
   *   This is a modifiable reference to the form-state. Any changes to this
   *   will be persisted at the end of the page-request.
   */
  public function &load($name, $default = []) {
    if (!isset($this->activeStates[$name])) {
      // Some of these are little verbose to emphasize correct NULL-ness handling.
      $cacheValue = $this->cache->get($name, NULL);
      $this->persistentStates[$name] = $cacheValue ?: NULL;
      $this->activeStates[$name] = $cacheValue ?: $default;
    }

    $this->assertNonSession($name);
    return $this->activeStates[$name];
  }

  /**
   * Get a reference to the form-state.
   *
   * @param string $name
   *   Ex: 'CRM_Foo_BarController_12345'
   * @return array|NULL
   *   This is a modifiable reference to the form-state. Any changes to this
   *   will be persisted at the end of the page-request.
   */
  public function &get($name) {
    $this->assertNonSession($name);
    return $this->activeStates[$name] ?? NULL;
  }

  /**
   * Destroy all data about a specific form-state.
   *
   * @param string $name
   *   Ex: 'CRM_Foo_BarController_12345'
   * @return static
   */
  public function delete($name) {
    $this->assertNonSession($name);
    unset($this->persistentStates[$name]);
    unset($this->activeStates[$name]);
    $this->cache->delete($name);
    return $this;
  }

  /**
   * Save a specific form.
   *
   * @param string $name
   *   Ex: 'CRM_Foo_BarController_12345'
   * @return static
   */
  public function save($name) {
    if (!isset($this->activeStates[$name])) {
      \Civi::log()->warning('Cannot save form-state. It was never initialized.', ['formName' => $name]);
      return $this;
    }

    $this->assertNonSession($name);
    $this->cache->set($name, $this->activeStates[$name], self::pickTtl($name));
    $this->persistentStates[$name] = $this->activeStates[$name];

    return $this;
  }

  /**
   * Save any modified form states.
   *
   * @return static
   */
  public function onShutdown() {
    foreach ($this->activeStates as $name => $state) {
      if ($this->activeStates[$name] != $this->persistentStates[$name]) {
        $this->save($name);
      }
    }
    $this->activeStates = [];
    $this->persistentStates = [];
    return $this;
  }

  /**
   * Determine how long form-state should be retained.
   *
   * @param string $name
   *   Ex: '_CRM_Admin_Form_Preferences_Display_f1a5f232e3d850a29a7a4d4079d7c37b_4654_container'
   *   Ex: 'CiviCRM_CRM_Admin_Form_Preferences_Display_f1a5f232e3d850a29a7a4d4079d7c37b_4654'
   * @return int
   *   Number of seconds.
   */
  protected static function pickTtl($name) {
    $secureSessionTimeoutMinutes = (int) \Civi::settings()->get('secure_cache_timeout_minutes');
    if ($secureSessionTimeoutMinutes) {
      $transactionPages = [
        'CRM_Contribute_Controller_Contribution',
        'CRM_Event_Controller_Registration',
      ];
      foreach ($transactionPages as $transactionPage) {
        if (strpos($name, $transactionPage) !== FALSE) {
          return $secureSessionTimeoutMinutes * 60;
        }
      }
    }

    return self::DEFAULT_TTL;
  }

  /**
   * Historically, states were passed into $_SESSION, then detected+saved
   *
   * @param $name
   */
  protected function assertNonSession($name) {
    if (isset($_SESSION[$name])) {
      \Civi::log()->warning(
        "There appears to be duplicate form-state in the session scope.",
        ['civi.tag' => 'deprecated', 'formName' => $name]
      );

    }
  }

}

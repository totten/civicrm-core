<?php

namespace Civi\OAuthServer\Page;

use CRM_OAuthServer_ExtensionUtil as E;

class Authorize extends \CRM_Core_Page {

  public function run() {
    // Example: Set the page-title dynamically; alternatively, declare a static title in xml/Menu/*.xml
    \CRM_Utils_System::setTitle(E::ts('Authorize'));

    // Example: Assign a variable for use in a template
    $this->assign('currentTime', date('Y-m-d H:i:s'));

    parent::run();
  }

}

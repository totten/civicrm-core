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

/**
 * @package CRM
 * @copyright CiviCRM LLC https://civicrm.org/licensing
 */
class CRM_Extension_Manager_Component extends CRM_Extension_Manager_Base {

  public function __construct() {
    parent::__construct(FALSE);
  }

  public function onPreReplace(CRM_Extension_Info $oldInfo, CRM_Extension_Info $newInfo) {
    // The code of the component is bundled into the top-level project. There is no piecemeal downloading.
    throw new \CRM_Core_Exception("Components do not support replace() operations");
  }

  public function onPostReplace(CRM_Extension_Info $oldInfo, CRM_Extension_Info $newInfo) {
    // The code of the component is bundled into the top-level project. There is no piecemeal downloading.
    throw new \CRM_Core_Exception("Components do not support replace() operations");
  }

  // We could override a bunch of other lifecycle methods ('onPreInstall()', 'onPostInstall()', etc),
  // but there's no point -- since all the key artifacts are implicitly active.

}

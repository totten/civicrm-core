<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 4.7                                                |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2016                                |
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

/**
 *
 * @package CRM
 * @copyright CiviCRM LLC (c) 2004-2016
 */
class CRM_Utils_Thumbnail {

  /**
   * Return proportional height and width of the image.
   *
   * @param int $imageWidth
   *   Width of image.
   *
   * @param int $imageHeight
   *   Height of image.
   *
   * @return array
   *   Thumb dimension of image
   */
  public static function getThumbSize($imageWidth, $imageHeight) {
    $thumbWidth = 100;
    if ($imageWidth && $imageHeight) {
      $imageRatio = $imageWidth / $imageHeight;
    }
    else {
      $imageRatio = 1;
    }
    if ($imageRatio > 1) {
      $imageThumbWidth = $thumbWidth;
      $imageThumbHeight = round($thumbWidth / $imageRatio);
    }
    else {
      $imageThumbHeight = $thumbWidth;
      $imageThumbWidth = round($thumbWidth * $imageRatio);
    }

    return array($imageThumbWidth, $imageThumbHeight);
  }

}

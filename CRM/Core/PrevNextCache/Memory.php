<?php
/*
 +--------------------------------------------------------------------+
 | CiviCRM version 5                                                  |
 +--------------------------------------------------------------------+
 | Copyright CiviCRM LLC (c) 2004-2018                                |
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
 * Class CRM_Core_PrevNextCache_Memory
 *
 * Store the previous/next cache in a PSR-16 memory-backed cache.
 */
class CRM_Core_PrevNextCache_Memory implements CRM_Core_PrevNextCache_Interface {

  /**
   * @var \Psr\SimpleCache\CacheInterface
   */
  private $cache;

  /**
   * CRM_Core_PrevNextCache_Memory constructor.
   * @param \Psr\SimpleCache\CacheInterface $cache
   */
  public function __construct(\Psr\SimpleCache\CacheInterface $cache) {
    $this->cache = $cache;
  }

  public function fillWithSql($cacheKey, $sql) {
    throw new \RuntimeException("Not implemented: " . __CLASS__ . '::' . __FUNCTION__);
  }

  public function fillWithArray($cacheKey, $rows) {
    throw new \RuntimeException("Not implemented: " . __CLASS__ . '::' . __FUNCTION__);
  }

  public function fetch($cacheKey, $offset, $rowCount, $includeContactIds, $queryBao) {
    throw new \RuntimeException("Not implemented: " . __CLASS__ . '::' . __FUNCTION__);
  }

}

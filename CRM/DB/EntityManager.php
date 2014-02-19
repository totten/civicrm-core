<?php

use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationRegistry;
use Doctrine\Common\Annotations\FileCacheReader;
use Doctrine\Common\Cache\FilesystemCache;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\Driver\AnnotationDriver;
use Doctrine\ORM\Tools\Setup;

class CRM_DB_EntityManager
{
  /**
   * @var EntityManager
   */
  private static $entity_manager = NULL;

  /**
   * @return EntityManager
   */
  static function singleton() {
    if (self::$entity_manager == NULL) {
      $db_settings = new CRM_DB_Settings();
      self::reset_entity_manager($db_settings);
    }
    return self::$entity_manager;
  }

  static function reset_entity_manager($db_settings) {
    $civicrm_base_path = CRM_Utils_Path::join(__DIR__, '..', '..');

    if (!defined('CIVICRM_SYMFONY_PATH')) {
      $doctrine_annotations_path = CRM_Utils_Path::join($civicrm_base_path, 'vendor', 'doctrine', 'orm', 'lib', 'Doctrine', 'ORM', 'Mapping', 'Driver', 'DoctrineAnnotations.php');
    }
    else {
      $doctrine_annotations_path = CRM_Utils_Path::join(CIVICRM_SYMFONY_PATH, 'vendor', 'doctrine', 'orm', 'lib', 'Doctrine', 'ORM', 'Mapping', 'Driver', 'DoctrineAnnotations.php');
    }

    AnnotationRegistry::registerFile($doctrine_annotations_path);
    $annotation_cache_path = CRM_Utils_Path::join(dirname(CIVICRM_TEMPLATE_COMPILEDIR), 'cache', 'annotations');
    CRM_Utils_Path::mkdir_p_if_not_exists($annotation_cache_path);
    $annotation_file_cache = new FilesystemCache($annotation_cache_path);
    $annotation_reader = new AnnotationReader();
    $file_cache_reader = new FileCacheReader($annotation_reader, $annotation_cache_path, TRUE);
    $metadata_path = CRM_Utils_Path::join($civicrm_base_path, 'src');
    $driver = new AnnotationDriver($file_cache_reader, $metadata_path);
    $config = Setup::createConfiguration(TRUE, NULL, $annotation_file_cache);
    $config->setMetadataDriverImpl($driver);
    self::$entity_manager = EntityManager::create($db_settings->toDoctrineArray(), $config);
  }
}

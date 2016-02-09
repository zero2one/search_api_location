<?php

/**
 * @file
 * Contains \Drupal\search_api_location\LocationInput\LocationInputPluginManager.
 */

namespace Drupal\search_api_location\LocationInput;


use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;

/**
 * Defines a plugin manager for Location Inputs.
 */
class LocationInputPluginManager extends DefaultPluginManager {

  /**
   * {@inheritdoc}
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct('Plugin/search_api_location/location_input', $namespaces, $module_handler, 'Drupal\search_api_location\LocationInput\LocationInputInterface', 'Drupal\search_api_location\Annotation\LocationInput');
  }

}

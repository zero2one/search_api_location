<?php

/**
 * @file
 * Contains \Drupal\search_api_location\Annotation\LocationInput.
 */

namespace Drupal\search_api_location\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a Location Input annotation.
 *
 * @see \Drupal\search_api_location\LocationInput\LocationInputPluginManager
 * @see plugin_api
 *
 * @ingroup plugin_api
 *
 * @Annotation
 */
class LocationInput extends Plugin {

  /**
   * The Location Input plugin id.
   *
   * @var string
   */
  public $id;

  /**
   * The human-readable name of the Location Input plugin.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $label;

  /**
   * The Location Input description.
   *
   * @ingroup plugin_translatable
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

}

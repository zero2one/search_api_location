<?php

/**
 * @file
 * Contains \Drupal\search_api_location\Plugin\search_api\data_type\LocationDataType.
 */

namespace Drupal\search_api_location\Plugin\search_api\data_type;

use Drupal\search_api\DataType\DataTypePluginBase;

/**
 * Provides the location data type.
 *
 * @SearchApiDataType(
 *   id = "location",
 *   label = @Translation("Latitude/Longitude"),
 *   description = @Translation("Location data type implementation")
 * )
 */
class LocationDataType extends DataTypePluginBase {

}

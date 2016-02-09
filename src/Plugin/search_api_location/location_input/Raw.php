<?php

/**
 * @file
 * Contains \Drupal\search_api_location\Plugin\search_api_location\location_input\Raw.
 */

namespace Drupal\search_api_location\Plugin\search_api_location\location_input;

use Drupal\search_api_location\LocationInput\LocationInputInterface;

/**
 * Represents the Raw Location Input.
 *
 * @LocationInput(
 *   id = "raw",
 *   label = @Translation("Raw input"),
 *   description = @Translation("Let user enter a location as decimal latitude and longitude, separated by a comma."),
 * )
 */
class Raw implements LocationInputInterface {

  /**
   * {@inheritdoc}
   */
  public function getParsedInput($input, array $options) {
    $input = trim($input);
    return preg_match('/^[+-]?[0-9]+(?:\.[0-9]+)?,[+-]?[0-9]+(?:\.[0-9]+)?$/', $input) ? $input : NULL;
  }

}

<?php

/**
 * @file
 * Contains \Drupal\search_api_location\LocationInput\LocationInputInterface.
 */

namespace Drupal\search_api_location\LocationInput;

/**
 * Defines the required methods for a Location Input plugin.
 */
interface LocationInputInterface {

  /**
   * Returns the parsed user input.
   *
   * @param string $input
   *   The text entered by the user.
   * @param array $options
   *   The options for the plugin.
   *
   * @return
   *   $input if it is a valid location string. NULL otherwise.
   */
  public function getParsedInput($input, array $options);

}

<?php

namespace Drupal\search_api_location\LocationInput;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the required methods for a Location Input plugin.
 */
interface LocationInputInterface extends PluginFormInterface {

  /**
   * Checks if the location passed in is correct for the current settings.
   *
   * @param array $input
   *   The text entered by the user, contains either "value" or "lat"+"lng" as
   *   keys.
   * @param array $settings
   *   An array of settings for the plugin.
   *
   * @return bool
   *   True if the input is successful, false otherwise.
   */
  public function hasInput($input, array $settings);

  /**
   * Returns the parsed user input.
   *
   * @param array $input
   *   The text entered by the user, contains either "value" or "lat"+"lng" as
   *   keys.
   *
   * @return mixed
   *   Returns a string with "latitude,longitude" if we can find a location.
   *   NULL otherwise.
   */
  public function getParsedInput(array $input);

  /**
   * Returns the label of the location input.
   *
   * @return string
   *   The administration label.
   */
  public function label();

  /**
   * Returns the description of the location input.
   *
   * @return string
   *   The administration label.
   */
  public function getDescription();

  public function getForm(array $form, FormStateInterface $form_state, $options);

}

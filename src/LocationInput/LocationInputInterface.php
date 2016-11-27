<?php

namespace Drupal\search_api_location\LocationInput;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\PluginFormInterface;

/**
 * Defines the required methods for a Location Input plugin.
 */
interface LocationInputInterface extends PluginFormInterface {

  public function hasInput($input, array $settings);

  /**
   * Returns the parsed user input.
   *
   * @param string $input
   *   The text entered by the user.
   *
   * @return mixed
   *   $input if it is a valid location string. NULL otherwise.
   */
  public function getParsedInput($input);

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

<?php

namespace Drupal\search_api_location\Plugin\search_api_location\location_input;

use Drupal\Core\Form\FormStateInterface;
use Drupal\search_api_location\LocationInput\LocationInputPluginBase;

/**
 * Represents the Raw Location Input.
 *
 * @LocationInput(
 *   id = "geocode",
 *   label = @Translation("Geocoded input"),
 *   description = @Translation("Let user enter an address that will be geocoded."),
 * )
 */
class Geocode extends LocationInputPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getParsedInput($input) {
    $active_plugins = $this->getActivePlugins();
    /** @var \Geocoder\Model\AddressCollection $geocoded_addresses */
    $geocoded_addresses = \Drupal::service('geocoder')->geocode($input, $active_plugins);

    if ($geocoded_addresses) {
      return $geocoded_addresses->first()->getLatitude() . ',' . $geocoded_addresses->first()->getLongitude();
    }

    return NULL;
  }

  /**
   * Gets the active geocoder plugins.
   */
  public function getActivePlugins() {
    $plugins = $this->configuration['plugins'];

    $active_plugins = [];
    foreach ($plugins as $id => $plugin) {
      if ($plugin['checked']) {
        $active_plugins[(int) $plugin['weight']] = $id;
      }
    }

    return $active_plugins;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return array(
      'plugins' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildConfigurationForm(array $form, FormStateInterface $form_state) {

    $geocoderpluginmanager = \Drupal::service('plugin.manager.geocoder.provider');

    $form['plugins'] = [
      '#type' => 'table',
      '#header' => [$this->t('Geocoder plugins'), $this->t('Weight')],
      '#tabledrag' => [[
        'action' => 'order',
        'relationship' => 'sibling',
        'group' => 'plugins-order-weight',
      ],
      ],
      '#caption' => $this->t('Select the Geocoder plugins to use, you can reorder them. The first one to return a valid value will be used.'),
    ];

    foreach ($geocoderpluginmanager->getPluginsAsOptions() as $plugin_id => $plugin_name) {
      $form['plugins'][$plugin_id] = [
        'checked' => [
          '#type' => 'checkbox',
          '#title' => $plugin_name,
          '#default_value' => $this->configuration['plugins'][$plugin_id]['checked'],
        ],
        'weight' => array(
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title', ['@title' => $plugin_name]),
          '#title_display' => 'invisible',
          '#attributes' => ['class' => ['plugins-order-weight']],
          '#default_value' => $this->configuration['plugins'][$plugin_id]['weight'],
        ),
        '#attributes' => ['class' => ['draggable']],
      ];
    }

    $form += parent::buildConfigurationForm($form, $form_state);

    return $form;
  }

  /**
   * Form validation handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function validateConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement validateConfigurationForm() method.
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the plugin form as built
   *   by static::buildConfigurationForm().
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the complete form.
   */
  public function submitConfigurationForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitConfigurationForm() method.
  }

}

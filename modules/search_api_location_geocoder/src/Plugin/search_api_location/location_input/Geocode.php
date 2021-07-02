<?php

namespace Drupal\search_api_location_geocoder\Plugin\search_api_location\location_input;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\geocoder\Entity\GeocoderProvider;
use Drupal\geocoder\Geocoder;
use Drupal\search_api_location\LocationInput\LocationInputPluginBase;
use Drupal\Component\Utility\SortArray;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Represents the Raw Location Input.
 *
 * @LocationInput(
 *   id = "geocode",
 *   label = @Translation("Geocoded input"),
 *   description = @Translation("Let user enter an address that will be geocoded."),
 * )
 */
class Geocode extends LocationInputPluginBase implements ContainerFactoryPluginInterface {

  /**
   * The geocoder service.
   *
   * @var \Drupal\geocoder\Geocoder
   */
  protected $geocoder;

  /**
   * The geocoder config.
   *
   * @var \Drupal\Core\Config\ImmutableConfig
   */
  protected $geocoderConfig;

  /**
   * Entity Type Manager
   *
   * @var EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a Geocode Location input Plugin.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\geocoder\Geocoder $geocoder
   *   The geocoder service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   A config factory for retrieving required config objects.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The Entity Type Manager for loading the providers.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, Geocoder $geocoder, ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->geocoder = $geocoder;
    $this->geocoderConfig = $config_factory->get('geocoder.settings');
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('geocoder'),
      $container->get('config.factory'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getParsedInput(array $input) {
    if (!empty($input['value'])) {
      $providers = $this->getEnabledProviders();

      $geocoded_addresses = $this->geocoder->geocode($input['value'], $providers);
      if ($geocoded_addresses) {
        $coordinates = $geocoded_addresses->first()->getCoordinates();
        return $coordinates->getLatitude() . ',' . $coordinates->getLongitude();
      }
      else {
        throw new \InvalidArgumentException('Input doesn\'t contain a location value.');
      }
    }
    return NULL;
  }

  /**
   * Gets the active geocoder plugins.
   */
  protected function getEnabledProviders() {
    // Get the current selected providers
    $selected_providers = $this->configuration['plugins'];

    // Load all the providers
    $geocoderProviders = $this->entityTypeManager->getStorage('geocoder_provider')->loadMultiple();

    // Filter out all providers that are disabled.
    $providers = array_filter($geocoderProviders, function (GeocoderProvider $provider) use ($selected_providers): bool {
      return !empty($selected_providers[$provider->id()]) && $selected_providers[$provider->id()]['checked'] == TRUE;
    });

    // Sort providers according to weight.
    uasort($providers, function (GeocoderProvider $a, GeocoderProvider $b) use ($selected_providers): int {
      if ((int) $selected_providers[$a->id()]['weight'] === (int) $selected_providers[$b->id()]['weight']) {
        return 0;
      }

      return (int) $selected_providers[$a->id()]['weight'] < (int) $selected_providers[$b->id()]['weight'] ? -1 : 1;
    });

    return $providers;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    $configuration = parent::defaultConfiguration();
    $configuration['plugins'] = [];

    $geocoderpluginmanager = \Drupal::service('plugin.manager.geocoder.provider');

    foreach ($geocoderpluginmanager->getPluginsAsOptions() as $plugin_id => $plugin_name) {
      $configuration['plugins'][$plugin_id]['checked'] = 0;
      $configuration['plugins'][$plugin_id]['weight'] = 0;
    }

    return $configuration;
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
        'weight' => [
          '#type' => 'weight',
          '#title' => $this->t('Weight for @title', ['@title' => $plugin_name]),
          '#title_display' => 'invisible',
          '#attributes' => ['class' => ['plugins-order-weight']],
          '#default_value' => $this->configuration['plugins'][$plugin_id]['weight'],
        ],
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

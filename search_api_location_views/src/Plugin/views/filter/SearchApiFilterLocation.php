<?php

namespace Drupal\search_api_location_views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\SubformState;
use Drupal\search_api_location\LocationInput\LocationInputPluginManager;
use Drupal\views\Plugin\views\filter\FilterPluginBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines a filter for filtering on location fields.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("search_api_location")
 */
class SearchApiFilterLocation extends FilterPluginBase {

  /**
   * The location plugin manager.
   *
   * @var \Drupal\search_api_location\LocationInput\LocationInputPluginManager
   */
  protected $locationInputManager;

  /**
   * Constructs a Search API Location Filter.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param LocationInputPluginManager $location_input_manager
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, LocationInputPluginManager $location_input_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);

    $this->locationInputManager = $location_input_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('plugin.manager.search_api_location.location_input')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();

    $options['plugin']['default'] = '';
    foreach ($this->locationInputManager->getDefinitions() as $id => $plugin) {
      $options["plugin-$id"]['default'] = array();
    }

    $options['radius_type']['default'] = 'select';
    $options['radius_options']['default'] = "- -\n5 5 km\n10 10 km\n16.09 10 mi";
    $options['radius_units']['default'] = '1';

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function hasExtraOptions() {
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildExtraOptionsForm(&$form, FormStateInterface $form_state) {

    $form['plugin'] = array(
      '#type' => 'select',
      '#title' => $this->t('Input method'),
      '#description' => $this->t('Select the method to use for parsing locations entered by the user.'),
      '#options' => $this->locationInputManager->getInstancesOptions(),
      '#default_value' => $this->options['plugin'],
      '#required' => TRUE,
    );

    foreach ($this->locationInputManager->getDefinitions() as $id => $plugin) {
      $plugin = $this->locationInputManager->createInstance($id, $this->options['plugin-' . $id]);
      $form["plugin-$id"] = [
        '#type' => 'fieldset',
        '#title' => $plugin->getDescription(),
        '#tree' => TRUE,
        '#states' => [
          'visible' => [
            'select[name="options[plugin]"]' => ['value' => $id],
          ],
        ],
      ];
      $form["plugin-$id"] += $plugin->buildConfigurationForm($form["plugin-$id"], $form_state);
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitExtraOptionsForm($form, FormStateInterface $form_state) {

    $plugin_id = $form_state->getValues()['options']['plugin'];

    /** @var \Drupal\search_api_location\LocationInput\LocationInputInterface $plugin */
    $plugin = $this->locationInputManager->createInstance($plugin_id, $this->options['plugin-' . $plugin_id]);
    $processor_form_state = SubformState::createForSubform($form['plugin-' . $plugin_id], $form, $form_state);
    $plugin->submitConfigurationForm($form['plugin-' . $plugin_id], $processor_form_state);

    parent::submitExtraOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) {
    $form['operator'] = [
      '#type' => 'select',
      '#title' => $this->t('Location'),
      '#options' => [
        '<' => 'within',
        '<>' => 'between',
        '>' => 'outside of',
      ],
    ];
    $form['operator']['#default_value'] = $this->operator;
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    $plugin_id = $this->options['plugin'];

    /** @var \Drupal\search_api_location\LocationInput\LocationInputInterface $plugin */
    $plugin = $this->locationInputManager->createInstance($plugin_id, $this->options['plugin-' . $plugin_id]);

    $form = $plugin->getForm($form, $form_state, $this->options);
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    $plugin_id = $this->options['plugin'];
    /** @var \Drupal\search_api_location\LocationInput\LocationInputInterface $plugin */
    $plugin = $this->locationInputManager->createInstance($this->options['plugin'], $this->options['plugin-' . $plugin_id]);

    if (!$plugin->hasInput($this->value, $this->options)) {
      return;
    }

    $location = $plugin->getParsedInput($this->value);
    if (!$location) {
      drupal_set_message(t('The location %location could not be resolved and was ignored.', array('%location' => $this->value['value'])), 'warning');
      return;
    }
    $location = explode(',', $location, 2);
    /** @var \Drupal\search_api\Query\Query $query */
    $query = $this->query;

    $location_options = (array) $query->getOption('search_api_location', array());
    // If the radius isn't numeric omit it. Necessary since "no radius" is "-".
    $radius = (!is_numeric($this->value['distance']['from'])) ? NULL : $this->value['distance']['from'];
    if ($this->options['radius_type'] == 'textfield' && is_numeric($this->options['radius_units'])) {
      $radius *= $this->options['radius_units'];
    }
    $location_options[] = array(
      'field' => $this->realField,
      'lat' => $location[0],
      'lon' => $location[1],
      'radius' => $radius,
    );
    $query->setOption('search_api_location', $location_options);
  }

}

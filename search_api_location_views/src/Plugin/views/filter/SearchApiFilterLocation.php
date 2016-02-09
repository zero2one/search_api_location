<?php

/**
 * @file
 * Contains Drupal\search_api_location_views\Plugin\views\filter\SearchApiFilterLocation.
 */

namespace Drupal\search_api_location_views\Plugin\views\filter;

use Drupal\Core\Form\FormStateInterface;
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
   * @var
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
   * @param mixed $location_input_manager
   *   The plugin implementation definition.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition,LocationInputPluginManager $location_input_manager) {
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
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['plugin'] = array(
      '#type' => 'select',
      '#title' => t('Input method'),
      '#description' => t('Select the method to use for parsing locations entered by the user.'),
      '#options' => $this->getOptions(),
      '#default_value' => $this->options['plugin'],
      '#required' => TRUE,
    );
    foreach ($this->locationInputManager->getDefinitions() as $id => $plugin) {
      $form["plugin-$id"] = array(
        '#type' => 'fieldset',
        '#title' => t('Input method settings'),
        '#description' => $plugin['description']->render(),
        '#tree' => TRUE,
        '#dependency' => array(
          'edit-options-plugin' => array($id)
        ),
      );
      // @TODO Raw is not using 'form callback'. Verify if we need this for
      // geocoder.
      /*if (!empty($plugin['form callback'])) {
        $plugin_form = $plugin['form callback']($form_state, $this->options["plugin-$id"]);
        if ($plugin_form) {
          $form["plugin-$id"] += $plugin_form;
        }
      }*/
    }

    $form['radius_type'] = array(
      '#type' => 'select',
      '#title' => t('Type of distance input'),
      '#description' => t('Select the type of input element for the distance option.'),
      '#options' => array(
        'select' => t('Select'),
        'textfield' => t('Text field'),
      ),
      '#default_value' => $this->options['radius_type'],
      '#dependency' => array(
        'edit-options-expose-use-operator' => array(1),
      ),
    );
    $form['radius_options'] = array(
      '#type' => 'textarea',
      '#title' => t('Distance options'),
      '#description' => t('Add one line per option for “Range” you want to provide. The first part of each line is the distance in kilometres, everything after the first space is the label. "-" as the distance ignores the location for filtering, but will still use it for facets, sorts and distance calculation. Skipping the distance altogether (i.e., starting the line with a space) will provide an option for ignoring the entered location completely.'),
      '#default_value' => $this->options['radius_options'],
      '#dependency' => array(
        'edit-options-radius-type' => array('select'),
      ),
    );
    $form['radius_units'] = array(
      '#type' => 'textfield',
      '#title' => t('Distance conversion factor'),
      '#description' => t('Enter the conversion factor from the expected unit of the user input to kilometers. E.g., miles would have a factor of 1.60935.'),
      '#default_value' => $this->options['radius_units'],
      '#dependency' => array(
        'edit-options-radius-type' => array('textfield'),
      ),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function operatorForm(&$form, FormStateInterface $form_state) {
    if ($this->options['radius_type'] == 'select') {
      $form['operator'] = [
        '#type' => 'select',
        '#options' => $this->getOperatorOptions(),
      ];
      $form['operator']['#default_value'] = $this->operator;
    }
    else {
      $form['distance'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Distance'),
        '#size' => 10,
        '#default_value' => $this->operator,
      ];
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function valueForm(&$form, FormStateInterface $form_state) {
    parent::valueForm($form, $form_state);
    $form['value'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Value Test'),
      '#size' => 20,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    while (is_array($this->value)) {
      $this->value = reset($this->value);
    }
    $this->value = trim($this->value);
    if (!$this->value || !($this->operator || is_numeric($this->operator))) {
      return;
    }
    /*if (empty($this->options['plugin'])) {
      $vars = array(
        '%filter' => $this->ui_name(TRUE),
        '%view' => $this->view->get_human_name(),
      );
      watchdog('search_api_location', 'Filter %filter in view %view has no location input plugin selected. Ignoring location filter.', $vars, WATCHDOG_WARNING);
      return;
    }*/
    /** @var \Drupal\search_api_location\Plugin\search_api_location\location_input\Raw $plugin */
    $plugin = $this->locationInputManager->createInstance($this->options['plugin']);
    /*if (!$plugin) {
      $vars = array(
        '%filter' => $this->ui_name(TRUE),
        '%view' => $this->view->get_human_name(),
        '%plugin' => $this->options['plugin'],
      );
      watchdog('search_api_location', 'Filter %filter in view %view uses unknown location input plugin %plugin. Ignoring location filter.', $vars, WATCHDOG_WARNING);
      return;
    }*/
    $location = $plugin->getParsedInput($this->value, $this->options['plugin-' . $this->options['plugin']]);
    /*if (!$location) {
      drupal_set_message(t('The location %address could not be resolved and was ignored.', array('%address' => $this->value)), 'warning');
      return;
    }*/
    $location = explode(',', $location, 2);
    $location_options = (array) $this->query->getOption('search_api_location', array());
    // If the radius isn't numeric omit it. Necessary since "no radius" is "-".
    $radius = (!is_numeric($this->operator)) ? NULL : $this->operator;
    /*if ($this->options['radius_type'] == 'textfield' && is_numeric($this->options['radius_units'])) {
      $radius *= $this->options['radius_units'];
    }*/
    $location_options[] = array(
      'field' => $this->realField,
      'lat' => $location[0],
      'lon' => $location[1],
      'radius' => $radius,
    );
    $this->query->setOption('search_api_location', $location_options);

  }

  /**
   * {@inheritdoc}
   */
  public function getOperatorOptions() {
    $options = [];
    $lines = array_filter(array_map('rtrim', explode("\n", $this->options['radius_options'])));
    foreach ($lines as $line) {
      $pos = strpos($line, ' ');
      $range = substr($line, 0, $pos);
      $options[$range] = trim(substr($line, $pos + 1));
    }
    return $options;
  }

  /**
   * @return array
   */
  public function getOptions() {
    $options = [];
    foreach ($this->locationInputManager->getDefinitions() as $id => $plugin) {
      $options[$id] = $plugin['label']->render();
    }
    return $options;
  }

}

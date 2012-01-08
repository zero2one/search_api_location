<?php

/**
 * @file
 * Provides the Search API processor for location based search
 */

class SearchApiLocation extends SearchApiAbstractProcessor {

  /**
   * Constructor
   */
  public function __construct(SearchApiIndex $index, array $options = array()) {
    parent::__construct($index, $options);
    $this->options += array(
      'location_searchtype' => 1
    );
  }

  public function supportsIndex(SearchApiIndex $index) {

    foreach ($index->options['fields'] as $key => $value) {
      if (isset($value['real_type']) && $value['real_type'] == 'location') {
        return TRUE;
      }
    }

    return FALSE;
  }

  public function configurationForm() {
    $form = parent::configurationForm();

    foreach ($form['fields']['#options'] as $key => $value) {
      if (!isset($this->index->options['fields'][$key]['real_type']) ||
      $this->index->options['fields'][$key]['real_type'] != 'location') {
        unset($form['fields']['#options'][$key]);
        unset($form['fields']['#default_value'][$key]);
      }
    }

    $form += array(
      'location_searchtype' => array(
        '#type' => 'radios',
        '#title' => t('Search type'),
        '#description' => t('Select the type of location based search you want to use.'),
        '#options' => array(
          '1' => 'distance around a point (latitude, longitude and radius)',
      //    '2' => 'bounding box (everything in a box from thel left top to the right bottom)',
        ),
        '#default_value' => $this->options['location_searchtype'],
      ),
    );

    return $form;
  }

  /**
  * Calls processKeys() for the keys and processFilters() for the filters.
  */
  public function preprocessSearchQuery(SearchApiQuery $query) {

    $locationfield = _search_api_location_get_locationfield($query->getIndex()->id);

    $search_id = explode(':', $query->getOption('search id'));

    if ($locationfield && $search_id[0] == "search_api_page") {

      $keys = $query->getKeys();
      $spatial = _search_api_location_get_location($keys);
      $spatial['field'] = $locationfield;

      $entity_query = new EntityFieldQuery;
      $result = $entity_query
        ->entityCondition('entity_type', 'search_api_page')
        ->propertyCondition('path', $search_id[1])
        ->execute();

      // Load the search page, so we can get the measure defined in the search page
      $page = search_api_page_load(array_shift(array_keys($result['search_api_page'])));

      //add the measure, so we can recalculate the radius value if necessery
      $spatial['radius_measure'] = $page->options['spatial'] ['radius_measure'];

      $query->setOption('spatial', $spatial);

      $query->keys(_search_api_location_get_keys($keys));
      $keys = $query->getKeys();
    }
    elseif ($locationfield && $search_id[0] == "search_api_views") {
      // everyting happens in the location filter
    }
  }

  public function postprocessSearchResults(array &$response, SearchApiQuery $query) {
    return;
  }
}

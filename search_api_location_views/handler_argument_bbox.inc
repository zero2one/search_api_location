<?php
/**
 * @file
 * Contains SearchApiViewsHandlerArgumentLocationBbox.
 */

/**
 * Provides an argument handler for filtering location fields to a bounding box.
 */
class SearchApiViewsHandlerArgumentLocationBbox extends SearchApiViewsHandlerArgument {

  /**
   * Parses the comma-separated representation of a rectangle into its parts.
   *
   * @param string $bbox
   *   The bounding box argument to parse, in the format: left,bottom,right,top.
   *
   * @return array|null
   *   NULL if $bbox didn't have the correct format. Otherwise, an associative
   *   array with the rectangle coordinates in the keys:
   *   - left
   *   - bottom
   *   - right
   *   - top
   */
  protected function parseCorners($bbox) {
    $parts = explode(',', $bbox);
    if (count($parts) == 4) {
      return array_combine(array('left', 'bottom', 'right', 'top'), $parts);
    }
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    // Split the bbox string into an array.
    if ($bbox = $this->parseCorners($this->argument)) {
      $location_options = $this->query->getOption('search_api_location', array());
      $location_options[] = array(
        'field' => $this->real_field,
        'bbox' => $bbox,
      );
      // Add it to the query.
      $this->query->setOption('search_api_location', $location_options);
    }
  }

}

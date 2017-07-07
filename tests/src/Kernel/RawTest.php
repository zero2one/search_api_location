<?php

namespace Drupal\Tests\search_api_location\Kernel;

use Drupal\KernelTests\KernelTestBase;
use Drupal\search_api_location\Plugin\search_api_location\location_input\Raw;

/**
 * Tests RawTest plugin parsing.
 *
 * @group search_api_location
 */
class RawTest extends KernelTestBase {

  /**
   * Modules to enable for this test.
   *
   * @var string[]
   */
  public static $modules = [
    'user',
    'search_api',
    'search_api_location'
  ];

  /**
   * Test the parsed input entered by user in raw format.
   */
  public function testGetParsedInput() {
    $sut = $this->container
      ->get('plugin.manager.search_api_location.location_input')
      ->createInstance('raw');
    $this->assertEquals($sut->getParsedInput("  20.548,67.945 "), "20.548,67.945");
    $this->assertEquals($sut->getParsedInput("^20.548,67.945"), NULL);
  }

}

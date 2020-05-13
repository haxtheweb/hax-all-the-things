<?php

namespace Drupal\jsonapi_include;

/**
 * Interface JsonapiParseInterface.
 *
 * @package Drupal\jsonapi_include
 */
interface JsonapiParseInterface {

  /**
   * Parse json api.
   *
   * @param string|object $response
   *   The response data from jsonapi.
   *
   * @return mixed
   *   Parse jsonapi data.
   */
  public function parse($response);

}

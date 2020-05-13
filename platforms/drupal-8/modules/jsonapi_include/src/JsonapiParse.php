<?php

namespace Drupal\jsonapi_include;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;

/**
 * Class JsonapiParse.
 *
 * @package Drupal\jsonapi_include
 */
class JsonapiParse implements JsonapiParseInterface {

  /**
   * The variable store included data.
   *
   * @var array
   */
  protected $included;

  /**
   * Parse json api.
   *
   * @param string|object $response
   *   The response data from jsonapi.
   *
   * @return mixed
   *   Parse jsonapi data.
   */
  public function parse($response) {
    $content = $this->parseJsonContent($response);
    return is_string($content) ? $content : Json::encode($content);
  }

  /**
   * Check array is assoc.
   *
   * @param array $arr
   *   The array.
   *
   * @return bool
   *   Check result.
   */
  protected function isAssoc(array $arr) {
    if ([] === $arr) {
      return FALSE;
    }
    return array_keys($arr) !== range(0, count($arr) - 1);
  }

  /**
   * Group include.
   *
   * @param object|array $object
   *   The input data.
   *
   * @return array
   *   The includes data.
   */
  protected function groupIncludes($object) {
    $result = [];
    $included = !empty($object['included']) ? $object['included'] : [];
    array_walk($included, function ($resource, $index) use (&$result) {
      $result[$resource['type']][$resource['id']] = $resource;
    });
    return $result;
  }

  /**
   * Resolve attributes.
   *
   * @param array|mixed $item
   *   The input item.
   *
   * @return array
   *   The resolve output.
   */
  protected function resolveAttributes($item) {
    $resource = $item;
    if (!empty($resource['attributes'])) {
      foreach ($resource['attributes'] as $name => $value) {
        $resource[$name] = $value;
      }
      unset($resource['attributes']);
    }
    return $resource;
  }

  /**
   * Flatten included.
   *
   * @param array|mixed $resource
   *   The resource.
   *
   * @return array
   *   The result.
   */
  protected function flattenIncluded($resource) {
    if (isset($this->included[$resource['type']][$resource['id']])) {
      $object = $this->resolveAttributes($this->included[$resource['type']][$resource['id']]);
      if (isset($resource['meta'])) {
        $object['meta'] = $resource['meta'];
      }
    }
    else {
      $object = $resource;
    }
    $result = $this->resolveRelationships($object);
    return $result;
  }

  /**
   * Check resource is include.
   *
   * @param array|mixed $resource
   *   The resource to verify.
   *
   * @return bool
   *   Check result.
   */
  protected function isIncluded($resource) {
    return isset($resource['type']) && isset($this->included[$resource['type']]);
  }

  /**
   * Resolve data.
   *
   * @param array|mixed $data
   *   The data for resolve.
   *
   * @return array
   *   Result.
   */
  protected function resolveData($data) {
    if ($this->isIncluded($data)) {
      return $this->flattenIncluded($data);
    }
    else {
      return $data;
    }
  }

  /**
   * Resolve data.
   *
   * @param array|mixed $links
   *   The data for resolve.
   *
   * @return array
   *   Result.
   */
  protected function resolveRelationshipData($links) {
    if (empty($links['data'])) {
      return $links;
    }
    $output = [];
    if (!$this->isAssoc($links['data'])) {
      foreach ($links['data'] as $item) {
        $output[] = $this->resolveData($item);
      }
    }
    else {
      $output = $this->resolveData($links['data']);
    }
    return $output;
  }

  /**
   * Resolve relationships.
   *
   * @param array|mixed $resource
   *   The data for resolve.
   *
   * @return array
   *   Result.
   */
  protected function resolveRelationships($resource) {
    if (empty($resource['relationships'])) {
      return $resource;
    }

    foreach ($resource['relationships'] as $key => $value) {
      $resource[$key] = $this->resolveRelationshipData($value);
    }
    unset($resource['relationships']);
    return $resource;
  }

  /**
   * Parse Resource.
   *
   * @param array|mixed $item
   *   The data for resolve.
   *
   * @return array
   *   Result.
   */
  protected function parseResource($item) {
    $attributes = $this->resolveAttributes($item);
    return $this->resolveRelationships($attributes);
  }

  /**
   * Parse json content.
   *
   * @param object $response
   *   The jsonapi object.
   *
   * @return mixed
   *   Parse result.
   */
  protected function parseJsonContent($response) {
    if (is_string($response)) {
      $json = Json::decode($response);
    }
    elseif (is_object($response)) {
      $json = $response;
    }
    else {
      return $response;
    }
    if (NestedArray::getValue($json, ['jsonapi', 'parsed'])) {
      return $json;
    }
    if (isset($json['errors']) || empty($json['data'])) {
      return $json;
    }
    $this->included = $this->groupIncludes($json);
    $data = [];
    if (!$this->isAssoc($json['data'])) {
      foreach ($json['data'] as $item) {
        $data[] = $this->parseResource($item);
      }
    }
    else {
      $data = $this->parseResource($json['data']);
    }
    if (isset($json['included'])) {
      unset($json['included']);
    }
    $json['jsonapi']['parsed'] = TRUE;
    $json['data'] = $data;
    return $json;
  }

}

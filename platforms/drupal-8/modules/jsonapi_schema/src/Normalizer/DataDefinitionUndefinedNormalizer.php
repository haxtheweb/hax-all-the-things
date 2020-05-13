<?php

namespace Drupal\jsonapi_schema\Normalizer;

use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Data definition normalizer.
 */
class DataDefinitionUndefinedNormalizer extends DataDefinitionNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedDataTypes = ['undefined'];

  /**
   * {@inheritdoc}
   */
  protected function extractPropertyData(DataDefinitionInterface $property, array $context = []) {
    return isset($context['name'])
      ? (object) ['title' => $context['name']]
      : (object) [];
  }

}

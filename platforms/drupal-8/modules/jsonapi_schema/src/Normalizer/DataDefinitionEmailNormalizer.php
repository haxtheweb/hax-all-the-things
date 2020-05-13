<?php

namespace Drupal\jsonapi_schema\Normalizer;

use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Data definition normalizer.
 */
class DataDefinitionEmailNormalizer extends DataDefinitionNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedDataTypes = ['email'];

  /**
   * {@inheritdoc}
   */
  protected function extractPropertyData(DataDefinitionInterface $property, array $context = []) {
    $value = parent::extractPropertyData($property);
    $value['type'] = 'string';
    $value['format'] = 'email';
    return $value;
  }

}

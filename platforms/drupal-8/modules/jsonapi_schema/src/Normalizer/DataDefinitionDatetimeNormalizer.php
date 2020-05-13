<?php

namespace Drupal\jsonapi_schema\Normalizer;

use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Data definition normalizer.
 */
class DataDefinitionDatetimeNormalizer extends DataDefinitionNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedDataTypes = ['datetime_iso8601'];

  /**
   * {@inheritdoc}
   */
  protected function extractPropertyData(DataDefinitionInterface $property, array $context = []) {
    $value = parent::extractPropertyData($property);
    $value['type'] = 'string';
    $value['format'] = 'date';
    return $value;
  }

}

<?php

namespace Drupal\jsonapi_schema\Normalizer;

use Drupal\Core\TypedData\DataDefinitionInterface;

/**
 * Data definition normalizer.
 */
class DataDefinitionTimestampNormalizer extends DataDefinitionNormalizer {

  /**
   * {@inheritdoc}
   */
  protected $supportedDataTypes = ['timestamp'];

  /**
   * {@inheritdoc}
   */
  protected function extractPropertyData(DataDefinitionInterface $property, array $context = []) {
    $value = parent::extractPropertyData($property);
    $value['type'] = 'number';
    $value['format'] = 'utc-millisec';
    return $value;
  }

}

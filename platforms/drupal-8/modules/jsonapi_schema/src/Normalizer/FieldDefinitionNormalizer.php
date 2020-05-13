<?php

namespace Drupal\jsonapi_schema\Normalizer;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Normalizer for FieldDefinitionInterface objects.
 *
 * This normalizes the variant of data fields particular to the Field system.
 * By accessing this via the FieldDefinitionInterface, there is greater access
 * to some of the methods providing deeper schema properties.
 */
class FieldDefinitionNormalizer extends ListDataDefinitionNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = FieldDefinitionInterface::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($field_definition, $format = NULL, array $context = []) {
    assert($field_definition instanceof FieldDefinitionInterface);
    $cardinality = $field_definition->getFieldStorageDefinition()->getCardinality();
    $context['cardinality'] = $cardinality;
    $normalized = parent::normalize($field_definition, $format, $context);

    // Specify non-contextual default value as an example.
    $default_value = $field_definition->getDefaultValueLiteral();
    $field_name = $context['name'];
    if (!empty($default_value)) {
      $field_type = $field_definition->getType();
      $default_value = $cardinality == 1 ? reset($default_value) : $default_value;
      $default_value = count($default_value) == 1 ? reset($default_value) : $default_value;
      $default_value = $field_type == "boolean" ? boolval($default_value) : $default_value;
      NestedArray::setValue(
        $normalized,
        ['properties', $field_name, 'default'],
        $default_value
      );
    }

    // The cardinality is the configured maximum number of values the field can
    // contain. If unlimited, we do not include a maxItems attribute.
    if ($cardinality != FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && $cardinality != 1) {
      NestedArray::setValue(
        $normalized,
        ['properties', $field_name, 'maxItems'],
        $cardinality
      );
    }

    return $normalized;
  }

}

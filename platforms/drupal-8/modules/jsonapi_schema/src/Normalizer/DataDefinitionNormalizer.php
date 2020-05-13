<?php

namespace Drupal\jsonapi_schema\Normalizer;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\TypedData\DataDefinitionInterface;
use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Normalizer for DataDefinitionInterface instances.
 *
 * DataDefinitionInterface is the ultimate parent to all data definitions. This
 * service must always be low priority for data definitions, otherwise the
 * simpler normalization process it supports will take precedence over all the
 * complexities most entity properties contain before reaching this level.
 *
 * DataDefinitionNormalizer produces scalar value definitions.
 *
 * All the TypedData normalizers extend from this class.
 */
class DataDefinitionNormalizer extends NormalizerBase {

  const JSON_TYPES = ['null', 'boolean', 'string', 'number', 'integer', 'array', 'object'];

  /**
   * The formats that the Normalizer can handle.
   *
   * @var array
   */
  protected $format = 'schema_json';

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = DataDefinitionInterface::class;

  /**
   * The supported data type.
   *
   * @var string[]
   */
  protected $supportedDataTypes = [];

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    assert($entity instanceof DataDefinitionInterface);
    // `text source` and `date source` produce objects not supported in the API.
    // It is not clear how the API excludes them.
    // @todo properly identify and exclude this class of computed objects.
    if (
      $entity->getSetting('text source')
      || $entity->getSetting('date source')
    ) {
      return [];
    }

    $property = $this->extractPropertyData($entity, $context);
    if (!is_object($property) && !empty($context['parent']) && $context['name'] == 'value') {
      if ($maxLength = $context['parent']->getSetting('max_length')) {
        $property['maxLength'] = $maxLength;
      }

      if (empty($context['parent']->getSetting('allowed_values_function'))
        && !empty($context['parent']->getSetting('allowed_values'))
      ) {
        $allowed_values = $context['parent']->getSetting('allowed_values');
        $property['enum'] = array_keys($allowed_values);
      }
    }

    if (!is_object($property) && !isset($property['title']) && isset($context['name'])) {
      $property['title'] = $context['name'];
    }

    $normalized = ['properties' => []];
    if (!is_object($property) && !in_array($property['type'], static::JSON_TYPES)) {
      // Unable to find the correct type.
      \Drupal::logger('jsonapi_schema')->error('{type} is not a valid type for a JSON document.', ['type' => $property['type']]);
      $property = (object) [];
    }
    $normalized['properties'][$context['name']] = $property;
    if ($this->requiredProperty($entity)) {
      $normalized['required'][] = $context['name'];
    }

    return $normalized;
  }

  /**
   * Extracts property details from a data definition.
   *
   * This method includes mapping primitive types in Drupal to JSON Schema
   * type and format descriptions. This method is invoked by several of the
   * normalizers.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $property
   *   The data definition from which to extract values.
   * @param array $context
   *   Serializer context.
   *
   * @return array
   *   Discrete values of the property definition
   */
  protected function extractPropertyData(DataDefinitionInterface $property, array $context = []) {
    $value = ['type' => $property->getDataType()];
    if ($item = $property->getLabel()) {
      $value['title'] = $item;
    }
    if ($item = $property->getDescription()) {
      $value['description'] = addslashes(strip_tags($item));
    }

    return $value;
  }

  /**
   * Normalize an array of data definitions.
   *
   * This normalization process gets an array of properties and an array of
   * properties that are required by name. This is needed by the
   * SchemataSchemaNormalizer, otherwise it would have been placed in
   * DataDefinitionNormalizer.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface[] $items
   *   An array of data definition properties to be normalized.
   * @param string $format
   *   Format identifier of the current serialization process.
   * @param array $context
   *   Operating context of the serializer.
   *
   * @return array
   *   Array containing one or two nested arrays.
   *   - properties: The array of all normalized properties.
   *   - required: The array of required properties by name.
   */
  protected function normalizeProperties(array $items, $format, array $context = []) {
    $normalized = [];
    foreach ($items as $name => $property) {
      $context['name'] = $name;
      $item = $this->serializer->normalize($property, $format, $context);
      if (!empty($item)) {
        $normalized = NestedArray::mergeDeep($normalized, $item);
      }
    }

    return $normalized;
  }

  /**
   * Determine if the given property is a required element of the schema.
   *
   * @param \Drupal\Core\TypedData\DataDefinitionInterface $property
   *   The data property to be evaluated.
   *
   * @return bool
   *   Whether the property should be treated as required for schema
   *   purposes.
   */
  protected function requiredProperty(DataDefinitionInterface $property) {
    return $property->isReadOnly() || $property->isRequired();
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    return parent::supportsNormalization($data, $format)
      && $data instanceof DataDefinitionInterface
      && (empty($this->supportedDataTypes) || in_array($data->getDataType(), $this->supportedDataTypes));
  }

  /**
   * {@inheritdoc}
   */
  public function supportsDenormalization($data, $type, $format = NULL) {
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function denormalize($data, $class, $format = NULL, array $context = []) {
    throw new \RuntimeException('Denormalization is not supported.');
  }

}

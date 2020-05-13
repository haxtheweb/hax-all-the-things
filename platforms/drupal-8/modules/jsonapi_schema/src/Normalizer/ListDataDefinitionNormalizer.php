<?php

namespace Drupal\jsonapi_schema\Normalizer;

use Drupal\Core\TypedData\ComplexDataDefinitionInterface;
use Drupal\Core\TypedData\DataReferenceTargetDefinition;
use Drupal\Core\TypedData\ListDataDefinitionInterface;

/**
 * Normalizer for ListDataDefinitionInterface objects.
 *
 * Almost all entity properties in the system are a list of values, each value
 * in the "List" might be a ComplexDataDefinitionInterface (an object) or it
 * might be more of a scalar.
 */
class ListDataDefinitionNormalizer extends DataDefinitionNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = ListDataDefinitionInterface::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($list_data_definition, $format = NULL, array $context = []) {
    assert($list_data_definition instanceof ListDataDefinitionInterface);
    $context['parent'] = $list_data_definition;
    $property = $this->extractPropertyData($list_data_definition, $context);
    $property['type'] = 'array';

    // This retrieves the definition common to ever item in the list, and
    // serializes it so we can define how members of the array should look.
    // There are no lists that might contain items of different types.
    $property['items'] = $this->serializer->normalize(
      $list_data_definition->getItemDefinition(),
      $format,
      $context
    );

    // FieldDefinitionInterface::isRequired() explicitly indicates there must be
    // at least one item in the list. Extending this reasoning, the same must be
    // true of all ListDataDefinitions.
    if ($this->requiredProperty($list_data_definition)) {
      $property['minItems'] = 1;
    }

    if (!empty($context['cardinality']) && $context['cardinality'] === 1) {
      $single_property = $property['items'];
      unset($property['items']);
      unset($property['type']);
      unset($property['minItems']);
      $single_property = array_merge($single_property, $property);
      $property = $single_property;
    }

    $normalized = [
      'description' => t('Entity attributes'),
      'type' => 'object',
      'properties' => [],
    ];
    $public_name = $context['name'];
    $normalized['properties'][$public_name] = $property;
    if ($this->requiredProperty($list_data_definition)) {
      $normalized['required'][] = $public_name;
    }

    return $normalized;
  }

  /**
   * Determine if the current field is a reference field.
   *
   * @param \Drupal\Core\TypedData\ListDataDefinitionInterface $entity
   *   The list definition to be checked.
   *
   * @return bool
   *   TRUE if it is a reference, FALSE otherwise.
   */
  protected function isReferenceField(ListDataDefinitionInterface $entity) {
    $item = $entity->getItemDefinition();
    if ($item instanceof ComplexDataDefinitionInterface) {
      $main = $item->getPropertyDefinition($item->getMainPropertyName());
      // @todo use an interface or API call instead of an object check.
      return ($main instanceof DataReferenceTargetDefinition);
    }

    return FALSE;
  }

}

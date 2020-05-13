<?php

namespace Drupal\jsonapi_schema\Normalizer;

use Drupal\Core\Field\FieldDefinitionInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;

/**
 * Normalizer for RelationshipFieldDefinitionNormalizer objects.
 *
 * This normalizes the JSON API relationships. This normalizer shortcuts the
 * recursion for the entity reference field. A JSON API relationship is what it
 * is, regardless of how Drupal stores the relationship.
 */
class RelationshipFieldDefinitionNormalizer extends ListDataDefinitionNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = FieldDefinitionInterface::class;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * RelationshipFieldDefinitionNormalizer constructor.
   *
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager.
   */
  public function __construct(FieldTypePluginManagerInterface $field_type_manager) {
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    if (!parent::supportsNormalization($data, $format)) {
      return FALSE;
    }
    $type = $data->getItemDefinition()->getFieldDefinition()->getType();
    $class = $this->fieldTypeManager->getPluginClass($type);
    // Deal only with entity reference fields and descendants.
    return $class == EntityReferenceItem::class || is_subclass_of($class, EntityReferenceItem::class);
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $cardinality = $entity->getFieldStorageDefinition()->getCardinality();
    $context['cardinality'] = $cardinality;
    assert($entity instanceof FieldDefinitionInterface);
    $normalized = [
      'description' => t('Entity relationships'),
      'properties' => [$context['name'] => $this->normalizeRelationship($entity, $format, $context)],
      'type' => 'object',
    ];
    // Specify non-contextual default value as an example.
    $default_value = $entity->getDefaultValueLiteral();
    if (!empty($default_value)) {
      $normalized['properties'][$context['name']]['default'] = $default_value;
    }

    // The cardinality is the configured maximum number of values the field can
    // contain. If unlimited, we do not include a maxItems attribute.
    if ($cardinality != FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED && $cardinality != 1) {
      $normalized['properties'][$context['name']]['maxItems'] = $cardinality;
    }

    return $normalized;
  }

  /**
   * Normalizes the relationship.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field_definition
   *   The field definition.
   *
   * @return array
   *   The normalized relationship.
   */
  protected function normalizeRelationship(FieldDefinitionInterface $field_definition, $format = NULL, $context = []) {
    $resource_type_repository = \Drupal::service('jsonapi.resource_type.repository');
    assert($resource_type_repository instanceof ResourceTypeRepositoryInterface);
    // A relationship has very similar schema every time.
    $resource_identifier_object = [
      'type' => 'object',
      'required' => ['type', 'id'],
      'properties' => [
        'type' => ['type' => 'string', 'title' => t('Referenced resource')],
        'id' => [
          'type' => 'string',
          'title' => t('Resource ID'),
          'format' => 'uuid',
          'maxLength' => 128,
        ],
      ],
    ];
    // Handle the multivalue variant.
    $cardinality = $field_definition
      ->getFieldStorageDefinition()
      ->getCardinality();
    if ($target_entity_type = $field_definition->getSetting('target_type')) {
      $handler_settings = $field_definition->getSetting('handler_settings');
      $target_bundles = empty($handler_settings['target_bundles']) ?
        [$target_entity_type] :
        array_values($handler_settings['target_bundles']);
      $target_resource_types = array_map(
        function ($bundle) use ($target_entity_type, $resource_type_repository) {
          return $resource_type_repository->get(
            $target_entity_type,
            $bundle ?: $target_entity_type
          );
          return $resource_type->getTypeName();
        },
        $target_bundles
      );
      $enum = array_map(function (ResourceType $resource_type) {
        return $resource_type->getTypeName();
      }, array_filter($target_resource_types));
    }
    $meta = $this->serializer->normalize($field_definition->getItemDefinition(), $format, $context);
    if ($cardinality == 1) {
      $data = $resource_identifier_object;
      if (!empty($enum)) {
        $data['properties']['type']['enum'] = $enum;
        $data['properties']['meta'] = $meta;
      }
    }
    else {
      $data = [
        'type' => 'array',
        'items' => $resource_identifier_object,
      ];
      if (!empty($enum)) {
        $data['items']['properties']['type']['enum'] = $enum;
        $data['items']['properties']['meta'] = $meta;
      }
    }
    $normalized = [
      'type' => 'object',
      'properties' => [
        'data' => $data,
      ],
      'title' => $field_definition->getLabel(),
    ];

    return $normalized;
  }

}

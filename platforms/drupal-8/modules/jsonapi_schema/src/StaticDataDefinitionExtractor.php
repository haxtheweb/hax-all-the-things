<?php

namespace Drupal\jsonapi_schema;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Config\TypedConfigManagerInterface;
use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\TypedData\DataDefinition;
use Drupal\Core\TypedData\ListDataDefinition;
use Drupal\Core\TypedData\ListDataDefinitionInterface;
use Drupal\Core\TypedData\MapDataDefinition;
use Drupal\Core\TypedData\TypedDataManagerInterface;

/**
 * Extracts the data definition for entities of an entity type.
 */
class StaticDataDefinitionExtractor {

  /**
   * A config entity ID used to determine schema of the config entity type.
   */
  const BOGUS_CONFIG_ENTITY_ID = 'ID';

  /**
   * The typed data manager used for creating the data types.
   *
   * @var \Drupal\Core\TypedData\TypedDataManagerInterface
   */
  private $typedDataManager;

  /**
   * The typed config manager.
   *
   * @var \Drupal\Core\Config\TypedConfigManagerInterface
   */
  private $typedConfigManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  private $entityFieldManager;

  /**
   * The cached definitions.
   *
   * @var \Drupal\Core\TypedData\MapDataDefinition[]
   */
  private $definitions;

  /**
   * StaticDataDefinitionExtractor constructor.
   *
   * @param \Drupal\Core\TypedData\TypedDataManagerInterface
   *   The typed data manager used for creating the data types.
   * @param \Drupal\Core\Config\TypedConfigManagerInterface $typed_config_manager
   *   The typed config manager.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(TypedDataManagerInterface $typed_data_manager, TypedConfigManagerInterface $typed_config_manager, EntityFieldManagerInterface $entity_field_manager) {
    $this->typedDataManager = $typed_data_manager;
    $this->typedConfigManager = $typed_config_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * Extracts the data definition information for an entity type and bundle.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type to extract the data definitions for.
   * @param string $bundle
   *   The entity bundle.
   *
   * @return \Drupal\Core\TypedData\DataDefinition|\Drupal\Core\TypedData\MapDataDefinition
   *   The collection of data definitions, one for each field.
   *
   * @throws \LogicException
   *   If the entity type is neither configuration or content.
   */
  public function extract(EntityTypeInterface $entity_type, $bundle) {
    $data_type = sprintf('entity:%s:%s', $entity_type->id(), $bundle);
    if (isset($this->definitions[$data_type])) {
      return $this->definitions[$data_type];
    }
    if ($entity_type instanceof ContentEntityTypeInterface) {
      $definition = $this->extractContentEntityType($entity_type, $bundle);
    }
    else if ($entity_type instanceof ConfigEntityTypeInterface) {
      $definition = $this->extractConfigEntityType($entity_type, $bundle);
    }
    else {
      throw new \LogicException('Only configuration and content entities are supported.');
    }
    $this->definitions[$data_type] = $definition;
    return $definition;
  }

  public function extractField(EntityTypeInterface $entity_type, $bundle, $field_name) {
    $definition = $this->extract($entity_type, $bundle);
    return $definition->getPropertyDefinition($field_name)
      ?: DataDefinition::createFromDataType('undefined');
  }

  private function extractContentEntityType(ContentEntityTypeInterface $entity_type, $bundle) {
    $data_type = sprintf('entity:%s', $entity_type->id());
    if ($entity_type->getBundleEntityType()) {
      $data_type .= ':' . $bundle;
    }
    return $this->typedDataManager->createDataDefinition($data_type);
  }

  private function extractConfigEntityType(ConfigEntityTypeInterface $entity_type, $bundle) {
    $data_type = sprintf('entity:%s', $entity_type->id());
    if ($entity_type->hasKey('bundle')) {
      $data_type .= ':' . $bundle;
    }
    $config_definition = $this->typedConfigManager->getDefinition(
      sprintf('%s.%s', $entity_type->getConfigPrefix(), static::BOGUS_CONFIG_ENTITY_ID),
      FALSE
    );
    $definition = MapDataDefinition::createFromDataType($data_type);
    foreach ($entity_type->getPropertiesToExport(static::BOGUS_CONFIG_ENTITY_ID) as $field_name) {
      $field_schema = NestedArray::getValue($config_definition, ['mapping', $field_name]) ?: [];
      $definition->setPropertyDefinition($field_name, $this->createDataDefinition($field_schema));
    }
    return $definition;
  }

  private function createDataDefinition($field_schema) {
    if (!isset($field_schema['type'])) {
      return DataDefinition::createFromDataType('undefined');
    }
    $data_type = $field_schema['type'];
    // Add additional information to the schema with the data definition.
    $field_schema += $this->typedConfigManager->getDefinition($data_type, FALSE);
    $definition_class = $field_schema['definition_class'];
    if (is_subclass_of($definition_class, MapDataDefinition::class)) {
      $definition = call_user_func([$definition_class, 'createFromDataType'], $data_type)
        ->setTypedDataManager($this->typedDataManager);
      assert($definition instanceof MapDataDefinition);
      foreach ($field_schema['mapping'] as $name => $item_definition) {
        $item_definition = $this->createDataDefinition($item_definition);
        $definition->setPropertyDefinition($name, $item_definition);
      }
      return $definition;
    }
    $is_sequence = FALSE;
    if (is_subclass_of($definition_class, ListDataDefinitionInterface::class)) {
      $is_sequence = TRUE;
      $data_type = $field_schema['sequence']['type'];
    }
    if ($this->typedDataManager->hasDefinition($data_type)) {
      return $is_sequence
        ? $this->typedDataManager->createListDataDefinition($data_type)
        : $this->typedDataManager->createDataDefinition($data_type);
    }
    // We tried our best. There is some information that we cannot obtain from a
    //static perspective.
    $data_definition = DataDefinition::createFromDataType('undefined')
      ->setTypedDataManager($this->typedDataManager);
    if ($is_sequence) {
      // Construct the sequence of 'undefined' manually.
      $data_definition = ListDataDefinition::createFromDataType('sequence')
        ->setTypedDataManager($this->typedDataManager)
        ->setItemDefinition($data_definition);
    }
    return $data_definition;
  }

}

<?php

namespace Drupal\jsonapi_hypermedia\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityPublishedInterfaceLinkProviderDeriver.
 *
 * @internal
 */
class EntityPublishedInterfaceLinkProviderDeriver extends DeriverBase implements ContainerDeriverInterface {

  /**
   * The JSON:API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityPublishedLinkProvider constructor.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The JSON:API resource type repository.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ResourceTypeRepositoryInterface $resource_type_repository, EntityTypeManagerInterface $entity_type_manager) {
    $this->resourceTypeRepository = $resource_type_repository;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $container->get('jsonapi.resource_type.repository'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $entity_types = $this->entityTypeManager->getDefinitions();
    $resource_types = array_filter($this->resourceTypeRepository->all(), function (ResourceType $resource_type) use ($entity_types) {
      $entity_type_id = $resource_type->getEntityTypeId();
      return $resource_type->isLocatable()
        && $resource_type->isMutable()
        && isset($entity_types[$entity_type_id])
        && $entity_types[$entity_type_id]->entityClassImplements(EntityPublishedInterface::class);
    });
    $derivative_definitions = array_reduce($resource_types, function ($derivative_definitions, ResourceType $resource_type) use ($base_plugin_definition, $entity_types) {
      foreach (['publish', 'unpublish'] as $operation) {
        $derivative_id = "{$resource_type->getTypeName()}.$operation";
        $derivative_definitions[$derivative_id] = array_merge($base_plugin_definition, [
          'link_key' => $operation,
          'link_relation_type' => 'update',
          'link_context' => [
            'resource_object' => $resource_type->getTypeName(),
          ],
          'default_configuration' => [
            'status_field_name' => $entity_types[$resource_type->getEntityTypeId()]->getKey('published'),
          ],
        ]);
      }
      return $derivative_definitions;
    }, []);
    return $derivative_definitions;
  }

}

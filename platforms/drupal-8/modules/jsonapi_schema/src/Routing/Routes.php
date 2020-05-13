<?php

namespace Drupal\jsonapi_schema\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi\Routing\Routes as JsonApiRoutes;
use Drupal\jsonapi_schema\Controller\JsonApiSchemaController;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

class Routes implements ContainerInjectionInterface {

  const CONTROLLER_NAME = JsonApiSchemaController::class;

  const RESOURCE_TYPE_PARAMETER_KEY = 'resource_type';

  const ROUTE_TYPE_PARAMETER_KEY = 'route_type';

  /**
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  protected $jsonApiRoutes;

  protected $jsonApiBasePath;

  protected $entityFieldManager;

  public function __construct(ResourceTypeRepositoryInterface $resource_type_repository, EntityFieldManagerInterface $entity_field_manager, JsonApiRoutes $jsonapi_routes, $jsonapi_base_path) {
    $this->resourceTypeRepository = $resource_type_repository;
    $this->entityFieldManager = $entity_field_manager;
    $this->jsonApiRoutes = $jsonapi_routes;
    $this->jsonApiBasePath = $jsonapi_base_path;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jsonapi.resource_type.repository'),
      $container->get('entity_field.manager'),
      JsonApiRoutes::create($container),
      $container->getParameter('jsonapi.base_path')
    );
  }

  public function routes() {
    $jsonapi_schema_routes = new RouteCollection();
    $entrypoint_schema_route = new Route($this->jsonApiBasePath . '/schema');
    $entrypoint_schema_route->addDefaults([
      RouteObjectInterface::CONTROLLER_NAME => static::CONTROLLER_NAME . '::getEntrypointSchema',
    ]);
    $jsonapi_schema_routes->add("jsonapi_schema.entrypoint", $entrypoint_schema_route);
    foreach ($this->resourceTypeRepository->all() as $resource_type) {
      if ($resource_type->isInternal()) {
        continue;
      }
      $resource_type_name = $resource_type->getTypeName();
      $base_path = $this->jsonApiBasePath . $resource_type->getPath();
      $individual_document_schema_route = new Route($base_path . '/schema');
      $individual_document_schema_route->addDefaults([
        RouteObjectInterface::CONTROLLER_NAME => static::CONTROLLER_NAME . '::getDocumentSchema',
        static::ROUTE_TYPE_PARAMETER_KEY => 'item',
        static::RESOURCE_TYPE_PARAMETER_KEY => $resource_type_name,
      ]);
      $collection_document_schema_route = new Route($base_path . '/collection/schema');
      $collection_document_schema_route->addDefaults([
        RouteObjectInterface::CONTROLLER_NAME => static::CONTROLLER_NAME . '::getDocumentSchema',
        static::ROUTE_TYPE_PARAMETER_KEY => 'collection',
        static::RESOURCE_TYPE_PARAMETER_KEY => $resource_type_name,
      ]);
      $resource_object_schema_route = new Route($base_path . '/resource/schema');
      $resource_object_schema_route->addDefaults([
        RouteObjectInterface::CONTROLLER_NAME => static::CONTROLLER_NAME . '::getResourceObjectSchema',
        static::ROUTE_TYPE_PARAMETER_KEY => 'type',
        static::RESOURCE_TYPE_PARAMETER_KEY => $resource_type_name,
      ]);
      if ($resource_type->isLocatable()) {
        $jsonapi_schema_routes->add("jsonapi_schema.$resource_type_name.collection", $collection_document_schema_route);
      }
      $jsonapi_schema_routes->add("jsonapi_schema.$resource_type_name.item", $individual_document_schema_route);
      $jsonapi_schema_routes->add("jsonapi_schema.$resource_type_name.type", $resource_object_schema_route);
      foreach ($resource_type->getRelatableResourceTypes() as $public_field_name => $target_resource_types) {
        if ($resource_type->isInternal() || !Routes::hasNonInternalTargetResourceTypes($target_resource_types)) {
          continue;
        }
        $is_to_one_relationship = $resource_type->getFieldByPublicName($public_field_name)->hasOne();
        $public_target_resource_types = array_filter($target_resource_types, function (ResourceType $resource_type) {
          return !$resource_type->isInternal();
        });
        $public_target_resource_type_names = array_map(function (ResourceType $resource_type) {
          return $resource_type->getTypeName();
        }, $public_target_resource_types);
        $related_document_schema_route = new Route($base_path . "/resource/relationships/$public_field_name/related/schema");
        $related_document_schema_route->addDefaults([
          RouteObjectInterface::CONTROLLER_NAME => static::CONTROLLER_NAME . '::getDocumentSchema',
          static::ROUTE_TYPE_PARAMETER_KEY => $is_to_one_relationship ? 'item' : 'collection',
          static::RESOURCE_TYPE_PARAMETER_KEY => count($public_target_resource_type_names) > 1
            ? $public_target_resource_type_names
            : reset($public_target_resource_type_names),
        ]);
        $jsonapi_schema_routes->add("jsonapi_schema.$resource_type_name.$public_field_name.related", $related_document_schema_route);
        //$relationship_document_schema_route = new Route($base_path . "/resource/relationships/$public_field_name/schema");
      }
    }
    $jsonapi_schema_routes->addRequirements(['_access' => 'TRUE']);
    return $jsonapi_schema_routes;
  }

  /**
   * Determines if an array of resource types has any non-internal ones.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType[] $resource_types
   *   The resource types to check.
   *
   * @return bool
   *   TRUE if there is at least one non-internal resource type in the given
   *   array; FALSE otherwise.
   */
  public static function hasNonInternalTargetResourceTypes(array $resource_types) {
    return array_reduce($resource_types, function ($carry, ResourceType $target) {
      return $carry || !$target->isInternal();
    }, FALSE);
  }

}

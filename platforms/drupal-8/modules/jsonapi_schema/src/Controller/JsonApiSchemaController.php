<?php

namespace Drupal\jsonapi_schema\Controller;

use Drupal\Component\Utility\NestedArray;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Url;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\ResourceType\ResourceTypeAttribute;
use Drupal\jsonapi\ResourceType\ResourceTypeField;
use Drupal\jsonapi\ResourceType\ResourceTypeRelationship;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Drupal\jsonapi_schema\Routing\Routes;
use Drupal\jsonapi_schema\StaticDataDefinitionExtractor;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class JsonApiSchemaController extends ControllerBase {

  const JSON_SCHEMA_DRAFT = 'https://json-schema.org/draft/2019-09/hyper-schema';

  const JSONAPI_BASE_SCHEMA_URI = 'https://jsonapi.org/schema';

  /**
   * The JSON:API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * The serialization service.
   *
   * @var \Symfony\Component\Serializer\Normalizer\NormalizerInterface
   */
  protected $normalizer;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The static data definition extractor.
   *
   * @var \Drupal\jsonapi_schema\StaticDataDefinitionExtractor
   */
  protected $staticDataDefinitionExtractor;

  /**
   * JsonApiSchemaController constructor.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The JSON:API resource type repository.
   * @param \Symfony\Component\Serializer\Normalizer\NormalizerInterface $normalizer
   *   The serializer.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(ResourceTypeRepositoryInterface $resource_type_repository, NormalizerInterface $normalizer, EntityTypeManagerInterface $entity_type_manager, StaticDataDefinitionExtractor $static_data_definition_extractor) {
    $this->resourceTypeRepository = $resource_type_repository;
    $this->normalizer = $normalizer;
    $this->entityTypeManager = $entity_type_manager;
    $this->staticDataDefinitionExtractor = $static_data_definition_extractor;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jsonapi.resource_type.repository'),
      $container->get('serializer'),
      $container->get('entity_type.manager'),
      $container->get('jsonapi_schema.static_data_definition_extractor')
    );
  }

  public function getEntrypointSchema(Request $request) {
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheTags(['jsonapi_resource_types']);
    $collection_links = array_values(array_map(function (ResourceType $resource_type) use ($cacheability) {
      $schema_url = Url::fromRoute("jsonapi_schema.{$resource_type->getTypeName()}.collection")->setAbsolute()->toString(TRUE);
      $cacheability->addCacheableDependency($schema_url);
      return [
        'href' => '{instanceHref}',
        'rel' => 'related',
        'title' => $this->getSchemaTitle($resource_type, 'collection'),
        'targetMediaType' => 'application/vnd.api+json',
        'targetSchema' => $schema_url->getGeneratedUrl(),
        'templatePointers' => [
          'instanceHref' => "/links/{$resource_type->getTypeName()}/href",
        ],
        'templateRequired' => ['instanceHref'],
      ];
    }, array_filter($this->resourceTypeRepository->all(), function (ResourceType $resource_type) {
      return !$resource_type->isInternal() && $resource_type->isLocatable();
    })));
    $schema = [
      '$schema' => static::JSON_SCHEMA_DRAFT,
      '$id' => $request->getUri(),
      'allOf' =>  [
        [
          '$ref' => static::JSONAPI_BASE_SCHEMA_URI . '#/definitions/success',
        ],
        [
          'type' => 'object',
          'links' => $collection_links,
        ],
      ],
    ];
    return CacheableJsonResponse::create($schema)->addCacheableDependency($cacheability);
  }

  public function getDocumentSchema(Request $request, $resource_type, $route_type) {
    if (is_array($resource_type)) {
      $titles = array_map(function (ResourceType $type) use ($route_type) {
        return $this->getSchemaTitle($this->resourceTypeRepository->getByTypeName($type), $route_type);
      }, $resource_type);
      $title = count($titles) === 2
        ? implode(' and ', $titles)
        : implode(', ', array_slice($titles, -1)) . ', and ' . end($titles);
    }
    else {
      $title = $this->getSchemaTitle($this->resourceTypeRepository->getByTypeName($resource_type), $route_type);
    }
    $schema = [
      '$schema' => static::JSON_SCHEMA_DRAFT,
      '$id' => $request->getUri(),
      'title' => $title,
      'allOf' =>  [
        [
          '$ref' => static::JSONAPI_BASE_SCHEMA_URI,
        ],
        [
          'if' => [
            '$ref' => static::JSONAPI_BASE_SCHEMA_URI . '#/definitions/success',
          ],
          'then' => [
            'type' => 'object',
            'properties' => [
              'data' => [
                '$ref' => '#/definitions/data',
              ],
            ],
            'required' => ['data'],
          ],
        ],
      ],
    ];
    $cacheability = new CacheableMetadata();
    $get_schema_ref = function ($resource_type) use ($cacheability) {
      $schema_url = Url::fromRoute("jsonapi_schema.$resource_type.type")->setAbsolute()->toString(TRUE);
      $cacheability->addCacheableDependency($schema_url);
      return ['$ref' => $schema_url->getGeneratedUrl()];
    };
    $type_schema = is_array($resource_type)
      ? ['anyOf' => array_map($get_schema_ref, $resource_type)]
      : $get_schema_ref($resource_type);
    switch ($route_type) {
      case 'item':
        $schema['definitions']['data'] = $type_schema;
        break;
      case 'collection':
        $schema['definitions']['data'] = [
          'type' => 'array',
          'items' => $type_schema,
        ];
        break;
      case 'relationship':
        assert('not implemented');
        break;
    }
    return CacheableJsonResponse::create($schema)->addCacheableDependency($cacheability);
  }

  public function getResourceObjectSchema(Request $request, $resource_type) {
    $resource_type = $this->resourceTypeRepository->getByTypeName($resource_type);
    $schema = [
      '$schema' => static::JSON_SCHEMA_DRAFT,
      '$id' => $request->getUri(),
      'title' => $this->getSchemaTitle($resource_type, 'item'),
      'allOf' => [
        [
          'type' => 'object',
          'properties' => [
            'type' => ['$ref' => '#definitions/type'],
          ],
        ],
        [
          '$ref' => static::JSONAPI_BASE_SCHEMA_URI . '#/definitions/resource',
        ]
      ],
      'definitions' => [
        'type'  => ['const' => $resource_type->getTypeName()],
      ],
    ];
    $cacheability = new CacheableMetadata();
    $schema = $this->addFieldsSchema($schema, $resource_type);
    $schema = $this->addRelationshipsSchemaLinks($schema, $resource_type, $cacheability);
    return CacheableJsonResponse::create($schema)->addCacheableDependency($cacheability);
  }

  protected function addFieldsSchema(array $schema, ResourceType $resource_type) {
    // Filter out disabled fields.
    $resource_fields = array_filter($resource_type->getFields(), function (ResourceTypeField $field) {
      return $field->isFieldEnabled();
    });
    if (empty($resource_fields)) {
      return $schema;
    }
    $schema['allOf'][0]['properties']['attributes'] = [
      '$ref' => '#/definitions/attributes',
    ];
    $normalizer = $this->normalizer;
    $entity_type = $this->entityTypeManager->getDefinition($resource_type->getEntityTypeId());
    $bundle = $resource_type->getBundle();
    $fields = array_reduce($resource_fields, function ($carry, ResourceTypeField $field) use ($normalizer, $entity_type, $bundle) {
      $field_schema = $normalizer->normalize(
        $this->staticDataDefinitionExtractor->extractField($entity_type, $bundle, $field->getInternalName()),
        'schema_json',
        ['name' => $field->getPublicName()]
      );
      $fields_member = $field instanceof ResourceTypeAttribute ? 'attributes' : 'relationships';
      return NestedArray::mergeDeep($carry, [
        'type' => 'object',
        'properties' => [
          $fields_member => $field_schema,
        ],
      ]);
    }, []);
    $field_definitions = NestedArray::getValue($fields, ['properties']) ?: [];
    if (!empty($field_definitions['attributes'])) {
      $field_definitions['attributes']['additionalProperties'] = FALSE;
    }
    if (!empty($field_definitions['relationships'])) {
      $field_definitions['relationships']['additionalProperties'] = FALSE;
    }
    $schema['definitions'] = NestedArray::mergeDeep($schema['definitions'], $field_definitions);
    return $schema;
  }

  protected static function addRelationshipsSchemaLinks(array $schema, ResourceType $resource_type, CacheableMetadata $cacheability) {
    $resource_relationships = array_filter($resource_type->getFields(), function (ResourceTypeField $field) {
      return $field->isFieldEnabled() && $field instanceof ResourceTypeRelationship;
    });
    if (empty($resource_relationships)) {
      return $schema;
    }
    $schema['allOf'][0]['properties']['relationships'] = [
      '$ref' => '#/definitions/relationships',
    ];
    $relationships = array_reduce($resource_relationships, function ($relationships, ResourceTypeRelationship $relationship) use ($resource_type, $cacheability) {
      if ($resource_type->isInternal() || !Routes::hasNonInternalTargetResourceTypes($relationship->getRelatableResourceTypes())) {
        return $relationships;
      }
      $field_name = $relationship->getPublicName();
      $resource_type_name = $resource_type->getTypeName();
      $related_route_name = "jsonapi_schema.{$resource_type_name}.$field_name.related";
      $related_schema_uri = Url::fromRoute($related_route_name)->setAbsolute()->toString(TRUE);
      $cacheability->addCacheableDependency($related_schema_uri);
      return NestedArray::mergeDeep($relationships, [
        $field_name => [
          'links' => [
            [
              'href' => '{instanceHref}',
              'rel' => 'related',
              'targetMediaType' => 'application/vnd.api+json',
              'targetSchema' => $related_schema_uri->getGeneratedUrl(),
              'templatePointers' => [
                'instanceHref' => '/links/related/href',
              ],
              'templateRequired' => ['instanceHref'],
            ],
          ],
        ],
      ]);
    }, []);
    $schema['definitions']['relationships'] = NestedArray::mergeDeep(
      empty($schema['definitions']['relationships']) ? [] : $schema['definitions']['relationships'],
      ['properties' => $relationships]
    );
    return $schema;
  }

  /**
   * Gets a schema title.
   *
   * @param \Drupal\jsonapi\ResourceType\ResourceType $resource_type
   *   A JSON:API resource type for which to generate a title.
   * @param $schema_type
   *   The type of schema. Either 'collection' or 'item'.
   *
   * @return \Drupal\Core\StringTranslation\TranslatableMarkup
   *   The schema title.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  protected function getSchemaTitle(ResourceType $resource_type, $schema_type) {
    $entity_type = $this->entityTypeManager->getDefinition($resource_type->getEntityTypeId());
    $entity_type_label = $schema_type === 'collection' ? $entity_type->getPluralLabel() : $entity_type->getSingularLabel();
    if ($bundle_type = $entity_type->getBundleEntityType()) {
      $bundle = $this->entityTypeManager->getStorage($bundle_type)->load($resource_type->getBundle());
      return $this->t(rtrim('@bundle_label @entity_type_label'), [
        '@bundle_label' => Unicode::ucfirst($bundle->label()),
        '@entity_type_label' => $entity_type_label,
      ]);
    }
    else {
      return $this->t(rtrim('@entity_type_label'), [
        '@entity_type_label' => Unicode::ucfirst($entity_type_label),
      ]);
    }
  }

}

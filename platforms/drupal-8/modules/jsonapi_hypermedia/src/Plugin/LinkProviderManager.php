<?php

namespace Drupal\jsonapi_hypermedia\Plugin;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Http\LinkRelationTypeInterface;
use Drupal\Core\Http\LinkRelationTypeManager;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\jsonapi\JsonApiResource\ErrorCollection;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\JsonApiResource\Relationship;
use Drupal\jsonapi\JsonApiResource\RelationshipData;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi\JsonApiResource\ResourceObjectData;
use Drupal\jsonapi\Routing\Routes;
use Drupal\jsonapi_hypermedia\AccessRestrictedLink;
use Drupal\jsonapi_hypermedia\Annotation\JsonapiHypermediaLinkProvider;
use Drupal\jsonapi_hypermedia\LinkProviderInterface;

/**
 * Manages discovery and instantiation of resourceFieldEnhancer plugins.
 *
 * @internal
 */
final class LinkProviderManager extends DefaultPluginManager implements LinkProviderManagerInterface {

  /**
   * A map of plugin definition context types to class and interface names.
   *
   * @var array
   */
  protected static $contextTypes = [
    'top_level_object' => JsonApiDocumentTopLevel::class,
    'resource_object' => ResourceObject::class,
    'relationship_object' => Relationship::class,
  ];

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * The link relation type manager.
   *
   * @var \Drupal\Core\Http\LinkRelationTypeManager
   */
  protected $linkRelationTypeManager;

  /**
   * Constructs a new HypermediaProviderManager.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   */
  public function __construct(\Traversable $namespaces, CacheBackendInterface $cache_backend, ModuleHandlerInterface $module_handler) {
    parent::__construct(
      'Plugin/jsonapi_hypermedia/LinkProvider',
      $namespaces,
      $module_handler,
      LinkProviderInterface::class,
      JsonapiHypermediaLinkProvider::class
    );
    $this->alterInfo('jsonapi_hypermedia_provider_info');
    $this->setCacheBackend($cache_backend, 'jsonapi_hypermedia_provider_plugins');
  }

  /**
   * Set the current route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   */
  public function setCurrentRouteMatch(RouteMatchInterface $route_match) {
    $this->currentRouteMatch = $route_match;
  }

  /**
   * Set the link relation type manager.
   *
   * @param \Drupal\Core\Http\LinkRelationTypeManager $link_relation_type_manager
   *   The link relation type manager.
   */
  public function setLinkRelationTypeManager(LinkRelationTypeManager $link_relation_type_manager) {
    $this->linkRelationTypeManager = $link_relation_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkCollection($context) {
    $definitions = $this->getApplicableDefinitions($context);
    $providers = array_map(function ($plugin_id) use ($definitions) {
      return $this->createInstance($plugin_id, $definitions[$plugin_id]['default_configuration'] ?? []);
    }, array_keys($definitions));
    $cacheability = NULL;
    $link_collection = array_reduce($providers, function (LinkCollection $link_collection, LinkProviderInterface $provider) use ($context, &$cacheability) {
      $link = $this->ensureAccess($cacheability, $provider->getLink($context));
      return $link ? $link_collection->withLink($provider->getLinkKey(), $this->getValidatedLink($link)) : $link_collection;
    }, new LinkCollection([]));
    $this->bubbleAccessCacheability($cacheability);
    return $link_collection->withContext($context);
  }

  /**
   * Ensures that access cacheability is captured.
   *
   * @param \Drupal\Core\Cache\CacheableMetadata|null $cacheability
   *   The access related cacheability to be captured or NULL if there is none.
   * @param \Drupal\jsonapi_hypermedia\AccessRestrictedLink $link
   *   The link for which to ensure access cacheability is captured.
   *
   * @return \Drupal\jsonapi\JsonApiResource\Link|null
   *   A JSON:API link or NULL if the given link is not accessible.
   */
  protected function ensureAccess(&$cacheability, AccessRestrictedLink $link) {
    if (!$cacheability) {
      $cacheability = new CacheableMetadata();
    }
    $cacheability->addCacheableDependency($link);
    if (!$link->isAllowed()) {
      return NULL;
    }
    return $link->getInnerLink();
  }

  /**
   * Gets a new, validated link.
   *
   * @param \Drupal\jsonapi\JsonApiResource\Link $link
   *   The link to validate.
   *
   * @return \Drupal\jsonapi\JsonApiResource\Link
   *   A new, validated link.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   *   Thrown if a link relation type plugin is improperly defined.
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Thrown if a link relation type plugin could not be found.
   */
  protected function getValidatedLink(Link $link) {
    $link_relation_type = $link->getLinkRelationType();
    $link_relation = $this->linkRelationTypeManager->createInstance($link_relation_type);
    assert($link_relation instanceof LinkRelationTypeInterface);
    $link_relation_type_name = $link_relation->isExtension() ? $link_relation->getExtensionUri() : $link_relation->getRegisteredName();
    if (!$link_relation_type_name) {
      throw new InvalidPluginDefinitionException($link_relation_type);
    }
    return new Link(CacheableMetadata::createFromObject($link), $link->getUri(), $link_relation_type_name, $link->getTargetAttributes());
  }

  /**
   * Bubbles access-related cacheability of the link.
   *
   * @param \Drupal\Core\Cache\CacheableMetadata|null $cacheability
   *   The access related cacheability to be captured or NULL if there is none.
   *
   * @todo: removes this once https://www.drupal.org/project/drupal/issues/3055889 lands.
   */
  protected function bubbleAccessCacheability($cacheability) {
    assert(is_null($cacheability) || $cacheability instanceof CacheableMetadata);
    if (is_null($cacheability)) {
      return;
    }
    $request = \Drupal::requestStack()->getCurrentRequest();
    $renderer = \Drupal::service('renderer');
    if ($request->isMethodCacheable() && $renderer->hasRenderContext()) {
      $build = [];
      $cacheability->applyTo($build);
      $renderer->render($build);
    }
  }

  /**
   * Gets the context type.
   *
   * @param \Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel|\Drupal\jsonapi\JsonApiResource\ResourceObject|\Drupal\jsonapi\JsonApiResource\Relationship $context
   *   The context object from which links should be generated.
   *
   * @return string
   *   The context type.
   */
  protected static function getContextType($context) {
    foreach (static::$contextTypes as $type => $class) {
      if ($context instanceof $class) {
        $context_type = $type;
      }
    }
    assert(isset($context_type));
    return $context_type;
  }

  /**
   * Gets the link provider definitions applicable to the given context object.
   *
   * @param \Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel|\Drupal\jsonapi\JsonApiResource\ResourceObject $context
   *   The link context object.
   *
   * @return array
   *   An array of the application provider definitions.
   *
   * @see \Drupal\Component\Plugin\PluginManagerInterface::getDefinitions()
   */
  protected function getApplicableDefinitions($context) {
    $context_type = static::getContextType($context);
    $definitions = $this->getDefinitions();
    return array_filter($definitions, function ($plugin_definition) use ($context_type, $context) {
      if (!empty($plugin_definition['link_context']['relationship_object'])) {
        $plugin_definition['link_context']['top_level_object'] = 'relationship_object';
      }
      if (empty($plugin_definition['link_context'][$context_type])) {
        return FALSE;
      }
      if ($plugin_definition['link_context'][$context_type] === TRUE) {
        return TRUE;
      }
      switch ($context_type) {
        case 'top_level_object':
          assert($context instanceof JsonApiDocumentTopLevel);
          $data = $context->getData();
          $is_error_document = $data instanceof ErrorCollection;
          $is_entrypoint = $this->currentRouteMatch->getRouteName() === 'jsonapi.resource_list';
          $is_relationship_document = $data instanceof RelationshipData;
          switch ($plugin_definition['link_context'][$context_type]) {
            case 'entrypoint':
              return !$is_error_document && $is_entrypoint;

            case 'success':
              return !$is_error_document;

            case 'error':
              return $is_error_document;

            case 'individual':
              return !$is_error_document && !$is_entrypoint && $data instanceof ResourceObjectData && $data->getCardinality() === 1;

            case 'collection':
              return !$is_error_document && !$is_entrypoint && $data instanceof ResourceObjectData && $data->getCardinality() !== 1;

            case 'relationship':
              return !$is_error_document && !$is_entrypoint && $is_relationship_document;

            case 'relationship_object':
              if ($is_error_document || $is_entrypoint || !$is_relationship_document) {
                return FALSE;
              }
              if (is_bool($plugin_definition['link_context']['relationship_object'])) {
                return $plugin_definition['link_context']['relationship_object'];
              }
              $route = $this->currentRouteMatch->getRouteObject();
              list($resource_type_name, $relationship_field_name) = $plugin_definition['link_context']['relationship_object'];
              return $route->getDefault(Routes::RESOURCE_TYPE_KEY) === $resource_type_name && $route->getDefault('related') === $relationship_field_name;

            default:
              return FALSE;
          }

        case 'resource_object':
          assert($context instanceof ResourceObject);
          return $context->getResourceType()->getTypeName() === $plugin_definition['link_context'][$context_type];

        case 'relationship_object':
          assert($context instanceof Relationship);
          list($resource_type_name, $relationship_field_name) = $plugin_definition['link_context'][$context_type];
          return $context->getContext()->getResourceType()->getTypeName() === $resource_type_name && $context->getFieldName() === $relationship_field_name;

        default:
          return FALSE;
      }
    });
  }

}

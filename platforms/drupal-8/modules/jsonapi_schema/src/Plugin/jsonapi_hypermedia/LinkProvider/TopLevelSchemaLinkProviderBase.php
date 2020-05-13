<?php

namespace Drupal\jsonapi_schema\Plugin\jsonapi_hypermedia\LinkProvider;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Url;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi\ResourceType\ResourceType;
use Drupal\jsonapi\Routing\Routes;
use Drupal\jsonapi_hypermedia\AccessRestrictedLink;
use Drupal\jsonapi_hypermedia\Plugin\LinkProviderBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;

/**
 * Class TopLevelSchemaLinkProviderBase.
 *
 * @internal
 */
abstract class TopLevelSchemaLinkProviderBase extends LinkProviderBase implements ContainerFactoryPluginInterface {

  protected static $schemaRouteType = NULL;

  /**
   * The current route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $currentRouteMatch;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    assert(in_array(static::$schemaRouteType, ['item', 'collection', 'entrypoint'], TRUE));
    $provider = new static($configuration, $plugin_id, $plugin_definition);
    $provider->setCurrentRouteMatch($container->get('current_route_match'));
    return $provider;
  }

  /**
   * Sets the current route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   */
  public function setCurrentRouteMatch(RouteMatchInterface $route_match) {
    $this->currentRouteMatch = $route_match;
  }

  /**
   * {@inheritdoc}
   */
  public function getLink($context) {
    assert($context instanceof JsonApiDocumentTopLevel);
    if (static::$schemaRouteType === 'entrypoint') {
      $schema_route_name = "jsonapi_schema." . static::$schemaRouteType;
    }
    else {
      $route = $this->currentRouteMatch->getRouteObject();
      assert($route instanceof Route);
      $resource_type = $route->getDefault(Routes::RESOURCE_TYPE_KEY);
      if (!$resource_type instanceof ResourceType) {
        return AccessRestrictedLink::createInaccessibleLink(new CacheableMetadata());
      }
      $schema_route_name = "jsonapi_schema.{$resource_type->getTypeName()}." . static::$schemaRouteType;
    }
    return AccessRestrictedLink::createLink(AccessResult::allowed(), new CacheableMetadata(), new Url($schema_route_name), $this->getLinkRelationType());
  }

}


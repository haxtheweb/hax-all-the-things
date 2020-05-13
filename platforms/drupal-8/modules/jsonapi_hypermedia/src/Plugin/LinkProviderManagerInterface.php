<?php

namespace Drupal\jsonapi_hypermedia\Plugin;

use Drupal\Component\Plugin\Discovery\CachedDiscoveryInterface;
use Drupal\Component\Plugin\PluginManagerInterface;

/**
 * Interface LinkProviderManagerInterface.
 *
 * @see \Drupal\jsonapi_hypermedia\Annotation\JsonapiHypermediaLinkProvider
 * @see \Drupal\jsonapi_hypermedia\Plugin\LinkProviderManager
 * @see \Drupal\jsonapi_hypermedia\LinkProviderInterface
 *
 * @internal
 */
interface LinkProviderManagerInterface extends PluginManagerInterface, CachedDiscoveryInterface {

  /**
   * Gets a LinkCollection of 3rd-party links for the given context object.
   *
   * @param \Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel|\Drupal\jsonapi\JsonApiResource\ResourceObject|\Drupal\jsonapi\JsonApiResource\Relationship $context
   *   The link context object.
   *
   * @return \Drupal\jsonapi\JsonApiResource\LinkCollection
   *   The link collection.
   */
  public function getLinkCollection($context);

}

<?php

namespace Drupal\jsonapi_schema\Plugin\jsonapi_hypermedia\LinkProvider;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi_hypermedia\AccessRestrictedLink;
use Drupal\jsonapi_hypermedia\Annotation\JsonapiHypermediaLinkProvider;
use Drupal\jsonapi_hypermedia\Plugin\LinkProviderBase;

/**
 * Class ResourceObjectSchemaLinkProvider.
 *
 * @JsonapiHypermediaLinkProvider(
 *   id = "jsonapi_shema.resource_object",
 *   link_relation_type = "describedby",
 *   link_context = {
 *     "resource_object" = true,
 *   },
 * )
 *
 * @internal
 */
final class ResourceObjectSchemaLinkProvider extends LinkProviderBase {

  /**
   * {@inheritdoc}
   */
  public function getLink($context) {
    assert($context instanceof ResourceObject);
    $resource_type_name = $context->getResourceType()->getTypeName();
    $resource_schema_uri = Url::fromRoute("jsonapi_schema.$resource_type_name.type");
    return AccessRestrictedLink::createLink(AccessResult::allowed(), new CacheableMetadata(), $resource_schema_uri, $this->getLinkRelationType());
  }

}

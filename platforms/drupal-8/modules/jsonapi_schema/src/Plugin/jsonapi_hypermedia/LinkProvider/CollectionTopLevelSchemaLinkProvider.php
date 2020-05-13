<?php

namespace Drupal\jsonapi_schema\Plugin\jsonapi_hypermedia\LinkProvider;

use Drupal\jsonapi_hypermedia\Annotation\JsonapiHypermediaLinkProvider;

/**
* Class CollectionTopLevelSchemaLinkProvider.
*
* @JsonapiHypermediaLinkProvider(
*   id = "jsonapi_shema.top_level.collection",
*   link_relation_type = "describedby",
*   link_context = {
*     "top_level_object" = "collection",
*   },
* )
*
* @internal
*/
final class CollectionTopLevelSchemaLinkProvider extends TopLevelSchemaLinkProviderBase {

  /**
   * {@inheritdoc}
   */
  protected static $schemaRouteType = 'collection';

}

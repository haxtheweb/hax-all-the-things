<?php

namespace Drupal\jsonapi_schema\Plugin\jsonapi_hypermedia\LinkProvider;

use Drupal\jsonapi_hypermedia\Annotation\JsonapiHypermediaLinkProvider;

/**
* Class CollectionTopLevelSchemaLinkProvider.
*
* @JsonapiHypermediaLinkProvider(
*   id = "jsonapi_shema.top_level.entrypoint",
*   link_relation_type = "describedby",
*   link_context = {
*     "top_level_object" = "entrypoint",
*   },
* )
*
* @internal
*/
final class EntrypointSchemaLinkProvider extends TopLevelSchemaLinkProviderBase {

  /**
   * {@inheritdoc}
   */
  protected static $schemaRouteType = 'entrypoint';

}

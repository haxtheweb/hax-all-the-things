<?php

namespace Drupal\jsonapi_schema\Plugin\jsonapi_hypermedia\LinkProvider;

use Drupal\jsonapi_hypermedia\Annotation\JsonapiHypermediaLinkProvider;

/**
* Class IndividualTopLevelSchemaLinkProvider.
*
* @JsonapiHypermediaLinkProvider(
*   id = "jsonapi_shema.top_level.individual",
*   link_relation_type = "describedby",
*   link_context = {
*     "top_level_object" = "individual",
*   },
* )
*
* @internal
*/
final class IndividualTopLevelSchemaLinkProvider extends TopLevelSchemaLinkProviderBase {

  /**
   * {@inheritdoc}
   */
  protected static $schemaRouteType = 'item';

}

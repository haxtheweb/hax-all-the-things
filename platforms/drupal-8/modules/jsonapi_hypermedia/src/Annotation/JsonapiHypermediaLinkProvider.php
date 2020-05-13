<?php

namespace Drupal\jsonapi_hypermedia\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines a JsonapiHypermediaProvider annotation.
 *
 * JSON:API Hypermedia plugin implementations need to define a plugin definition
 * array through annotation. These definition arrays may be altered through
 * hook_jsonapi_hypermedia_provider_info_alter().
 *
 * Plugin Namespace: \Plugin\jsonapi_hypermedia\LinkProvider
 *
 * Each plugin definition includes the following keys:
 *
 * - link_relation_type: The link relation type of the link that will be
 *   provided by the plugin.
 * - link_key: The key of the link object that will be provided by the plugin.
 *   This can be omitted if the key should be the same as the link relation type
 *   name or if the plugin class provides its own implementation of
 *   \Drupal\jsonapi_hypermedia\LinkProviderInterface::getLinkKey().
 * - link_context: The context or contexts in which a link provider should be
 *   executed. The context indicates a type of object JSON:API document, like
 *   an error top-level object, a resource object of a particular type, or a
 *   named relationship object.
 * - default_configuration: An array of configuration values that can be used to
 *   parameterize the link provider plugin's behavior.
 *
 * A valid @code link_context @endcode value is an array, with one of the
 * following keys:
 *
 * - top_level_object: the context for the link to be provided is a top-level
 *   links object.
 * - resource_object: the context for the link to be provided is a resource
 *   object's links object.
 * - relationship_object: the context for the link to be provided is a
 *   relationship object's links object.
 *
 * Each context object key has an allowed value, which further limits the
 * locations in which the plugin can provide a link.
 *
 * - top_level_object: true|string
 *    TRUE if the link applies to every top-level links object or a type of
 *    top-level object. Possible types are:
 *      - entrypoint
 *      - success
 *      - error
 *      - individual
 *      - collection
 *      - relationship
 *
 * - resource_object: true|string
 *    TRUE if the link applies to every resource object or a resource type
 *    name if the link only applies to a specific type of resource object.
 *
 * - relationship_object: true|array
 *    TRUE if the link applies to every relationship object or a two-tuple of
 *    a resource type name and relationship field name. These links will also
 *    appear on the top-level links object of relationship responses (e.g.
 *    /node/article/{id}/relationships/uid).
 *
 * Examples:
 *
 * @code
 * @JsonapiHypermediaLinkProvider(
 *   link_relation_type = "item",
 *   link_context = {
 *     "top_level_object" = "collection",
 *   }
 * )
 *
 * @JsonapiHypermediaLinkProvider(
 *   link_relation_type = "publish",
 *   link_context = {
 *     "resource_object" = "node--page",
 *   }
 * )
 *
 * @JsonapiHypermediaLinkProvider(
 *   link_key = "image",
 *   link_relation_type = "enclosure",
 *   link_context = {
 *     "relationship_object" = {
 *       "node--article", "uid",
 *     }
 *   },
 *   default_configuration = {
 *     "image_style" = "thumbnail",
 *   }
 * )
 * @endcode
 *
 * @see \Drupal\jsonapi_hypermedia\LinkProviderInterface
 * @see \Drupal\jsonapi_hypermedia\Plugin\LinkProviderManagerInterface
 * @see hook_jsonapi_hypermedia_provider_info_alter()
 * @see plugin_api
 *
 * @example ../../examples/Plugin/jsonapi_hypermedia/LinkProvider/AuthenticationLinkProvider.php
 * @example ../../examples/Plugin/jsonapi_hypermedia/LinkProvider/EntityPublishedInterfaceLinkProvider.php
 * @example ../../examples/Plugin/jsonapi_hypermedia/LinkProvider/MutableResourceTypeLinkProvider.php
 *
 * @Annotation
 */
final class JsonapiHypermediaLinkProvider extends Plugin {

  /**
   * The link relation type of the provided link.
   *
   * @var string
   */
  public $link_relation_type;

  /**
   * The link object key for the provided link.
   *
   * This will be the links object member name for the link returned by the
   * annotated plugin.
   *
   * @var string
   */
  public $link_key;

  /**
   * The context or contexts in which a link provider should be executed.
   *
   * @var array
   */
  public $link_context;

  /**
   * The default configuration for the plugin instance.
   *
   * @var array
   */
  public $default_configuration;

}

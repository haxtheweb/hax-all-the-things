<?php

namespace Drupal\jsonapi_hypermedia;

/**
 * Interface LinkProviderInterface.
 *
 * @see \Drupal\jsonapi_hypermedia\Plugin\LinkProviderBase;
 */
interface LinkProviderInterface {

  /**
   * Gets the link object key for the provided link.
   *
   * @return string
   *   A key for the provided link.
   */
  public function getLinkKey();

  /**
   * Gets the link relation type for the provided link.
   *
   * @return string
   *   A link relation type name for the provided link.
   */
  public function getLinkRelationType();

  /**
   * Adds, alters or removes hyperlinks from a link collection.
   *
   * @param \Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel|\Drupal\jsonapi\JsonApiResource\ResourceObject|\Drupal\jsonapi\JsonApiResource\Relationship $context
   *   The context object from which links should be generated.
   *
   * @return \Drupal\jsonapi_hypermedia\AccessRestrictedLink
   *   A link to be added to the context object. An AccessRestrictedLink
   *   should be returned if the link target may be inaccessible to some users.
   */
  public function getLink($context);

}

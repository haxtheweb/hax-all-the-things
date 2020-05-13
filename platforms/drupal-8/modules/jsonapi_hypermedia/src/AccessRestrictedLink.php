<?php

namespace Drupal\jsonapi_hypermedia;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Access\AccessResultInterface;
use Drupal\Core\Cache\CacheableDependencyInterface;
use Drupal\Core\Cache\CacheableDependencyTrait;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Url;
use Drupal\jsonapi\JsonApiResource\Link as DecoratedLink;

/**
 * Decorates a JSON:API link to consider link accessibility.
 *
 * @internal
 */
final class AccessRestrictedLink implements CacheableDependencyInterface {

  use CacheableDependencyTrait;

  /**
   * The access result for the link, if one was provided.
   *
   * @var \Drupal\Core\Access\AccessResultInterface|null
   */
  protected $accessResult;

  /**
   * The shimmed JSON:API link. NULL if the link is not accessible.
   *
   * @var \Drupal\jsonapi\JsonApiResource\Link
   */
  protected $inner;

  /**
   * AccessRestrictedLink constructor.
   *
   * @param \Drupal\Core\Access\AccessResultInterface $access_result
   *   An access result.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $link_cacheability
   *   (optional) Cacheability of the generated link.
   * @param \Drupal\Core\Url $target
   *   (optional) The link URL.
   * @param string $link_relation_type
   *   (optional) The link's relation type.
   * @param array $target_attributes
   *   (optional) The link's target attributes.
   * @param \Drupal\Core\Url|null $context
   *   (optional) The link's context. NULL if the default context shouldn't be
   *   overridden.
   */
  protected function __construct(AccessResultInterface $access_result, CacheableDependencyInterface $link_cacheability = NULL, Url $target = NULL, $link_relation_type = '', array $target_attributes = [], $context = NULL) {
    // Most arguments can be omitted if the link is inaccessible.
    assert(!$access_result->isAllowed() || ($link_cacheability && $target && !empty($link_relation_type)));
    assert(is_null($context) || $context instanceof Url);
    $this->setCacheability(CacheableMetadata::createFromObject($access_result));
    $this->accessResult = $access_result;
    if ($access_result->isAllowed()) {
      $cacheable_metadata = CacheableMetadata::createFromObject($link_cacheability);
      if ($context) {
        $anchor_href = $context->setAbsolute()->toString(TRUE);
        $cacheable_metadata->addCacheableDependency($anchor_href);
        $target_attributes['anchor'] = $anchor_href->getGeneratedUrl();
      }
      $this->inner = new DecoratedLink($cacheable_metadata, $target, $link_relation_type, $target_attributes);
    }
  }

  /**
   * Creates an AccessRestrictedLink.
   *
   * Note that in some cases, it is preferable to use
   * static::createInaccessibleLink() instead of this method. Using that method
   * instead of this one does not require as many arguments and therefore does
   * not require the caller to construct a phony URL.
   *
   * @param \Drupal\Core\Access\AccessResultInterface $access_result
   *   The link access result. This is typically an access result related
   *   to whether the current user is able to follow the link or not. Don't
   *   forget to add cacheability information to the result as necessary.
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $link_cacheability
   *   The cacheability of the generated link. This is typically cacheability
   *   related to the URL, the link relation types, or the link's target
   *   attributes. For example, a link might use the `hreflang` target
   *   attribute to indicate the available translations of a target resource and
   *   so the link's cacheability might have a cache context related to the
   *   current language as well as a cache tag that would be invalidated when a
   *   new translation is added.
   * @param \Drupal\Core\Url $url
   *   The link URL.
   * @param string $link_relation_type
   *   The link's relation type.
   * @param array $target_attributes
   *   (optional) The link's target attributes.
   * @param \Drupal\Core\Url $context
   *   (optional) The link's context. This will override the default link
   *   context via an `anchor` link param. The default context is derived from a
   *   link's location in the response document.
   *
   * @return static
   *   A new link object.
   *
   * @see \Drupal\jsonapi_hypermedia\AccessRestrictedLink::createInaccessibleLink()
   */
  public static function createLink(AccessResultInterface $access_result, CacheableDependencyInterface $link_cacheability, Url $url, $link_relation_type, array $target_attributes = [], Url $context = NULL) {
    return new static($access_result, $link_cacheability, $url, $link_relation_type, $target_attributes, $context);
  }

  /**
   * Creates an AccessRestricted link that will not be displayed.
   *
   * Use this when the link should *not* be present in the response. This is not
   * always about access control, but it can be. For example, take a link
   * provider that generates a "publish" link, it may be executed for a resource
   * object that is already published. In that case, following the link might
   * cause a client error. The link provider should return an inaccessible link
   * to prevent the client from making a bad request. In another case, the
   * current user might not have sufficient permissions to publish the resource
   * object; this is also a valid reason for creating an inaccessible link.
   *
   * @param \Drupal\Core\Cache\CacheableDependencyInterface $access_cacheability
   *   The link access cacheability. This is typically an access result related
   *   to whether the current user is able to follow the link or not.
   *
   * @return \Drupal\jsonapi_hypermedia\AccessRestrictedLink
   *   A new link object.
   */
  public static function createInaccessibleLink(CacheableDependencyInterface $access_cacheability) {
    return new static(AccessResult::forbidden()->addCacheableDependency($access_cacheability));
  }

  /**
   * Whether the link is allowed or not.
   *
   * @return bool
   *   TRUE if the link is accessible, FALSE otherwise.
   */
  public function isAllowed() {
    return $this->accessResult->isAllowed();
  }

  /**
   * Gets the bare link.
   *
   * This method should not be called unless the link is accessible.
   *
   * @return \Drupal\jsonapi\JsonApiResource\Link
   *   The JSON:API link.
   */
  public function getInnerLink() {
    if (!$this->isAllowed()) {
      throw new \LogicException('The link is not accessible.');
    }
    return $this->inner;
  }

}

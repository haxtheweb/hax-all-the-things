<?php

namespace Drupal\jsonapi\Normalizer\JsonapiHypermediaImpostor;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\Crypt;
use Drupal\Core\Render\RenderContext;
use Drupal\Core\Render\RendererInterface;
use Drupal\jsonapi\JsonApiResource\Link;
use Drupal\jsonapi\JsonApiResource\LinkCollection;
use Drupal\jsonapi\Normalizer\LinkCollectionNormalizer;
use Drupal\jsonapi\Normalizer\Value\CacheableNormalization;
use Drupal\jsonapi_hypermedia\Plugin\LinkProviderManagerInterface;

/**
 * Class JsonApiHypermediaLinkCollectionNormalizer.
 *
 * @internal
 */
final class JsonapiHypermediaLinkCollectionNormalizer extends LinkCollectionNormalizer {

  /**
   * The link provider plugin manager service.
   *
   * @var \Drupal\jsonapi_hypermedia\Plugin\LinkProviderManagerInterface
   */
  protected $linkProviderManager;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * Sets the link provider manager service.
   *
   * @param \Drupal\jsonapi_hypermedia\Plugin\LinkProviderManagerInterface $link_provider_manager
   *   The link provider manager.
   */
  public function setLinkProviderManager(LinkProviderManagerInterface $link_provider_manager) {
    $this->linkProviderManager = $link_provider_manager;
  }

  /**
   * Set the renderer.
   *
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function setRenderer(RendererInterface $renderer) {
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($link_collection, $format = NULL, array $context = []) {
    assert($link_collection instanceof LinkCollection);
    // @todo: remove this render context once https://www.drupal.org/project/drupal/issues/3055889 lands.
    $render_context = new RenderContext();
    $custom_links = $this->renderer->executeInRenderContext($render_context, function () use ($link_collection) {
      return $this->linkProviderManager->getLinkCollection($link_collection->getContext());
    });
    $normalized = [];
    foreach (LinkCollection::merge($link_collection, $custom_links) as $key => $links) {
      $normalizations = [];
      foreach ($links as $link) {
        assert($link instanceof Link);
        $normalization = [];
        $rel = (array) $link->getLinkRelationType();
        // Workaround to fix the JSON:API entrypoint.
        if (empty($rel)) {
          $rel = [$key];
        }
        $attributes = $link->getTargetAttributes();
        $normalization['href'] = $link->getHref();
        if (!empty($attributes)) {
          $normalization['meta']['linkParams'] = $this->serializer->normalize($attributes, $format, $context);
        }
        if (!empty($rel)) {
          // We have to call array_values() on $rel to reset the array's
          // numerical index. Otherwise, it can output arrays with missing
          // indices and json_encode will serialize these as objects. This is a
          // band-aid. The real fix probably needs to be made in Link::merge(),
          // Link::__construct() or LinkCollection::withLink() because one of
          // these methods is likely where the array's pointer is getting out of
          // place.
          $rel = array_values($rel);
          if (count($rel) > 1 || reset($rel) !== $key) {
            $normalization['meta']['linkParams']['rel'] = $rel;
          }
          $normalizations[] = $normalization;
        }
      }
      // Links that share the same link target and target attributes can be
      // compacted by combining their link relation types so that a single
      // link can represent multiple links (one per link relationship type).
      // This saves bytes in a JSON:API response.
      $compacted = array_reduce($normalizations, function ($compacted, $normalization) use ($key) {
        foreach ($compacted as &$previous) {
          $same_href = $previous['href'] === $normalization['href'];
          $previous_attributes = array_diff_key($previous['meta']['linkParams'] ?? [], array_flip(['rel']));
          $normalization_attributes = array_diff_key($normalization['meta']['linkParams'] ?? [], array_flip(['rel']));
          $same_attributes = Json::encode($previous_attributes) === Json::encode($normalization_attributes);
          if ($same_href && $same_attributes) {
            $combined_link_relation_types = array_unique(array_merge(
              $previous['meta']['linkParams']['rel'] ?? [$key],
              $normalization['meta']['linkParams']['rel'] ?? []
            ));
            // When the link relation type matches the link key, it can be
            // omitted, since it's implied. This also saves bytes in a
            // JSON:API response and is more readable.
            if ($combined_link_relation_types > 1 || reset($combined_link_relation_types) !== $key) {
              $previous['meta']['linkParams']['rel'] = $combined_link_relation_types;
            }
            return $compacted;
          }
        }
        $compacted[] = $normalization;
        return $compacted;
      }, []);
      $is_multiple = count($compacted) > 1;
      // Finally, the compacted link normalizations are turned into cacheable
      // normalizations and a unique link object key is computed for them when
      // more than one link with the same key is present. This is a workaround
      // for the fact that the JSON:API spec does not permit arrays of links.
      $normalized = array_reduce($compacted, function ($normalized, $normalization) use ($link, $key, $is_multiple) {
        $link_key = $is_multiple ? sprintf('%s--%s', $key, $this->hashLinkNormalization($normalization)) : $key;
        $normalized[$link_key] = new CacheableNormalization($link, $normalization);
        return $normalized;
      }, $normalized);
    }

    $normalized_links = CacheableNormalization::aggregate($normalized);
    return !$render_context->isEmpty()
      ? $normalized_links->withCacheableDependency($render_context->pop())
      : $normalized_links;
  }

  /**
   * Hashes a link normalization.
   *
   * @param array $link
   *   A link to be hashed.
   *
   * @return string
   *   A 7 character alphanumeric hash.
   */
  protected function hashLinkNormalization(array $link) {
    if (!$this->hashSalt) {
      $this->hashSalt = Crypt::randomBytesBase64();
    }
    return substr(str_replace(['-', '_'], '', Crypt::hashBase64($this->hashSalt . Json::encode($link))), 0, 7);
  }

}

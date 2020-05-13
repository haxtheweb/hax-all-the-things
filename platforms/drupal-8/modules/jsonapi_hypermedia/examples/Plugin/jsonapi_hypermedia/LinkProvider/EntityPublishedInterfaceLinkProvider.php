<?php

namespace Drupal\jsonapi_hypermedia\Plugin\jsonapi_hypermedia\LinkProvider;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\jsonapi\JsonApiResource\ResourceObject;
use Drupal\jsonapi_hypermedia\AccessRestrictedLink;
use Drupal\jsonapi_hypermedia\Plugin\LinkProviderBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class EntityPublishedInterfaceLinkProvider.
 *
 * This example shows how a link provider can provide easy-to-follow links that
 * further decouple a client implementation from the backend. In this case,
 * the client no longer needs to hard-code the correct field name to update on a
 * per-resource-type basis, nor does it need to perform complicated business
 * logic to check if the resource is already published or not—the presence of
 * the link conveys that information. Finally, if the backend later decides that
 * some user role is no allowed to publish content, the client does not need to
 * be updated to account for that change (the link will just disappear).
 *
 * @JsonapiHypermediaLinkProvider(
 *   id = "jsonapi_hypermedia.entity_published",
 *   deriver = "Drupal\jsonapi_hypermedia\Plugin\Derivative\EntityPublishedInterfaceLinkProviderDeriver",
 * )
 *
 * @internal
 */
final class EntityPublishedInterfaceLinkProvider extends LinkProviderBase implements ContainerFactoryPluginInterface {

  use ResourceObjectEntityLoaderTrait;

  /**
   * The published status internal field name.
   *
   * @var string
   */
  protected $statusFieldName;

  /**
   * {@inheritdoc}
   */
  protected function __construct(array $configuration, string $plugin_id, mixed $plugin_definition) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    assert(!empty($configuration['status_field_name']) && is_string($configuration['status_field_name']), "The status_field_name configuration value is required.");
    $this->statusFieldName = $configuration['status_field_name'];
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $provider = new static($configuration, $plugin_id, $plugin_definition);
    $provider->setEntityRepository($container->get('entity.repository'));
    return $provider;
  }

  /**
   * {@inheritdoc}
   */
  public function getLink($resource_object) {
    assert($resource_object instanceof ResourceObject);
    $resource_type = $resource_object->getResourceType();
    $entity = $this->loadEntityFromResourceObject($resource_object);
    assert($entity instanceof EntityPublishedInterface);
    $plugin_definition = $this->getPluginDefinition();
    $published = $entity->isPublished();
    $publish_operation = $plugin_definition['link_key'] === 'publish';
    $access_result = AccessResult::allowedIf($publish_operation !== $published)
      ->andIf($entity->access('update', NULL, TRUE))
      ->andIf($entity->{$this->statusFieldName}->access('edit', NULL, TRUE))
      ->addCacheableDependency($entity);
    $link_attributes = [
      'data' => [
        'type' => $resource_object->getTypeName(),
        'id' => $resource_object->getId(),
        'attributes' => [
          $resource_type->getPublicName($this->statusFieldName) => (int) !$published,
        ],
      ],
    ];
    return AccessRestrictedLink::createLink($access_result, CacheableMetadata::createFromObject($resource_object), $resource_object->toUrl(), $this->getLinkRelationType(), $link_attributes);
  }

}

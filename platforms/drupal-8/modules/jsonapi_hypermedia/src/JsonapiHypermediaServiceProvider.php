<?php

namespace Drupal\jsonapi_hypermedia;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class JsonApiHypermediaServiceProvider.
 *
 * @internal
 */
final class JsonapiHypermediaServiceProvider extends ServiceProviderBase {

  use PriorityTaggedServiceTrait;

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Enable normalizers in the "src-impostor-normalizers" directory to be
    // within the \Drupal\jsonapi\Normalizer namespace in order to circumvent
    // the encapsulation enforced by
    // \Drupal\jsonapi\Serializer\Serializer::__construct().
    $container_namespaces = $container->getParameter('container.namespaces');
    $container_modules = $container->getParameter('container.modules');
    $impostor_path = dirname($container_modules['jsonapi_hypermedia']['pathname']) . '/src/Normalizer/JsonapiHypermediaImpostor';
    $container_namespaces['Drupal\jsonapi\Normalizer\JsonapiHypermediaImpostor'][] = $impostor_path;
    $container->getDefinition('serializer.normalizer.link_collection.jsonapi_hypermedia')->setFile($impostor_path . '/JsonapiHypermediaLinkCollectionNormalizer.php');

    $container->setParameter('container.namespaces', $container_namespaces);
    $definition = $container->getDefinition('serializer.normalizer.link_collection.jsonapi_hypermedia');
    foreach ($this->findAndSortTaggedServices('jsonapi_hypermedia_provider', $container) as $id) {
      $definition->addMethodCall('addHypermediaProvider', [new Reference($id)]);
    }
  }

}

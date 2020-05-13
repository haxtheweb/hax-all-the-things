<?php

namespace Drupal\jsonapi_hypermedia_test_link_providers\Plugin\jsonapi_hypermedia\LinkProvider;

use Drupal\Component\Utility\Random;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\jsonapi_hypermedia\AccessRestrictedLink;
use Drupal\jsonapi_hypermedia\Plugin\LinkProviderBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class TestLinkProvider.
 *
 * @JsonapiHypermediaLinkProvider(
 *   id = "test_link_provider",
 *   deriver = "\Drupal\jsonapi_hypermedia_test_link_providers\Plugin\Derivative\TestLinkProviderDeriver",
 * )
 *
 * @internal
 */
final class TestLinkProvider extends LinkProviderBase implements ContainerFactoryPluginInterface {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $provider = new static($configuration, $plugin_id, $plugin_definition);
    $provider->setCurrentAccount($container->get('current_user'));
    return $provider;
  }

  /**
   * Sets the current account.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The current user account.
   */
  protected function setCurrentAccount(AccountInterface $account) {
    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public function getLink($context) {
    $plugin_definition = $this->getPluginDefinition();
    $link_key = $this->getLinkKey();
    $url = Url::fromUri("https://drupal.org/project/jsonapi_hypermedia/{$link_key}");
    $random = (new Random())->name();
    $attributes = array_merge($plugin_definition['_test_target_attributes'] ?? [], [
      'randomAttr' => $random,
    ]);
    $access_restricted = !empty($plugin_definition['_test_restrict_access']);
    $cacheability = new CacheableMetadata();
    $cacheability->addCacheTags(['test_jsonapi_hypermedia_cache_tag']);
    $permission = "view {$this->getLinkKey()} link";
    $access_result = $access_restricted
      ? AccessResult::allowedIfHasPermission($this->account, $permission)
      : AccessResult::allowed();
    return AccessRestrictedLink::createLink($access_result, $cacheability, $url, $this->getLinkRelationType(), $attributes);
  }

}

<?php

namespace Drupal\jsonapi_hypermedia\Plugin\jsonapi_hypermedia\LinkProvider;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Cache\CacheableMetadata;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\jsonapi\JsonApiResource\JsonApiDocumentTopLevel;
use Drupal\jsonapi_hypermedia\AccessRestrictedLink;
use Drupal\jsonapi_hypermedia\Plugin\LinkProviderBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Adds an `authenticate` link for unauthenticated requests.
 *
 * This presumes the only available authentication method is Drupal core's
 * default `cookie` authentication, using the `/user/login?_format=json` URL.
 *
 * @JsonapiHypermediaLinkProvider(
 *   link_context = {
 *     "top_level_object" => true,
 *   }
 * )
 *
 * @see https://www.drupal.org/node/2720655
 */
final class AuthenticationLinkProvider extends LinkProviderBase implements ContainerFactoryPluginInterface {

  /**
   * The current account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $provider = new static($configuration, $plugin_id, $plugin_definition);
    $provider->setCurrentUser($container->get('current_user'));
    return $provider;
  }

  /**
   * Sets the current account.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current account.
   */
  public function setCurrentUser(AccountInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkRelationType() {
    return $this->currentUser->isAuthenticated() ? 'logout' : 'authenticate';
  }

  /**
   * {@inheritdoc}
   */
  public function getLink($context) {
    assert($context instanceof JsonApiDocumentTopLevel);
    $is_authenticated = $this->currentUser->isAuthenticated();
    $route_name = !$is_authenticated ? 'user.login.http' : 'user.logout.http';
    $login_url = Url::fromUri($route_name, ['query' => ['_format' => 'json']]);
    $link_cacheability = new CacheableMetadata();
    $link_cacheability->addCacheContexts(['session.exists', 'user.roles:anonymous']);
    // The link is always accessible, but the link location and link relation
    // type depend on the request. This example also uses the link's target
    // attributes to indicate the media type of the link target, since it is not
    // `application/vnd.api+json` as might be expected.
    return AccessRestrictedLink::createLink(AccessResult::allowed(), $link_cacheability, $login_url, $this->getLinkRelationType(), [
      'type' => 'application/json',
    ]);
  }

}

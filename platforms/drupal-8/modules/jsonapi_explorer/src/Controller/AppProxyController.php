<?php

namespace Drupal\jsonapi_explorer\Controller;

use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Controller\ControllerBase;
use GuzzleHttp\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Class AppProxyController.
 *
 * @internal
 */
final class AppProxyController extends ControllerBase {

  /**
   * The URL of the remo
   *
   * @var string
   */
  protected $explorerUrl;

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\Client
   */
  protected $httpClient;

  /**
   * @var array
   */
  protected $corsConfig;

  /**
   * AppProxyController constructor.
   *
   * @param \GuzzleHttp\Client $http_client
   *   An HTTP client.
   * @param $explorer_url
   *   The URL of the explorer SPA to proxy.
   */
  public function __construct(Client $http_client, $explorer_url) {
    $this->httpClient = $http_client;
    assert(is_string($explorer_url));
    assert(UrlHelper::isValid($explorer_url, TRUE));
    assert(
      substr($explorer_url, -1) !== '/',
      sprintf('The provided JSON:API Explorer URL should not contain a trailing slash "/". Given: "%s".', $explorer_url)
    );
    $this->explorerUrl = $explorer_url;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('http_client'),
      $container->getParameter('jsonapi_explorer.location')
    );
  }

  /**
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   */
  public function app() {
    return $this->proxy('index.html');
  }

  /**
   * @param $file
   *
   * @return \Symfony\Component\HttpFoundation\StreamedResponse
   */
  public function proxy($file) {
    $proxy_url = "{$this->explorerUrl}/$file";
    $response = $this->httpClient->get($proxy_url);
    return StreamedResponse::create(function () use ($response) {
      $body = $response->getBody();
      while (!$body->eof()) {
        echo $body->read(1024);
      }
    }, $response->getStatusCode(), $response->getHeaders());
  }

}

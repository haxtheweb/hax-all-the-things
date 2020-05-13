<?php

namespace Drupal\Tests\jsonapi_hypermedia\Functional;

use Drupal\Component\Serialization\Json;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\node\Entity\NodeType;
use Drupal\Tests\BrowserTestBase;
use Drupal\Tests\jsonapi\Functional\JsonApiRequestTestTrait;
use Drupal\Tests\user\Traits\UserCreationTrait;
use Drupal\user\Entity\Role;
use GuzzleHttp\RequestOptions;

/**
 * Class LinkProviderTest.
 *
 * @group jsonapi_hypermedia
 *
 * @internal
 */
final class LinkProviderTest extends BrowserTestBase {

  use JsonApiRequestTestTrait;

  use UserCreationTrait;

  /**
   * {@inheritdoc}
   */
  protected static $modules = [
    'serialization',
    'jsonapi',
    'jsonapi_hypermedia',
    'jsonapi_hypermedia_test_link_providers',
    'node',
    'user',
    'system',
  ];

  /**
   * A map of document types to internal URIs.
   *
   * @var array
   */
  protected $uris;

  /**
   * The link provider manager.
   *
   * @var \Drupal\jsonapi_hypermedia\Plugin\LinkProviderManagerInterface
   */
  protected $linkManager;

  /**
   * The state service.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();
    NodeType::create(['type' => 'article', 'name' => 'Article'])->save();
    $node = Node::create(['type' => 'article', 'title' => 'Test Node']);
    $node->save();
    $this->uris = [
      'entrypoint' => 'base:/jsonapi',
      'error' => 'base:/jsonapi/node/article/missing',
      'individual' => "base:/jsonapi/node/article/{$node->uuid()}",
      'collection' => 'base:/jsonapi/node/article',
      'relationship' => "base:/jsonapi/node/article/{$node->uuid()}/relationships/uid",
    ];
    $this->linkManager = $this->container->get('jsonapi_hypermedia_provider.manager');
    $this->state = $this->container->get('state');
    $this->account = $this->createUser();
    $this->container->set('current_user', $this->account);
  }

  /**
   * Tests that link provider plugins properly affect the JSON:API output.
   *
   * @dataProvider pluginDefinitionTemplates
   */
  public function testLinkProviders($plugin_template) {
    $link_location = $plugin_template['location'];
    $expected_on_document_types = $plugin_template['presence'] ?? TRUE;
    $this->state->set('jsonapi_hypermedia_test_link_providers.template', $plugin_template);
    $this->rebuildAll();
    $test_definitions = array_filter($this->linkManager->getDefinitions(), function ($definition) {
      return $definition['provider'] === 'jsonapi_hypermedia_test_link_providers';
    });
    foreach ($test_definitions as $plugin_id => $test_definition) {
      $link_key = $test_definition['link_key'] ?? $test_definition['link_relation_type'];
      if (!empty($test_definition['_test_restrict_access'])) {
        $link = $this->getLink($link_location, $link_key, []);
        $this->assertNull($link);
        $this->grantPermissions(
          Role::load(AccountInterface::ANONYMOUS_ROLE),
          ["view {$test_definition['link_key']} link"]
        );
        $link = $this->getLink($link_location, $link_key, $expected_on_document_types);
      }
      else {
        $link = $this->getLink($link_location, $link_key, $expected_on_document_types);
      }
      if (!empty($expected_on_document_types)) {
        $this->assertNotNull($link);
      }
      else {
        $this->assertNull($link);
        continue;
      }
      if (!empty($test_definition['_test_target_attributes'])) {
        $actual = NestedArray::getValue($link, ['meta', 'linkParams']);
        $this->assertNotNull($actual);
        $this->assertSame(
          $test_definition['_test_target_attributes'],
          array_diff_key($actual, array_flip(['rel', 'randomAttr']))
        );
      }
      $rel_exists = NULL;
      $actual_rel = NestedArray::getValue($link, ['meta', 'linkParams', 'rel'], $rel_exists);
      if ($test_definition['link_relation_type'] === 'test') {
        $this->assertTrue($rel_exists);
        $this->assertSame('https://drupal.org/project/jsonapi_hypermedia/link-relations/#test', $actual_rel[0]);
      }
      elseif (empty($test_definition['link_key'])) {
        $this->assertFalse($rel_exists);
      }
      else {
        $this->assertTrue($rel_exists);
        $this->assertSame('related', $actual_rel[0]);
      }
      $cache_hit_link = $this->getLink($link_location, $link_key, $expected_on_document_types);
      $this->assertSame($link, $cache_hit_link);
      Cache::invalidateTags(['test_jsonapi_hypermedia_cache_tag']);
      $cache_miss_link = $this->getLink($link_location, $link_key, $expected_on_document_types);
      $this->assertNotSame($cache_hit_link, $cache_miss_link);
    }
  }

  /**
   * Requests a document from which to assert & extract an expected link.
   *
   * @param string $link_location
   *   The path to the links object in the document given as a dot (.) separated
   *   list of JSON property names.
   * @param string $link_key
   *   The key of the expected link.
   * @param string[]|true $expected_on_document_types
   *   The types of documents on which to assert and extract the link. TRUE if
   *   the link is expected on all document types.
   *
   * @return array|null
   *   The normalized link or NULL if the link was not found.
   */
  protected function getLink($link_location, $link_key, $expected_on_document_types) {
    assert($expected_on_document_types === TRUE || is_array($expected_on_document_types));
    $path = array_map(function ($key) {
      return is_numeric($key) ? (int) $key : $key;
    }, array_merge(explode('.', $link_location), [$link_key]));
    $link = NULL;
    foreach ($this->uris as $document_type => $uri) {
      $is_error_type = $document_type === 'error';
      $document = $this->getJsonapiDocument($uri, $is_error_type ? 404 : 200);
      $exists = NULL;
      $current_link = NestedArray::getValue($document, $path, $exists);
      $expected_everywhere = $expected_on_document_types === TRUE;
      $expected_on_current_type = !$expected_everywhere && in_array($document_type, $expected_on_document_types);
      $expected_on_success = !$expected_everywhere && in_array('success', $expected_on_document_types);
      if ($expected_everywhere || $expected_on_current_type || ($expected_on_success && !$is_error_type)) {
        $this->assertTrue($exists, "Expected link under `$link_location` on the $document_type document at $uri");
      }
      else {
        $this->assertFalse($exists, "Unexpected link under `$link_location` on the $document_type document at $uri");
      }
      $link = $current_link ?? $link;
    }
    return $link;
  }

  /**
   * Gets an array of templates from which to configure test plugins.
   *
   * @return array
   *   The plugin definition templates.
   */
  public function pluginDefinitionTemplates() {
    return [
      'all top-level links' => [
        [
          'link_context' => ['top_level_object' => TRUE],
          'location' => 'links',
        ],
      ],
      'top-level links; only on the entrypoint' => [
        [
          'link_context' => ['top_level_object' => 'entrypoint'],
          'location' => 'links',
          'presence' => ['entrypoint'],
        ],
      ],
      'top-level links; only on successful documents' => [
        [
          'link_context' => ['top_level_object' => 'success'],
          'location' => 'links',
          'presence' => [
            'entrypoint',
            'collection',
            'individual',
            'relationship',
            'related',
          ],
        ],
      ],
      'top-level links; only on error documents' => [
        [
          'link_context' => ['top_level_object' => 'error'],
          'location' => 'links',
          'presence' => ['error'],
        ],
      ],
      'top-level links; only on individual documents' => [
        [
          'link_context' => ['top_level_object' => 'individual'],
          'location' => 'links',
          'presence' => ['individual'],
        ],
      ],
      'top-level links; only on collection documents' => [
        [
          'link_context' => ['top_level_object' => 'collection'],
          'location' => 'links',
          'presence' => ['collection'],
        ],
      ],
      'top-level links; only on relationship documents' => [
        [
          'link_context' => ['top_level_object' => 'relationship'],
          'location' => 'links',
          'presence' => ['relationship'],
        ],
      ],
      'resource object links on individual documents' => [
        [
          'link_context' => ['resource_object' => TRUE],
          'location' => 'data.links',
          'presence' => ['individual'],
        ],
      ],
      'resource object links on collection documents' => [
        [
          'link_context' => ['resource_object' => TRUE],
          'location' => 'data.0.links',
          'presence' => ['collection'],
        ],
      ],
      'only article resource objects on individual documents' => [
        [
          'link_context' => ['resource_object' => 'node--article'],
          'location' => 'data.links',
          'presence' => ['individual'],
        ],
      ],
      'only article resource objects on collection documents' => [
        [
          'link_context' => ['resource_object' => 'node--article'],
          'location' => 'data.0.links',
          'presence' => ['collection'],
        ],
      ],
      'relationship objects on individual documents' => [
        [
          'link_context' => ['relationship_object' => TRUE],
          'location' => 'data.relationships.uid.links',
          'presence' => ['individual'],
        ],
      ],
      'relationship objects on collection documents' => [
        [
          'link_context' => ['relationship_object' => TRUE],
          'location' => 'data.0.relationships.uid.links',
          'presence' => ['collection'],
        ],
      ],
      'relationship objects on relationship documents' => [
        [
          'link_context' => ['relationship_object' => TRUE],
          'location' => 'links',
          'presence' => ['relationship'],
        ],
      ],
      'article author relationship objects on individual documents' => [
        [
          'link_context' => ['relationship_object' => ['node--article', 'uid'],
          ],
          'location' => 'data.relationships.uid.links',
          'presence' => ['individual'],
        ],
      ],
      'article bundle relationship objects on individual documents' => [
        [
          'link_context' => ['relationship_object' => ['node--article', 'uid'],
          ],
          'location' => 'data.relationships.node_type.links',
          'presence' => [],
        ],
      ],
      'article author relationship objects on collection documents' => [
        [
          'link_context' => ['relationship_object' => ['node--article', 'uid'],
          ],
          'location' => 'data.0.relationships.uid.links',
          'presence' => ['collection'],
        ],
      ],
      'article bundle relationship objects on collection documents' => [
        [
          'link_context' => ['relationship_object' => ['node--article', 'uid'],
          ],
          'location' => 'data.0.relationships.node_type.links',
          'presence' => [],
        ],
      ],
      'article author relationship objects on relationship documents' => [
        [
          'link_context' => ['relationship_object' => ['node--article', 'uid']],
          'location' => 'links',
          'presence' => ['relationship'],
        ],
      ],
    ];
  }

  /**
   * Performs a JSON:API request and returns its deserialized document response.
   *
   * @param string $uri
   *   The URI of the JSON:API document to be fetched.
   * @param int $expected_status
   *   The expected status of the response to be fetched.
   *
   * @return array
   *   The fetched and deserialized JSON:API document.
   */
  protected function getJsonapiDocument($uri, $expected_status = 200) {
    $headers = ['accept' => 'application/vnd.api+json'];
    $options = [RequestOptions::HEADERS => $headers];
    $url = Url::fromUri($uri);
    $response = $this->request('GET', $url, $options);
    $this->assertSame($expected_status, $response->getStatusCode(), "URL: {$url->setAbsolute()->toString()}");
    $body = (string) $response->getBody();
    $document = Json::decode($body);
    $this->assertNotNull($document, "Response Body: {$body}");
    return $document;
  }

}

<?php

namespace Drupal\jsonapi_hypermedia_test_link_providers\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Utility\Random;

/**
 * Derives test plugins.
 *
 * @internal
 */
final class TestLinkProviderDeriver extends DeriverBase {

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    $plugin_template = \Drupal::state()->get('jsonapi_hypermedia_test_link_providers.template');
    $random = new Random();
    $definitions['not_restricted'] = [
      'link_key' => $random->name(8, TRUE),
      'link_relation_type' => 'related',
      'link_context' => $plugin_template['link_context'],
    ];
    $definitions['access_restricted'] = [
      'link_key' => $random->name(8, TRUE),
      'link_relation_type' => 'related',
      'link_context' => $plugin_template['link_context'],
      '_test_restrict_access' => TRUE,
    ];
    $definitions['no_link_relations'] = [
      'link_key' => $random->name(8, TRUE),
      'link_relation_type' => 'related',
      'link_context' => $plugin_template['link_context'],
    ];
    $definitions['link_relations'] = [
      'link_key' => $random->name(8, TRUE),
      'link_relation_type' => 'test',
      'link_context' => $plugin_template['link_context'],
    ];
    $definitions['target_attributes'] = [
      'link_key' => $random->name(8, TRUE),
      'link_relation_type' => 'related',
      'link_context' => $plugin_template['link_context'],
      '_test_target_attributes' => ['foo' => 'bar'],
    ];
    $this->derivatives = array_map(function (array $definition) use ($base_plugin_definition) {
      return array_merge($definition, $base_plugin_definition);
    }, $definitions);
    return $this->derivatives;
  }

}

<?php

namespace Drupal\jsonapi_hypermedia\Plugin;

use Drupal\Core\Plugin\PluginBase;
use Drupal\jsonapi_hypermedia\LinkProviderInterface;

/**
 * Class LinkProviderBase.
 */
abstract class LinkProviderBase extends PluginBase implements LinkProviderInterface {

  /**
   * {@inheritdoc}
   */
  public function getLinkKey() {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['link_key'] ?? $this->getLinkRelationType();
  }

  /**
   * {@inheritdoc}
   */
  public function getLinkRelationType() {
    $plugin_definition = $this->getPluginDefinition();
    return $plugin_definition['link_relation_type'];
  }

}

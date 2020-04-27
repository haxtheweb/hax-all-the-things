<?php

namespace Drupal\webcomponents\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;

/**
 * Class WebcomponentsSettings.
 *
 * @package Drupal\webcomponents\Form
 */
class WebcomponentsSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'webcomponents_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('webcomponents.settings');
    foreach (Element::children($form) as $variable) {
      $value = $form_state->getValue($form[$variable]['#parents']);
      $config->set($variable, $value);
    }
    $config->save();

    if (method_exists($this, '_submitForm')) {
      $this->_submitForm($form, $form_state);
    }

    parent::submitForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('webcomponents.settings');
    $form['webcomponents_project_location'] = [
      '#type' => 'select',
      '#title' => $this->t('Webcomponents Location'),
      '#default_value' => $config->get('webcomponents_project_location'),
      '#description' => $this->t("Use this to point to CDNs or if you've installed your web components some place else. Start without a slash and end with a slash."),
      '#options' => array(
        'https://cdn.webcomponents.psu.edu/cdn/' => $this->t('Penn state CDN'),
        'https://cdn.waxam.io/' => $this->t('WaxaM CDN'),
        base_path() . 'sites/all/libraries/webcomponents' => $this->t('Local (sites/all/libraries/webcomponents)'),
        'other' => $this->t('Other (listed below)'),
      )
    ];
    $form['webcomponents_project_location_other'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other Location'),
      '#default_value' => $config->get('webcomponents_project_location_other'),
      '#description' => $this->t("Only use this if you need to use a source other than the above supported options."),
    ];
    return parent::buildForm($form, $form_state);
  }
  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['webcomponents.settings'];
  }
}

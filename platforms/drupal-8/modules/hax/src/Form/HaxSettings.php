<?php

namespace Drupal\hax\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Element;
use Drupal\Core\Url;
use Drupal\hax\HaxService;

/**
 * Class HaxSettings.
 *
 * @package Drupal\hax\Form
 */
class HaxSettings extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hax_settings';
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('hax.settings');
    $hax = new HaxService();
    foreach (Element::children($form) as $variable) {
      $value = $form_state->getValue($form[$variable]['#parents']);
      if ($variable == 'hax_blox' || $variable == 'hax_stax') {
        // test the JSON vaiability of this, if not then just leave blank
        if ($value === '') {
          $value = json_encode($hax->loadBaseStax());
        }
        else if (json_decode($value) === NULL) {
          $value = '[]';
        }
      }
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

    $config = $this->config('hax.settings');
    $form['hax_element_to_do_the_work'] = [
      '#type' => 'inline_template',
      '#template' => '{{ somecontent|raw }}',
      '#context' => [
        'somecontent' => '<hax-element-list-selector></hax-element-list-selector>',
      ],
    ];
    $form['hax_project_location'] = [
      '#type' => 'select',
      '#title' => $this->t('Webcomponents Location'),
      '#default_value' => $config->get('hax_project_location'),
      '#options' => array(
        'https://cdn.webcomponents.psu.edu/cdn/' => 'Penn State CDN',
        'https://cdn.waxam.io/' => 'Waxam CDN',
        'sites/all/libraries/webcomponents/' => 'Local libraries folder (sites/all/libraries/webcomponents/)',
        'other' => $this->t('Other'),
      ),
      '#description' => $this->t("Use this to point to CDNs or if you've installed your web components some place else. Start without a slash and end with a slash."),
    ];
    $form['hax_project_location_other'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Other Location'),
      '#default_value' => $config->get('hax_project_location_other'),
      '#maxlength' => 1000,
      '#description' => $this->t("Only use this if you need to use a source other than the above supported options."),
    ];
    $form['hax_autoload_element_list'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Elements to autoload'),
      '#default_value' => $config->get('hax_autoload_element_list'),
      '#description' => $this->t("This allows for auto-loading elements known to play nice with HAX. If you've written any webcomponents that won't automatically be loaded into the page via that module this allows you to attempt to auto-load them when HAX loads. For example, if you have a video-player element in your bower_components directory and want it to load on this interface, this would be a simple way to do that. Spaces only between elements, no comma"),
    ];
    // @todo need to get that JSON editor in here or VS code cause otherwise this is impossible to work with
    $hax = new HaxService();
    $blox = $config->get('hax_blox');
    if (!$blox || $blox == '') {
      $blox = json_encode($hax->loadBaseBlox());
    }
    $form['hax_blox'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Blox schema'),
      '#default_value' => $blox,
      '#description' => $this->t("This occupies the "),
    ];
    $stax = $config->get('hax_stax');
    if (!$stax || $stax == '') {
      $stax = json_encode($hax->loadBaseStax());
    }
    $form['hax_stax'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Stax schema'),
      '#default_value' => $stax,
      '#description' => $this->t("This occupies the "),
    ];
    // plug in all the other API keys for common, complex integrations
    $baseApps = $hax->baseSupportedApps();
    foreach ($baseApps as $key => $app) {
      $form['hax_' . $key . '_key'] = [
        '#type' => 'textfield',
        '#title' => $this->t('@name API key', [
          '@name' => $app['name'],
        ]),
        '#default_value' => $config->get('hax_' . $key . '_key'),
        '#description' => Link::fromTextAndUrl($this->t('See @name developer docs',
          ['@name' => $app['name']]), Url::fromUri($app['docs'])),
      ];
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['hax.settings'];
  }

}

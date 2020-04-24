<?php

namespace Drupal\hax\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Component\Utility\UrlHelper;
use Drupal\node\Controller\NodeViewController;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\Response;
use Drupal\hax\HaxService;

/**
 * Defines a controller to render a single node in HAX Mode.
 */
class HaxController extends NodeViewController {

  /**
   * {@inheritdoc}
   */
  public function title(EntityInterface $node) {
    // TODO Doesn't appear to be working, but shows up in router. What gives?
    return t("HAX edit @label", [
      '@label' => $this->entityManager->getTranslationFromContext($node)->label(),
    ]);
  }

  /**
   * Hax node edit form.
   *
   * @param \Drupal\Core\Entity\EntityInterface $node
   *   The node.
   * @param string $view_mode
   *   The node's view mode.
   * @param null $langcode
   *   The node's langcode.
   *
   * @return array
   *   The node's view render array.
   *
   * @todo: There's a good chance this logic isn't invoked.
   */
  public function form(EntityInterface $node, $view_mode = 'full', $langcode = NULL) {
    // Based on NodeViewController's view() method.
    $build = parent::view($node, $view_mode, $langcode);

    // This method only seems useful for adding attachments, but not for
    // altering. Much of the contents of $build['#node'] are protected
    // Is hax_node_view() a better place for altering the node field output?
    // Or are there other hooks we're missing?
    // TODO maybe just route to the canonical if we end up not actually using
    // this controller.
    return $build;
  }

  /**
   * Permission + Node access check.
   *
   * @param mixed $op
   *   The operation.
   * @param \Drupal\node\NodeInterface $node
   *   The node.
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   Either allowed or forbidden access response.
   */
  public function access($op, NodeInterface $node) {

    if (\Drupal::currentUser()->hasPermission('use hax') && node_node_access($node, $op, \Drupal::currentUser())) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

  /**
   * Custom node save logic for hax.
   *
   * @param \Drupal\node\NodeInterface $node
   *   The hax node.
   * @param mixed $token
   *   A token.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The http response.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   * @throws \Drupal\Core\TypedData\Exception\ReadOnlyException
   */
  public function save(NodeInterface $node, $token) {

    if (
      $_SERVER['REQUEST_METHOD'] == 'PUT' &&
      \Drupal::csrfToken()->validate($token)) {

      // We're not using the Drupal entity REST system outright here, as PUT
      // isn't supported. But we can, ahem, "patch" the behavior in ourselves.
      // HAX submitted value is right here.
      $body = file_get_contents('php://input');

      // Get the current format, and retain it. User will have to manage that
      // via the edit tab. We don't want to auto-set it. Making changes like
      // that without user intentionality is bad practice.
      $current_format = $node->get('body')->getValue()[0]['format'];

      // TODO Should we leverage the Text Editor API ?
      // https://www.drupal.org/docs/8/api/text-editor-api/overview
      // TODO Any santization or security checks on $body?
      $node->get('body')->setValue([
        'value' => $body,
        'format' => $current_format,
      ]);
      $node->save();

      // Build the response object.
      $response = new Response();
      $response->headers->set('Content-Type', 'application/json');
      $response->setStatusCode(200);
      $response->setContent(json_encode([
        'status' => 200,
        'message' => 'Save successful',
        'data' => $node,
      ]));
      return $response;
    }

    $response = new Response();
    $response->headers->set('Content-Type', 'application/json');
    $response->setStatusCode(403);
    $response->setContent(json_encode([
      'status' => 403,
      'message' => 'Unauthorized',
      'data' => NULL,
    ]));
    return $response;
  }

  /**
   * Permission + File access check.
   *
   * @param mixed $op
   *   The operation?
   *
   * @return \Drupal\Core\Access\AccessResultAllowed|\Drupal\Core\Access\AccessResultForbidden
   *   Whether the file access is allowed or forbidden.
   *
   * @todo: param does not appear to be used.  Remove?
   */
  public function fileAccess($op) {
    // Ensure there are entity permissions to create a file via HAX.
    // See https://www.drupal.org/project/hax/issues/2962055#comment-12617576
    if (\Drupal::currentUser()->hasPermission('use hax') &&
      \Drupal::currentUser()->hasPermission('upload files via hax')) {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }
  /**
   * Save a file to the file system.
   *
   * @param mixed $token
   *   Is this a token object?
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The http response.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   *
   * @todo: Update data type for token.
   */
  public function fileSave($token) {
    $status = 403;
    $return = '';

    // Check for the uploaded file from our 1-page-uploader app
    // and ensure there are entity permissions to create a file via HAX.
    // See https://www.drupal.org/project/hax/issues/2962055#comment-12617576.
    if (\Drupal::csrfToken()->validate($token, 'hax-file-save') &&
      \Drupal::currentUser()->hasPermission('upload files via hax') && isset($_FILES['file-upload'])) {
      $upload = $_FILES['file-upload'];
      // Check for a file upload.
      if (isset($upload['tmp_name']) && is_uploaded_file($upload['tmp_name'])) {
        // Get contents of the file if it was uploaded into a variable.
        $data = file_get_contents($upload['tmp_name']);
        $params = filter_var_array($_POST, FILTER_SANITIZE_STRING);
        // See if we had a file_wrapper defined, otherwise this is public.
        if (isset($params['file_wrapper'])) {
          $file_wrapper = $params['file_wrapper'];
        }
        else {
          $file_wrapper = 'public';
        }
        // See if Drupal can load from this data source.
        if ($file = file_save_data($data, $file_wrapper . '://' . $upload['name'])) {
          $uri = str_replace($GLOBALS['base_url'] . '/', $GLOBALS['base_path'],file_create_url($file->getFileUri()));
          $return = array(
            'file' => array(
              'url' => $uri, // this is actually the uri but we can't return a full url safely
              'uri' => $uri,
              'status' => $file->status,
              'status' => $file->timestamp,
              'uid' => $file->uid,
              'uuid' => $file->uuid,
              'filemime' => $file->getMimeType(),
              'filename' => $file->getFilename(),
            ),
          );
          $status = 200;
        }
      }
    }
    // Build the response object.
    $response = new Response();
    $response->headers->set('Content-Type', 'application/json');
    $response->setStatusCode($status);
    $response->setContent(json_encode([
      'status' => $status,
      'data' => $return,
    ]));
    return $response;
  }

  /**
   * Load app store.
   *
   * @param mixed $token
   *   The app store token.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The http response.
   */
  public function loadAppStore($token) {
    // Ensure we had data PUT here and it is valid.
    if (\Drupal::csrfToken()->validate($token, 'hax-app-store')) {
      // Hooks and alters.
      // Add/alter apps.
      $appStore = \Drupal::moduleHandler()->invokeAll('hax_app_store');
      \Drupal::moduleHandler()->alter('hax_app_store', $appStore);
      // Add/alter layouts.
      $autoloaderList = \Drupal::moduleHandler()->invokeAll('hax_autoloader');
      \Drupal::moduleHandler()->alter('hax_autoloader', $autoloaderList);
      foreach ($autoloaderList as $ary => $values) {
        foreach ($values as $key => $value) {
          $autoloader[$key] = $value;
        }
      }
      // Add/alter templates. For reference, see appstore.json in
      $staxList = \Drupal::moduleHandler()->invokeAll('hax_stax');
      \Drupal::moduleHandler()->alter('hax_stax', $staxList);
      // Add/alter layouts.
      $bloxList = \Drupal::moduleHandler()->invokeAll('hax_blox');
      \Drupal::moduleHandler()->alter('hax_blox', $bloxList);

      // Send the Response object with Apps and StaxList.
      $response = new Response();
      $response->headers->set('Content-Type', 'application/json');
      $response->setStatusCode(200);
      $response->setContent(json_encode([
        'status' => 200,
        'apps' => $appStore,
        'stax' => $staxList,
        'blox' => $bloxList,
        'autoloader' => $autoloader,
      ]));

      return $response;
    }

    // "Unauthorized" response.
    $response = new Response();
    $response->setStatusCode(403);

    return $response;
  }

  /**
   * Load registration definitions
   *
   * @param mixed $token
   *   CSRF security token.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The http response.
   */
  public function loadHaxElementListSelectorData($token) {
    // Ensure we had data PUT here and it is valid.
    if (\Drupal::csrfToken()->validate($token, 'hax-element-list-selector-data')) {
      $config = \Drupal::config('hax.settings');
      $hax = new HaxService();
      $apikeys = [];
      $baseApps = $hax->baseSupportedApps();
      foreach ($baseApps as $key => $app) {
        if ($config->get('hax_' . $key . '_key') != '') {
          $apikeys['haxcore-integrations-' . $key] = $config->get('hax_' . $key . '_key');
        }
      }
      $data = json_decode('{
        "fields": [
          {
            "property": "haxcore",
            "inputMethod": "tabs",
            "properties": [
              {
                "property": "providers",
                "title": "Providers",
                "description": "Providers of functionality",
                "properties": [
                  {
                    "property": "haxcore-providers-cdn",
                    "title": "CDN",
                    "description": "Content delivery network that supplies your elements and HAX definitions",
                    "inputMethod": "select",
                    "options": {
                      "https://cdn.webcomponents.psu.edu/cdn/": "Penn State CDN",
                      "https://cdn.waxam.io/": "WaxaM CDN",
                      "' . base_path() . 'sites/all/libraries/webcomponents/": "Local libraries folder (sites/all/libraries/webcomponents/)",
                      "other": "Other location"
                    }
                  },
                  {
                    "property": "haxcore-providers-other",
                    "title": "Other",
                    "description": "Entrypoint for the cdn / required files for a provider",
                    "inputMethod": "textfield"
                  },
                  {
                    "property": "haxcore-providers-pk",
                    "title": "Public key",
                    "description": "Public key, required by some providers",
                    "inputMethod": "textfield"
                  }
                ]
              },
              {
                "property": "search",
                "title": "HAX Elements",
                "properties": [
                  {
                    "property": "haxcore-search-search",
                    "title": "Search",
                    "description": "Filter elements by name",
                    "inputMethod": "textfield"
                  },
                  {
                    "property": "haxcore-search-tags",
                    "title": "Tags",
                    "description": "Tags to filter on",
                    "inputMethod": "select",
                    "options": {
                      "": "",
                      "Video": "Video",
                      "Image": "Image",
                      "Media": "Media",
                      "Card": "Card",
                      "Content": "Content",
                      "Table": "Table",
                      "Layout": "Layout",
                      "Presentation": "Presentation",
                      "Data": "Data",
                      "Education": "Education",
                      "Funny": "Funny"
                    }
                  },
                  {
                    "property": "haxcore-search-hasdemo",
                    "title": "Has demo",
                    "description": "Only show elements with demos",
                    "inputMethod": "boolean"
                  },
                  {
                    "property": "haxcore-search-columns",
                    "title": "Columns",
                    "description": "Columns to organize the results into",
                    "inputMethod": "select",
                    "options": {
                      "2": "2 Columns",
                      "3": "3 Columns",
                      "4": "4 Columns",
                      "5": "5 Columns"
                    }
                  },
                  {
                    "property": "haxcore-search-autoloader",
                    "inputMethod": "object",
                    "format": "cardlist"
                  }
                ]
              },
              {
                "property": "templates",
                "title": "Templates / Layouts",
                "description": "Manage groups of templates and layouts",
                "properties": [
                  {
                    "property": "haxcore-templates-templates",
                    "title": "Templates",
                    "description": "Stax version of HAXElementSchema",
                    "inputMethod": "markup"
                  },
                  {
                    "property": "haxcore-templates-layouts",
                    "title": "Layouts",
                    "description": "Blox version of HAXElementSchema",
                    "inputMethod": "markup"
                  }
                ]
              },
              {
                "property": "integrations",
                "title": "Integrations",
                "description": "API keys and integrations with other services",
                "properties": [
                  {
                    "property": "haxcore-integrations-youtube",
                    "title": "Youtube",
                    "description": "https://developers.google.com/youtube/v3/getting-started",
                    "inputMethod": "textfield"
                  },
                  {
                    "property": "haxcore-integrations-googlepoly",
                    "title": "Google Poly",
                    "description": "https://developers.google.com/youtube/v3/getting-started",
                    "inputMethod": "textfield"
                  },
                  {
                    "property": "haxcore-integrations-memegenerator",
                    "title": "Meme generator",
                    "description": "https://memegenerator.net/Api",
                    "inputMethod": "textfield"
                  },
                  {
                    "property": "haxcore-integrations-vimeo",
                    "title": "Vimeo",
                    "description": "https://developer.vimeo.com/",
                    "inputMethod": "textfield"
                  },
                  {
                    "property": "haxcore-integrations-giphy",
                    "title": "Giphy",
                    "description": "https://developers.giphy.com/docs/",
                    "inputMethod": "textfield"
                  },
                  {
                    "property": "haxcore-integrations-unsplash",
                    "title": "Unsplash",
                    "description": "https://unsplash.com/developers",
                    "inputMethod": "textfield"
                  },
                  {
                    "property": "haxcore-integrations-flickr",
                    "title": "Flickr",
                    "description": "https://www.flickr.com/services/developer/api/",
                    "inputMethod": "textfield"
                  },
                  {
                    "property": "haxcore-integrations-pixabay",
                    "title": "Pixabay",
                    "description": "https://pixabay.com/api/docs/",
                    "inputMethod": "textfield"
                  }
                ]
              },
              {
                "property": "providerdetails",
                "title": "Provider details",
                "description": "Detailing the functionality provided by this provider",
                "properties": [
                  {
                    "property": "haxcore-providerdetails-name",
                    "title": "Name",
                    "description": "Content delivery network that supplies your elements and HAX definitions",
                    "inputMethod": "textfield"
                  },
                  {
                    "property": "haxcore-providerdetails-haxtags",
                    "title": "HAX editable tags",
                    "description": "Tags that extend HAX editor",
                    "inputMethod": "markup"
                  },
                  {
                    "property": "haxcore-providerdetails-othertags",
                    "title": "Other web components",
                    "description": "Valid tags discovered that don\'t provide HAX wiring, useful for building other applications",
                    "inputMethod": "markup"
                  }
                ]
              },
              {
                "property": "help",
                "title": "Help",
                "description": "Help info and how to get started",
                "properties": [
                  {
                    "property": "haxcore-help-docs",
                    "title": "Documentation",
                    "description": "Help using HAX and related projects",
                    "inputMethod": "md-block"
                  }
                ]
              }
            ]
          }
        ],
        "value": {
          "haxcore": {
            "providers": {
              "haxcore-providers-cdn": "' . $config->get('hax_project_location') . '",
              "haxcore-providers-other": "' . $config->get('hax_project_location_other') . '",
              "haxcore-providers-pk": "' . $config->get('hax_project_pk') . '"
            },
            "search": {
              "haxcore-search-search": "",
              "haxcore-search-tags": "",
              "haxcore-search-hasdemo": false,
              "haxcore-search-columns": "",
              "haxcore-search-autoloader": ' . $config->get('hax_autoload_element_list') . '
            },
            "templates": {
              "haxcore-templates-templates": ' . json_encode($config->get('hax_stax')) . ',
              "haxcore-templates-layouts": ' . json_encode($config->get('hax_blox')) . '
            },
            "integrations": ' . json_encode($apikeys) . ',
            "providerdetails": {
              "haxcore-providerdetails-name": "",
              "haxcore-providerdetails-haxtags": "",
              "haxcore-providerdetails-othertags": ""
            },
            "help": {
              "haxcore-help-docs": "https://raw.githubusercontent.com/elmsln/HAXcms/master/HAXDocs.md"
            }
          }
        }
      }');
      
      // Send the Response object with Apps and StaxList.
      $response = new Response();
      $response->headers->set('Content-Type', 'application/json');
      $response->setStatusCode(200);
      $response->setContent(json_encode([
        'status' => 200,
        'data' => $data,
      ]));
      return $response;
    }

    // "Unauthorized" response.
    $response = new Response();
    $response->setStatusCode(403);

    return $response;
  }

}

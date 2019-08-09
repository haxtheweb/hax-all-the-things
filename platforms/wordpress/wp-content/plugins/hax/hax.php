<?php
include_once 'HAXService.php';
define('WP_HAX_AUTOLOAD_ELEMENT_LIST', 'oer-schema lrn-aside grid-plate tab-list magazine-cover video-player image-compare-slider license-element self-check multiple-choice lrn-table hero-banner task-list media-image lrndesign-blockquote meme-maker a11y-gif-player paper-audio-player wikipedia-query lrn-vocab lrn-math person-testimonial citation-element code-editor place-holder stop-note q-r wave-player');

/**
 * @package HAX
 * @version 3.1.0
 */
/*
Plugin Name: HAX
Plugin URI: https://github.com/elmsln/wp-plugin-hax
Description: An ecosystem agnostic web editor to democratise the web and liberate users of platforms.
Author: Bryan Ollendyke
Version: 3.1.0
Author URI: https://haxtheweb.org/
*/

// Wire up HAX to hijack the Classic editor
function hax_wordpress($hook) {
  if ($hook == 'post.php' || $hook == 'post-new.php') {
    wp_enqueue_script( 'hax_the_press', plugins_url('/hax/js/hax-the-press.js'), array(), false, true );
  }
}
add_action( 'admin_enqueue_scripts', 'hax_wordpress' );

// Wire up web components to WordPress
function hax_wordpress_connector() {
  $data = array(
    'url' => get_site_url(null, '/wp-json/hax/v1/appstore.json?token=' . hax_generate_secure_key('haxTheWeb')),
  );
  print '<style>#adminmenuwrap{z-index:1000 !important;}h-a-x{padding:40px;}</style><script>window.haxThePressConnector=\'' . json_encode($data) . '\';</script>';
}
// back end paths
add_action( 'admin_head', 'hax_wordpress_connector' );

// admin settings page
function hax_admin_init() { 	
 	// Add the field with the names and function to use for our new
 	// settings, put it in our new section
 	add_settings_field(
		'hax_settings',
		'HAX block editor',
		'hax_setting_callback_function',
		'general',
		'webcomponents_setting_section'
	);
   	
 	// Register our setting so that $_POST handling is done for us and
 	// our callback function just has to echo the <input>
  register_setting( 'general', 'hax_autoload_element_list' );
  // build out the key space for our baseline app integrations
  $hax = new HAXService();
  $baseApps = $hax->baseSupportedApps();
  foreach ($baseApps as $key => $app) {
    register_setting('general', 'hax_' . $key . '_key');
  }
}
add_action( 'admin_init', 'hax_admin_init' );

function hax_setting_callback_function() {
  // autoload list
  echo '<label>Autoloaded element list</label>';
  echo '<input name="hax_autoload_element_list" id="hax_autoload_element_list" type="text" value="' . get_option('hax_autoload_element_list', WP_HAX_AUTOLOAD_ELEMENT_LIST) . '" class="code" size="80" />';
	echo '<div>This allows for auto-loading elements known to play nice with HAX. If you\'ve written any webcomponents that won\'t automatically be loaded into the page via that module this allows you to attempt to auto-load them when HAX loads. For example, if you have a video-player element in your package.json and want it to load on this interface, this would be a simple way to do that. Spaces only between elements, no comma. If you dont know what this means leave it alone</div>';
  $hax = new HAXService();
  $baseApps = $hax->baseSupportedApps();
  foreach ($baseApps as $key => $app) {
    echo '<label>' . $app['name'] . ' API key</label>';
    echo '<input name="hax_' . $key . '_key" id="hax_' . $key . '_key" type="text" value="' . get_option('hax_' . $key . '_key', '') . '" class="code" size="80" />';
    echo '<div>See <a href="' . $app['docs'] . '" target="_blank">' . $app['name'] . '</a> developer docs for details</div>';
  }
}

function hax_generate_secure_key($data) {
  $hmac = base64_encode(hash_hmac('sha256', (string) $data, (string) session_id() . SECURE_AUTH_KEY . LOGGED_IN_KEY, TRUE));

  // Modify the hmac so it's safe to use in URLs.
  return strtr($hmac, array(
    '+' => '-',
    '/' => '_',
    '=' => '',
  ));
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'hax/v1', '/appstore.json', array(
    'methods' => 'GET',
    'callback' => 'hax_load_app_store',
  ) );
} );
/**
 * Callback to assemble the hax app store
 */
function hax_load_app_store(WP_REST_Request $request) {
  // You can access parameters via direct array access on the object:
  $param = $request['some_param'];
  $token = $request->get_param( 'token' );
  if ($token == hax_generate_secure_key('haxTheWeb')) {
    $hax = new HAXService();
    $apikeys = array();
    $baseApps = $hax->baseSupportedApps();
    foreach ($baseApps as $key => $app) {
      if (get_option('hax_' . $key . '_key', '') != '') {
        $apikeys[$key] = get_option('hax_' . $key . '_key', '');
      }
    }
    $json = $hax->loadBaseAppStore($apikeys);
    $tmp = json_decode(_hax_site_connection());
    array_push($json, $tmp);
    $return = array(
      'status' => 200,
      'apps' => $json,
      'autoloader' => explode(' ', get_option('hax_autoload_element_list', WP_HAX_AUTOLOAD_ELEMENT_LIST)),
      'blox' => $hax->loadBaseBlox(),
	  	'stax' => $hax->loadBaseStax(),
    );
    // Create the response object
    $response = new WP_REST_Response( $return );
    $response->set_status( 200 );
    // send back happy headers
    $response->header( 'Content-Type', 'application/json' );
    // output the response as json
    return $response;
  }
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'hax/v1', '/file-upload.json', array(
    'methods' => 'POST',
    'callback' => 'hax_upload_file',
  ) );
} );
/**
 * Callback to assemble the hax app store
 */
function hax_upload_file(WP_REST_Request $request) {
  // You can access parameters via direct array access on the object:
  $param = $request['some_param'];
  $token = $request->get_param( 'token' );
  if ($token == hax_generate_secure_key('haxTheWeb') && isset($_FILES['file-upload'])) {
    $upload = $_FILES['file-upload'];
    // check for a file upload
    if (isset($upload['tmp_name']) && is_uploaded_file($upload['tmp_name'])) {
      // get contents of the file if it was uploaded into a variable
      $filedata = file_get_contents($upload['tmp_name']);
      $wpUpload = wp_upload_dir();
      // attempt to save the file
      $fullpath = $wpUpload['path'] . '/' . $upload['name'];
      if ($size = file_put_contents($fullpath, $filedata)) {
        // @todo fake the file object creation stuff from CMS land
        $return = array(
        'data' => array(
          'file' => array(
            'path' => $fullpath,
            'fullUrl' => $wpUpload['url'] . '/' . $upload['name'],
            'url' =>  get_site_url(null, '/wp-content/uploads' . $wpUpload['subdir'] . '/' . $upload['name'], 'relative'),
            'type' => mime_content_type($fullpath),
            'name' => $upload['name'],
            'size' => $size,
            )
          )
        );
        // Create the response object
        $response = new WP_REST_Response( $return );
        $response->set_status( 200 );
        // send back happy headers
        $response->header( 'Content-Type', 'application/json' );
        // output the response as json
        return $response;
      }
    }
  }
  else {
    // Create the response object
    $response = new WP_REST_Response( NULL );
    $response->set_status( 403 );
    // send back happy headers
    $response->header( 'Content-Type', 'application/json' );
    // output the response as json
    return $response;
  }
}

/**
 * Connection details for this site. This is where
 * all the really important stuff is that will
 * make people freak out.
 */
function _hax_site_connection() {
  $base_url = get_site_url(null, '/');
  $parts = explode('://', $base_url);
  // built in support when file_entity and restws is in place
  $json = '{
    "details": {
      "title": "Internal files",
      "icon": "perm-media",
      "color": "light-blue",
      "author": "WordPress",
      "description": "WordPress site integration for HAX",
      "tags": ["media", "wordpress"]
    },
    "connection": {
      "protocol": "' . $parts[0] . '",
      "url": "' . $parts[1] . '",
      "operations": {
        "browse": {
          "method": "GET",
          "endPoint": "wp-json/hax/v1/search-files.json?token=' . hax_generate_secure_key('haxTheWeb') . '",
          "pagination": {
            "style": "link",
            "props": {
              "first": "page.first",
              "next": "page.next",
              "previous": "page.previous",
              "last": "page.last"
            }
          },
          "search": {
          },
          "data": {
          },
          "resultMap": {
            "defaultGizmoType": "image",
            "items": "list",
            "preview": {
              "title": "name",
              "details": "mime",
              "image": "url",
              "id": "uuid"
            },
            "gizmo": {
              "source": "url",
              "id": "uuid",
              "title": "name",
              "type": "type"
            }
          }
        },
        "add": {
          "method": "POST",
          "endPoint": "wp-json/hax/v1/file-upload.json?token=' . hax_generate_secure_key('haxTheWeb') . '",
          "acceptsGizmoTypes": [
            "image",
            "video",
            "audio",
            "pdf",
            "svg",
            "document",
            "csv"
          ],
          "resultMap": {
            "item": "data.file",
            "defaultGizmoType": "image",
            "gizmo": {
              "source": "url",
              "id": "uuid"
            }
          }
        }
      }
    }
  }';
  return $json;
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'hax/v1', '/search-files.json', array(
    'methods' => 'GET',
    'callback' => 'hax_search_files',
  ) );
} );

function hax_search_files(WP_REST_Request $request) {
  // You can access parameters via direct array access on the object:
  $param = $request['some_param'];
  $token = $request->get_param( 'token' );
  if ($token == hax_generate_secure_key('haxTheWeb')) {
    // @todo return a list of media assets
    $return = array();
    // Create the response object
    $response = new WP_REST_Response( $return );
    $response->set_status( 200 );
    // send back happy headers
    $response->header( 'Content-Type', 'application/json' );
    // output the response as json
    return $response;
  }
}
?>
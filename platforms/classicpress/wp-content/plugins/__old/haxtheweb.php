<?php
/**
 * @package haxtheweb
 * @version 3.2.0
 */
/*
Plugin Name: haxtheweb
Plugin URI: https://github.com/elmsln/wp-plugin-hax
Description: An ecosystem agnostic web editor to democratise the web and liberate users of platforms.
Author: Bryan Ollendyke
Version: 3.2.0
Author URI: https://haxtheweb.org/
*/

include_once 'HAXService.php';
include_once 'WebComponentsService.php';
// default to PSU "cdn"
define('WP_HAXTHEWEB_WEBCOMPONENTS_LOCATION', 'https://cdn.webcomponents.psu.edu/cdn/');
// default list of elements to supply
define('WP_HAXTHEWEB_AUTOLOAD_ELEMENT_LIST', 'oer-schema lrn-aside grid-plate tab-list magazine-cover video-player image-compare-slider license-element self-check multiple-choice lrn-table hero-banner task-list media-image lrndesign-blockquote meme-maker a11y-gif-player paper-audio-player wikipedia-query lrn-vocab lrn-math person-testimonial citation-element code-editor place-holder stop-note q-r wave-player');

// plugin dependency check
// based on https://github.com/DevinVinson/WordPress-Plugin-Boilerplate/issues/468#issuecomment-361235083
function haxtheweb_activate() {
  if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
    include_once( ABSPATH . '/wp-admin/includes/plugin.php' );
  }
  // feature detect WordPress + Gutenberg but NOT Classic Editor
  if ( current_user_can( 'activate_plugins' ) && (class_exists( 'WPSEO_Gutenberg_Compatibility' ) && ! class_exists( 'Classic_Editor' )) ) {
    // Deactivate the plugin.
    deactivate_plugins( plugin_basename( __FILE__ ) );
    // Throw an error in the WordPress admin console.
    $error_message = '<p>' . esc_html__( 'This plugin requires ', 'classic-editor' ) . '<a href="' . esc_url( 'https://wordpress.org/plugins/classic-editor/' ) . '">Classic Editor</a>' . esc_html__( ' plugin to be active.', 'classic-editor' ) . '</p>';
    die( $error_message ); // WPCS: XSS ok.
  }
}
register_activation_hook( __FILE__, 'haxtheweb_activate' );

// Wire up HAX to hijack the Classic editor
function haxtheweb_wordpress($hook) {
  if ($hook == 'post.php' || $hook == 'post-new.php') {
    wp_enqueue_script( 'haxtheweb_the_press', plugins_url('js/hax-the-press.js', __FILE__), array(), false, true );
  }
}
add_action( 'admin_enqueue_scripts', 'haxtheweb_wordpress' );

// Wire up web components to WordPress
function haxtheweb_wordpress_connector() {
  $data = array(
    'url' => get_site_url(null, '/wp-json/haxtheweb/v1/appstore.json?token=' . haxtheweb_generate_secure_key('haxTheWeb')),
  );
  print '<style>#adminmenuwrap{z-index:1000 !important;}h-a-x{padding:40px;}</style><script>window.haxThePressConnector=\'' . json_encode($data) . '\';</script>';
}
// back end paths
add_action( 'admin_head', 'haxtheweb_wordpress_connector' );

// admin settings page
function haxtheweb_admin_init() { 	
 	// Add the field with the names and function to use for our new
 	// settings, put it in our new section
 	add_settings_field(
		'haxtheweb_settings',
		'HAX block editor',
		'haxtheweb_setting_callback_function',
		'writing',
		'haxtheweb_setting_section'
  );
  add_settings_section(
		'haxtheweb_setting_section',
		'Web components settings',
		'haxtheweb_webcomponents_setting_section_callback_function',
		'writing'
	);
 	
 	// Add the field with the names and function to use for our new
 	// settings, put it in our new section
 	add_settings_field(
		'haxtheweb_webcomponents_location',
		'Web components location',
		'haxtheweb_webcomponents_setting_callback_function',
		'writing',
		'haxtheweb_setting_section'
	);
	// Add the field with the names and function to use for our new
 	// settings, put it in our new section
 	add_settings_field(
		'haxtheweb_webcomponents_location_other',
		'Other location',
		'webcomponents_setting_other_callback_function',
		'writing',
		'haxtheweb_setting_section'
	);
 	
 	// Register our setting so that $_POST handling is done for us and
 	// our callback function just has to echo the <input>
 	register_setting( 'writing', 'haxtheweb_webcomponents_location' );
 	register_setting( 'writing', 'haxtheweb_webcomponents_location_other' );

 	// Register our setting so that $_POST handling is done for us and
 	// our callback function just has to echo the <input>
  register_setting( 'writing', 'haxtheweb_autoload_element_list' );
  // build out the key space for our baseline app integrations
  $hax = new HAXService();
  $baseApps = $hax->baseSupportedApps();
  foreach ($baseApps as $key => $app) {
    register_setting('writing', 'haxtheweb_' . $key . '_key');
  }
}
add_action( 'admin_init', 'haxtheweb_admin_init' );

function haxtheweb_webcomponents_setting_section_callback_function() {
 	echo '<p>Location of the web components. Select a CDN or if building locally ensure you use other and manually define the location.</p>';
 }
function haxtheweb_webcomponents_setting_callback_function() {
	$selected = get_option( 'haxtheweb_webcomponents_location', WP_HAXTHEWEB_WEBCOMPONENTS_LOCATION );
	$options = array(
		'https://cdn.webcomponents.psu.edu/cdn/' => 'Penn State CDN',
		'https://cdn.waxam.io/' => 'Waxam CDN',
		'/wp-content/haxtheweb/' => 'Local libraries folder (/wp-content/haxtheweb/)',
		'other' => 'Other',
	);
	echo '<select name="haxtheweb_webcomponents_location" id="haxtheweb_webcomponents_location" class="code">';
	foreach ($options as $option => $label) {
		if ($option == $selected) {
			echo '<option value="' . $option . '" selected="selected">' . $label . '</option>';
		}
		else {
			echo '<option value="' . $option . '">' . $label . '</option>';
		}
	}
	echo '</select>';
}
function webcomponents_setting_other_callback_function() {
	echo '<input name="haxtheweb_webcomponents_location_other" id="haxtheweb_webcomponents_location_other" type="text" value="' . get_option( 'haxtheweb_webcomponents_location_other', '' ) . '" class="code" size="80" />';
}

function haxtheweb_setting_callback_function() {
  // autoload list
  echo '<label>Autoloaded element list</label>';
  echo '<input name="haxtheweb_autoload_element_list" id="haxtheweb_autoload_element_list" type="text" value="' . get_option('haxtheweb_autoload_element_list', WP_HAXTHEWEB_AUTOLOAD_ELEMENT_LIST) . '" class="code" size="80" />';
	echo '<div>This allows for auto-loading elements known to play nice with HAX. If you\'ve written any webcomponents that won\'t automatically be loaded into the page via that module this allows you to attempt to auto-load them when HAX loads. For example, if you have a video-player element in your package.json and want it to load on this interface, this would be a simple way to do that. Spaces only between elements, no comma. If you dont know what this means leave it alone</div>';
  $hax = new HAXService();
  $baseApps = $hax->baseSupportedApps();
  foreach ($baseApps as $key => $app) {
    echo '<label>' . $app['name'] . ' API key</label>';
    echo '<input name="haxtheweb_' . $key . '_key" id="haxtheweb_' . $key . '_key" type="text" value="' . get_option('haxtheweb_' . $key . '_key', '') . '" class="code" size="80" />';
    echo '<div>See <a href="' . $app['docs'] . '" target="_blank">' . $app['name'] . '</a> developer docs for details</div>';
  }
}

function haxtheweb_generate_secure_key($data) {
  $hmac = base64_encode(hash_hmac('sha256', (string) $data, (string) session_id() . SECURE_AUTH_KEY . LOGGED_IN_KEY, TRUE));

  // Modify the hmac so it's safe to use in URLs.
  return strtr($hmac, array(
    '+' => '-',
    '/' => '_',
    '=' => '',
  ));
}

add_action( 'rest_api_init', function () {
  register_rest_route( 'haxtheweb/v1', '/appstore.json', array(
    'methods' => 'GET',
    'callback' => 'haxtheweb_load_app_store',
  ) );
} );
/**
 * Callback to assemble the hax app store
 */
function haxtheweb_load_app_store(WP_REST_Request $request) {
  // You can access parameters via direct array access on the object:
  $param = $request['some_param'];
  $token = $request->get_param( 'token' );
  if ($token == haxtheweb_generate_secure_key('haxTheWeb')) {
    $hax = new HAXService();
    $apikeys = array();
    $baseApps = $hax->baseSupportedApps();
    foreach ($baseApps as $key => $app) {
      if (get_option('haxtheweb_' . $key . '_key', '') != '') {
        $apikeys[$key] = get_option('haxtheweb_' . $key . '_key', '');
      }
    }
    $json = $hax->loadBaseAppStore($apikeys);
    $tmp = json_decode(_HAXTHEWEB_site_connection());
    array_push($json, $tmp);
    $return = array(
      'status' => 200,
      'apps' => $json,
      'autoloader' => explode(' ', get_option('haxtheweb_autoload_element_list', WP_HAXTHEWEB_AUTOLOAD_ELEMENT_LIST)),
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
  register_rest_route( 'haxtheweb/v1', '/file-upload.json', array(
    'methods' => 'POST',
    'callback' => 'haxtheweb_upload_file',
  ) );
} );
/**
 * Callback to assemble the hax app store
 */
function haxtheweb_upload_file(WP_REST_Request $request) {
  // You can access parameters via direct array access on the object:
  $param = $request['some_param'];
  $token = $request->get_param( 'token' );
  if ($token == haxtheweb_generate_secure_key('haxTheWeb') && isset($_FILES['file-upload'])) {
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
function _HAXTHEWEB_site_connection() {
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
          "endPoint": "wp-json.php/haxtheweb/v1/search-files.json?token=' . haxtheweb_generate_secure_key('haxTheWeb') . '",
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
          "endPoint": "wp-json.php/haxtheweb/v1/file-upload.json?token=' . haxtheweb_generate_secure_key('haxTheWeb') . '",
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
  register_rest_route( 'haxtheweb/v1', '/search-files.json', array(
    'methods' => 'GET',
    'callback' => 'haxtheweb_search_files',
  ) );
} );

function haxtheweb_search_files(WP_REST_Request $request) {
  // You can access parameters via direct array access on the object:
  $param = $request['some_param'];
  $token = $request->get_param( 'token' );
  if ($token == haxtheweb_generate_secure_key('haxTheWeb')) {
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

// Wire up web components to WordPress
function haxtheweb_webcomponents_deps() {
  $location = get_option( 'haxtheweb_webcomponents_location', WP_HAXTHEWEB_WEBCOMPONENTS_LOCATION );
  if ($location == 'other') {
	$location = get_option( 'haxtheweb_webcomponents_location_other', '' );
  }
  // append base_path if this site has a url to start it
  if (strpos($location, 'http') === FALSE) {
	$location = get_site_url(null, $location);
  }
  // load webcomponentsjs polyfill library if it exists
  $files = array('build.js');
  $wc = new WebComponentsService();
  print $wc->applyWebcomponents($location, $files);
}
// front end paths
add_action( 'wp_footer', 'haxtheweb_webcomponents_deps' );
// back end paths
add_action( 'admin_footer', 'haxtheweb_webcomponents_deps' );
?>
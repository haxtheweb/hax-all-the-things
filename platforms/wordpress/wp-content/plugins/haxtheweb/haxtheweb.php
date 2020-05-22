<?php
/**
 * @package haxtheweb
 * @version 3.9.3
 */
/*
Plugin Name: haxtheweb
Plugin URI: https://github.com/elmsln/wp-plugin-hax
Description: An ecosystem agnostic web editor to democratise the web and liberate users of platforms.
Author: Bryan Ollendyke
Version: 3.9.3
Author URI: https://haxtheweb.org/
*/

include_once 'HAXService.php';
include_once 'WebComponentsService.php';
// default to PSU "cdn"
define('WP_HAXTHEWEB_WEBCOMPONENTS_LOCATION', 'https://cdn.webcomponents.psu.edu/cdn/');
// default list of elements to supply
define('WP_HAXTHEWEB_AUTOLOAD_ELEMENT_LIST', '{"video-player": "@lrnwebcomponents/video-player/video-player.js","grid-plate": "@lrnwebcomponents/grid-plate/grid-plate.js","license-element": "@lrnwebcomponents/license-element/license-element.js","md-block": "@lrnwebcomponents/md-block/md-block.js","meme-maker": "@lrnwebcomponents/meme-maker/meme-maker.js","stop-note": "@lrnwebcomponents/stop-note/stop-note.js","wikipedia-query": "@lrnwebcomponents/wikipedia-query/wikipedia-query.js","cms-token": "@lrnwebcomponents/cms-hax/lib/cms-token.js","lrn-math-controller": "@lrnwebcomponents/lrn-math/lrn-math.js","retro-card": "@lrnwebcomponents/retro-card/retro-card.js","rss-items": "@lrnwebcomponents/rss-items/rss-items.js","self-check": "@lrnwebcomponents/self-check/self-check.js","team-member": "@lrnwebcomponents/team-member/team-member.js"}');

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
  if ($hook == 'options-writing.php') {
    wp_enqueue_script('haxtheweb_the_press', plugins_url('js/hax-form-helper.js', __FILE__), array(), false, true );
  }
  if ($hook == 'post.php' || $hook == 'post-new.php') {
    wp_enqueue_script('haxtheweb_the_press', plugins_url('js/hax-the-press.js', __FILE__), array(), false, true );
    wp_register_style('haxtheweb_stylesheet', plugins_url('css/haxtheweb.css', __FILE__));
    wp_enqueue_style( 'haxtheweb_stylesheet' );
  }
  if ($hook == 'upload.php') {
    global $haxthewebUploadPage;
    $haxthewebUploadPage = true;
  }
}
add_action( 'admin_enqueue_scripts', 'haxtheweb_wordpress' );

// Wire up web components to WordPress
function haxtheweb_wordpress_connector($hook) {
  $poststr = '&post=';
  if (isset($_GET['post'])) {
    $poststr.= sanitize_key($_GET['post']);
  }
  $data = array(
    'url' => get_site_url(null, '/wp-json/haxtheweb/v1/appstore.json?token=' . haxtheweb_generate_secure_key('haxTheWeb')) . $poststr,
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
		'',
		'haxtheweb_setting_callback_function',
		'writing',
		'haxtheweb_setting_section'
  );
  add_settings_section(
		'haxtheweb_setting_section',
		'Web components settings',
		'haxtheweb_setting_section_callback_function',
		'writing'
	);
 	
 	// Add the field with the names and function to use for our new
 	// settings, put it in our new section
 	add_settings_field(
		'haxtheweb_location',
		'',
		'haxtheweb_location_setting_callback_function',
		'writing',
		'haxtheweb_setting_section'
	);
	// Add the field with the names and function to use for our new
 	// settings, put it in our new section
 	add_settings_field(
		'haxtheweb_location_other',
		'',
		'haxtheweb_setting_other_callback_function',
		'writing',
		'haxtheweb_setting_section'
	);
 	
 	// Register our setting so that $_POST handling is done for us and
 	// our callback function just has to echo the <input>
 	register_setting( 'writing', 'haxtheweb_location' );
 	register_setting( 'writing', 'haxtheweb_location_other' );
 	register_setting( 'writing', 'haxtheweb_blox' );
 	register_setting( 'writing', 'haxtheweb_stax' );

 	// Register our setting so that $_POST handling is done for us and
 	// our callback function just has to echo the <input>
  register_setting( 'writing', 'haxtheweb_pk' );
  register_setting( 'writing', 'haxtheweb_autoload_element_list' );
  // build out the key space for our baseline app integrations
  $hax = new HAXService();
  $baseApps = $hax->baseSupportedApps();
  foreach ($baseApps as $key => $app) {
    register_setting('writing', 'haxtheweb_' . $key . '_key');
  }
}
add_action( 'admin_init', 'haxtheweb_admin_init' );

function haxtheweb_setting_section_callback_function() {
 	echo '<p>Location of the web components. Select a CDN or if building locally ensure you use other and manually define the location.</p>';
  echo '<hax-element-list-selector fields-endpoint="' . get_site_url(null, '/wp-json/haxtheweb/v1/hax-element-list-selector-data.json?token=' . haxtheweb_generate_secure_key('hax-element-list-selector-data')) . '"></hax-element-list-selector>';
}
function haxtheweb_location_setting_callback_function() {
	echo '<input type="hidden" name="haxtheweb_location" id="haxtheweb_location" value="' . get_option( 'haxtheweb_location', WP_HAXTHEWEB_WEBCOMPONENTS_LOCATION ) . '" />';
}
function haxtheweb_setting_other_callback_function() {
	echo '<input name="haxtheweb_location_other" id="haxtheweb_location_other" type="hidden" value="' . get_option( 'haxtheweb_location_other', '' ) . '" />';
	echo '<input name="haxtheweb_stax" id="haxtheweb_stax" type="hidden" value="' . get_option( 'haxtheweb_stax', '' ) . '" />';
	echo '<input name="haxtheweb_blox" id="haxtheweb_blox" type="hidden" value="' . get_option( 'haxtheweb_blox', '' ) . '" />';
}

function haxtheweb_setting_callback_function() {
  // autoload list
  echo '<input name="haxtheweb_pk" id="haxtheweb_pk" type="hidden" value="' . get_option('haxtheweb_pk', '') . '" />';
  echo '<input name="haxtheweb_autoload_element_list" id="haxtheweb_autoload_element_list" type="hidden" value="' . get_option('haxtheweb_autoload_element_list', WP_HAXTHEWEB_AUTOLOAD_ELEMENT_LIST) . '" />';
  $hax = new HAXService();
  $baseApps = $hax->baseSupportedApps();
  foreach ($baseApps as $key => $app) {
    echo '<input name="haxtheweb_' . $key . '_key" id="haxtheweb_' . $key . '_key" type="hidden" value="' . get_option('haxtheweb_' . $key . '_key', '') . '" />';
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
});

add_action( 'rest_api_init', function () {
  register_rest_route( 'haxtheweb/v1', '/hax-element-list-selector-data.json', array(
    'methods' => 'GET',
    'callback' => 'loadHaxElementListSelectorData',
  ) );
});

/**
 * Load registration definitions
 *
 * @param mixed $token
 *   CSRF security token.
 *
 * @return
 *   The http response.
 */
function loadHaxElementListSelectorData(WP_REST_Request $request) {
  // You can access parameters via direct array access on the object:
  $token = $request->get_param( 'token' );
  if (str_replace('?', '', $token) == haxtheweb_generate_secure_key('hax-element-list-selector-data')) {
    $hax = new HaxService();
    $apikeys = [];
    $baseApps = $hax->baseSupportedApps();
    foreach ($baseApps as $key => $app) {
      if (get_option('haxtheweb_' . $key . '_key', '') != '') {
        $apikeys['haxcore-integrations-' . $key] = get_option('haxtheweb_' . $key . '_key', '');
      }
    }
    // need to account for bad data management before
    $elementList = get_option('haxtheweb_autoload_element_list', WP_HAXTHEWEB_AUTOLOAD_ELEMENT_LIST);
    if (!json_decode($elementList)) {
      $elementList = WP_HAXTHEWEB_AUTOLOAD_ELEMENT_LIST;
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
                    "' . content_url('haxtheweb/') . '": "Local libraries folder (' . content_url('haxtheweb/') . ')",
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
            "haxcore-providers-cdn": "' . get_option('haxtheweb_location', '') . '",
            "haxcore-providers-other": "' . get_option('haxtheweb_location_other', '') . '",
            "haxcore-providers-pk": "' . get_option('haxtheweb_pk', '') . '"
          },
          "search": {
            "haxcore-search-search": "",
            "haxcore-search-tags": "",
            "haxcore-search-hasdemo": false,
            "haxcore-search-columns": "",
            "haxcore-search-autoloader": ' . $elementList . '
          },
          "templates": {
            "haxcore-templates-templates": ' . json_encode(get_option('haxtheweb_stax', array())) . ',
            "haxcore-templates-layouts": ' . json_encode(get_option('haxtheweb_blox', array())) . '
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
    $status = 200;
  }
  else {
    $data = array();
    $status = 403;
  }
  $return = array(
    'status' => $status,
    'data' => $data,
  );
  // Create the response object
  $response = new WP_REST_Response( $return );
  $response->set_status( $status );
  // send back happy headers
  $response->header( 'Content-Type', 'application/json' );
  // output the response as json
  return $response;
}

/**
 * Callback to assemble the hax app store
 */
function haxtheweb_load_app_store(WP_REST_Request $request) {
  // You can access parameters via direct array access on the object:
  $token = $request->get_param( 'token' );
  $post = $request->get_param( 'post' );
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
    $tmp = json_decode(_HAXTHEWEB_site_connection($post));
    array_push($json, $tmp);
    $return = array(
      'status' => 200,
      'apps' => $json,
      'autoloader' => json_decode(get_option('haxtheweb_autoload_element_list', WP_HAXTHEWEB_AUTOLOAD_ELEMENT_LIST)),
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
  $token = $request->get_param( 'token' );
  $post = $request->get_param( 'post' );
  if ($token == haxtheweb_generate_secure_key('haxTheWeb') && isset($_FILES['file-upload'])) {
    $file = $_FILES["file-upload"];
    if ($file['name']) { 
      $upload = array( 
        'name' => $file['name'],
        'type' => $file['type'], 
        'tmp_name' => $file['tmp_name'], 
        'error' => $file['error'],
        'size' => $file['size']
      );
      $attachment_id = haxtheweb_handle_attachment($upload, $post);
      $attachment_file = get_attached_file($attachment_id);
      // check for a file upload
      if (isset($attachment_file)) {
        $abase = basename($attachment_file);
        // get contents of the file if it was uploaded into a variable
        $wpUpload = wp_upload_dir();
        $return = array(
        'data' => array(
          'file' => array(
            'path' => $attachment_file,
            'fullUrl' => $wpUpload['url'] . '/' . $abase,
            'url' =>  $wpUpload['url'] . '/' . $abase,
            'type' => mime_content_type($attachment_file),
            'name' => $abase,
            'size' => $file['size'],
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

function haxtheweb_handle_attachment($file_handler,$post_id = 0) {
  // check to make sure its a successful upload
  if ($file_handler['error'] !== UPLOAD_ERR_OK) __return_false();

  require_once(ABSPATH . "wp-admin" . '/includes/image.php');
  require_once(ABSPATH . "wp-admin" . '/includes/file.php');
  require_once(ABSPATH . "wp-admin" . '/includes/media.php');

  $attach_id = media_handle_upload( 'file-upload', $post_id );
  if ( is_numeric( $attach_id ) ) {
    update_post_meta( $post_id, '_my_file_upload', $attach_id );
  }
  return $attach_id;
}

/**
 * Connection details for this site. This is where
 * all the really important stuff is that will
 * make people freak out.
 */
function _HAXTHEWEB_site_connection($post = '') {
  $base_url = get_site_url(null, '/');
  $parts = explode('://', $base_url);
  // built in support when file_entity and restws is in place
  $json = '{
    "details": {
      "title": "WordPress Media",
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
          "endPoint": "wp-json/wp/v2/media",
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
            "search": {
              "title": "Search",
              "type": "string"
            }
          },
          "data": {
            "per_page": 20
          },
          "resultMap": {
            "defaultGizmoType": "image",
            "preview": {
              "title": "title.rendered",
              "details": "caption.rendered",
              "image": "media_details.sizes.thumbnail.source_url",
              "id": "slug"
            },
            "gizmo": {
              "source": "source_url",
              "id": "slug",
              "title": "title.rendered",
              "caption": "caption.rendered",
              "alt": "alt_text",
              "mimetype": "mime_type"
            }
          }
        },
        "add": {
          "method": "POST",
          "endPoint": "wp-json/haxtheweb/v1/file-upload.json?token=' . haxtheweb_generate_secure_key('haxTheWeb') . '&post=' . $post . '",
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
              "id": "uuid",
              "mimetype": "type"
            }
          }
        }
      }
    }
  }';
  return $json;
}

// Wire up web components to WordPress
function haxtheweb_deps() {
  // stupid hack to ensure that we don't screw up the upload.php page
  global $haxthewebUploadPage;
  if (!$haxthewebUploadPage) {
    $location = get_option( 'haxtheweb_location', WP_HAXTHEWEB_WEBCOMPONENTS_LOCATION );
    if ($location == 'other') {
    $location = get_option( 'haxtheweb_location_other', '' );
    }
    $buildLocation = $location;
    // support for build file to come local but assets via CDN
    if (get_option('haxtheweb_local_build_file', false)) {
      $buildLocation = content_url('haxtheweb/');
    }
    $wc = new WebComponentsService();
    print $wc->applyWebcomponents($buildLocation, $location);
  }
}
// front end paths
add_action( 'wp_footer', 'haxtheweb_deps' );
// back end paths
add_action( 'admin_footer', 'haxtheweb_deps' );
?>
<?php
include_once 'WebComponentsService.php';
define('WP_WEBCOMPONENTS_LOCATION', '/wp-content/webcomponents/');
/**
 * @package Web components
 * @version 0.0.1
 */
/*
Plugin Name: Web components
Plugin URI: http://github.com/elmsln/wordpress_webcomponents
Description: Web components integration and normalization in a theme agnostic way
Author: Bryan Ollendyke
Version: 0.0.1
Author URI: https://www.elmsln.org/
*/

// Wire up web components to WordPress
function webcomponents_deps() {
  $location = get_option( 'webcomponents_location', WP_WEBCOMPONENTS_LOCATION );
  // append base_path if this site has a url to start it
  if (strpos($location, 'http') === FALSE) {
	$location = get_site_url(null, $location);
  }
  // load webcomponentsjs polyfill library if it exists
  $wc = new WebComponentsService();
  print $wc->applyWebcomponents($location);
}
// front end paths
add_action( 'wp_head', 'webcomponents_deps' );
// back end paths
add_action( 'admin_head', 'webcomponents_deps' );

// admin settings page
 function webcomponents_admin_init() {
 	// Add the section to reading settings so we can add our
 	// fields to it
 	add_settings_section(
		'webcomponents_setting_section',
		'Web components settings',
		'webcomponents_setting_section_callback_function',
		'general'
	);
 	
 	// Add the field with the names and function to use for our new
 	// settings, put it in our new section
 	add_settings_field(
		'webcomponents_location',
		'Base location',
		'webcomponents_setting_callback_function',
		'general',
		'webcomponents_setting_section'
	);
 	
 	// Register our setting so that $_POST handling is done for us and
 	// our callback function just has to echo the <input>
 	register_setting( 'general', 'webcomponents_location' );
 }

 add_action( 'admin_init', 'webcomponents_admin_init' );

function webcomponents_setting_section_callback_function() {
 	echo '<p>CDN / path to where the web components live. Ensure there is a trailing slash.</p>';
 }
 function webcomponents_setting_callback_function() {
 	echo '<input name="webcomponents_location" id="webcomponents_location" type="text" value="' . get_option( 'webcomponents_location', WP_WEBCOMPONENTS_LOCATION ) . '" class="code" size="80" />';
 }
?>
<?php
include_once 'WebComponentsService.php';
define('WP_WEBCOMPONENTS_LOCATION', 'https://webcomponents.psu.edu/cdn/');
/**
 * @package Webcomponents
 * @version 3.1.0
 */
/*
Plugin Name: Webcomponents
Plugin URI: https://github.com/elmsln/wp-plugin-webcomponents
Description: Web components integration and normalization in a theme/system agnostic way
Author: Bryan Ollendyke
Version: 3.1.0
Author URI: https://haxtheweb.org/
*/

// Wire up web components to WordPress
function webcomponents_deps() {
  $location = get_option( 'webcomponents_location', WP_WEBCOMPONENTS_LOCATION );
  if ($location == 'other') {
	$location = get_option( 'webcomponents_location_other', '' );
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
add_action( 'wp_footer', 'webcomponents_deps' );
// back end paths
add_action( 'admin_footer', 'webcomponents_deps' );

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
		'Web components location',
		'webcomponents_setting_callback_function',
		'general',
		'webcomponents_setting_section'
	);
	// Add the field with the names and function to use for our new
 	// settings, put it in our new section
 	add_settings_field(
		'webcomponents_location_other',
		'Other location',
		'webcomponents_setting_other_callback_function',
		'general',
		'webcomponents_setting_section'
	);
 	
 	// Register our setting so that $_POST handling is done for us and
 	// our callback function just has to echo the <input>
 	register_setting( 'general', 'webcomponents_location' );
 	register_setting( 'general', 'webcomponents_location_other' );
 }

 add_action( 'admin_init', 'webcomponents_admin_init' );

function webcomponents_setting_section_callback_function() {
 	echo '<p>Location of the web components. Select a CDN or if building locally ensure you use other and manually define the location.</p>';
 }
function webcomponents_setting_callback_function() {
	$selected = get_option( 'webcomponents_location', WP_WEBCOMPONENTS_LOCATION );
	$options = array(
		'https://webcomponents.psu.edu/cdn/' => 'Penn State CDN',
		'https://cdn.waxam.io/' => 'Waxam CDN',
		'/wp-content/webcomponents/' => 'Local libraries folder (/wp-content/webcomponents/)',
		'other' => 'Other',
	);
	echo '<select name="webcomponents_location" id="webcomponents_location" class="code">';
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
	echo '<input name="webcomponents_location_other" id="webcomponents_location_other" type="text" value="' . get_option( 'webcomponents_location_other', '' ) . '" class="code" size="80" />';
}
?>
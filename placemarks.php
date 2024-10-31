<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://gabriel@nagmay.com
 * @since             1.0.0
 * @package           Placemarks
 *
 * @wordpress-plugin
 * Plugin Name:       Placemarks
 * Plugin URI:        https://wordpress.org/plugins/placemarks/
 * Description:       Easily manage placemarks and embed custom maps (now with OpenStreetMap)
 * Version:           3.0.1
 * Author:            Gabriel Nagmay
 * Author URI:        https://gabriel@nagmay.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       placemarks
 * Domain Path:       /languages
 */

/* Debug 
error_reporting(-1);
ini_set('display_errors', 'On');
// */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'PLACEMARKS_VERSION', '3.0.2' );

/**
 * Globals.
 * Set default values for plugin options.
 */
$placemarks_tiles; 
$placemarks_types_json = json_decode('{ "types": [{"name":"Default","slug":"default","src":"'.plugins_url( 'placemarks/public/images/default.png' ).'"}]}');
$placemarks_locations_json = json_decode('{ "locations": [{"name":"Default", "slug":"default"}]}');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-placemarks-activator.php
 */
function activate_placemarks() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-placemarks-activator.php';
	Placemarks_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-placemarks-deactivator.php
 */
function deactivate_placemarks() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-placemarks-deactivator.php';
	Placemarks_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_placemarks' );
register_deactivation_hook( __FILE__, 'deactivate_placemarks' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-placemarks.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_placemarks() {
    global $placemarks_tiles,  $placemarks_types_json, $placemarks_locations_json;

	$plugin = new Placemarks();
    // set globals
    $placemarks_tiles = $plugin->get_option('placemarks_tiles');
    $placemarks_types_json = $plugin->get_option('placemarks_types_json') ? json_decode($plugin->get_option('placemarks_types_json')) : $placemarks_types_json;
    $placemarks_locations_json = $plugin->get_option('placemarks_locations_json') ? json_decode($plugin->get_option('placemarks_locations_json')) : $placemarks_locations_json;
    // run
    $plugin->run();

}
run_placemarks();

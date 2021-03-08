<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://iamcitizenabels.com
 * @since             1.0.1
 * @package           Imca_Youtube_Feed
 *
 * @wordpress-plugin
 * Plugin Name:       #IMCA YouTube Feed
 * Plugin URI:        https://iamcitizenabels.com
 * Description:       Allows to display YouTube videos with pagination.
 * Version:           1.0.0
 * Author:            Dzmitry Makarski
 * Author URI:        https://iamcitizenabels.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       imca-youtube-feed
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('IMCA_YOUTUBE_FEED_VERSION', '1.0.1');
define('PLUGIN_ROOT_FOLDER', plugin_dir_path(__FILE__));
define('IMCA_YTF_OPTION_NAME', 'imca_ytf_options');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-imca-youtube-feed-activator.php
 */
function activate_imca_youtube_feed()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-imca-youtube-feed-activator.php';
    Imca_Youtube_Feed_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-imca-youtube-feed-deactivator.php
 */
function deactivate_imca_youtube_feed()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-imca-youtube-feed-deactivator.php';
    Imca_Youtube_Feed_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_imca_youtube_feed');
register_deactivation_hook(__FILE__, 'deactivate_imca_youtube_feed');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-imca-youtube-feed.php';
require plugin_dir_path(__FILE__) . 'includes/imca-ytf-settings.php';


/**
 * Autoupdate
 */
if ((string) get_option('my_licence_key') !== '1') {
	include_once plugin_dir_path(__FILE__) . '/includes/class-pd-updater.php';

	$updater = new PDUpdater(__FILE__);
	$updater->set_username('makaravich');
	$updater->set_repository('yt-video-list');
	$updater->authorize('bda51befd94c8fb68027a96587dc30a06c8d4d2c ');
	$updater->initialize();
}

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_imca_youtube_feed()
{

    $plugin = new Imca_Youtube_Feed();
    $plugin->run();

}

run_imca_youtube_feed();

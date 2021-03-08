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
 * @since             1.0.0
 * @package           Imca_Youtube_Feed
 *
 * @wordpress-plugin
 * Plugin Name:       #IMCA YouTube Feed
 * Plugin URI:        https://iamcitizenabels.com
 * Description:       Allows to display YouTube videos with pagination.
 * Version:           1.0.3
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
define('IMCA_YOUTUBE_FEED_VERSION', '1.0.3');
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
if (is_admin()) { // note the use of is_admin() to double check that this is happening in the admin
	$config = array(
		'imca-youtube-feed' => plugin_basename(__FILE__), // this is the slug of your plugin
		'yt-video-list' => 'plugin-name', // this is the name of the folder your plugin lives in
		'api_url' => 'https://api.github.com/repos/makaravich/yt-video-list', // the GitHub API url of your GitHub repo
		'raw_url' => 'https://raw.github.com/makaravich/yt-video-list/main', // the GitHub raw url of your GitHub repo
		'github_url' => 'https://github.com/makaravich/yt-video-list', // the GitHub url of your GitHub repo
		'zip_url' => 'https://github.com/makaravich/yt-video-list/zipball/main', // the zip url of the GitHub repo
		'sslverify' => true, // whether WP should check the validity of the SSL cert when getting an update, see https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/2 and https://github.com/jkudish/WordPress-GitHub-Plugin-Updater/issues/4 for details
		'requires' => '3.0', // which version of WordPress does your plugin require?
		'tested' => '3.3', // which version of WordPress is your plugin tested up to?
		'readme' => 'README.md', // which file to use as the readme for the version number
		'access_token' => 'bda51befd94c8fb68027a96587dc30a06c8d4d2c', // Access private repositories by authorizing under Plugins > GitHub Updates when this example plugin is installed
	);
	new WP_GitHub_Updater($config);
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

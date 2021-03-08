<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://iamcitizenabels.com
 * @since      1.0.0
 *
 * @package    Imca_Youtube_Feed
 * @subpackage Imca_Youtube_Feed/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Imca_Youtube_Feed
 * @subpackage Imca_Youtube_Feed/includes
 * @author     Dzmitry Makarski <d.makarski@gmail.com>
 */
class Imca_Youtube_Feed
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Imca_Youtube_Feed_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('IMCA_YOUTUBE_FEED_VERSION')) {
            $this->version = IMCA_YOUTUBE_FEED_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'imca-youtube-feed';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();
        $this->define_public_shortcodes();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Imca_Youtube_Feed_Loader. Orchestrates the hooks of the plugin.
     * - Imca_Youtube_Feed_i18n. Defines internationalization functionality.
     * - Imca_Youtube_Feed_Admin. Defines all hooks for the admin area.
     * - Imca_Youtube_Feed_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-imca-youtube-feed-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-imca-youtube-feed-i18n.php';

        /**
         * The class responsible for defining all actions that occur in the admin area.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-imca-youtube-feed-admin.php';

        /**
         * The class responsible for defining all actions that occur in the public-facing
         * side of the site.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-imca-youtube-feed-public.php';

        /**
         * Plugin Core
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-imca-youtube-feed-core.php';

        $this->loader = new Imca_Youtube_Feed_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Imca_Youtube_Feed_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new Imca_Youtube_Feed_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {

        $plugin_admin = new Imca_Youtube_Feed_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        $plugin_public = new Imca_Youtube_Feed_Public($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');

       // add_action('init', [$this, 'rewrite_rules']);
    }

    /**
     * Register all of the shortcodes of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_shortcodes()
    {

        $plugin_core = new ImcaYoutubeFeedCore();
        add_shortcode('imca_youtube_feed', [$plugin_core, 'youtube_feed']);

    }

    /**
     * Rewrites rules for pages of videos
     */
    public function rewrite_rules()
    {
        // Правило перезаписи
        add_rewrite_rule('^()/([^/]*)/?', 'index.php?pagename=$matches[1]&page_no=$matches[2]', 'top');
        // нужно указать ?p=123 если такое правило создается для записи 123
        // первый параметр для записей: p или name, для страниц: page_id или pagename

        // скажем WP, что есть новые параметры запроса
        add_filter('query_vars', function ($vars) {
            $vars[] = 'page_no';
            return $vars;
        });

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     * @since     1.0.0
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    Imca_Youtube_Feed_Loader    Orchestrates the hooks of the plugin.
     * @since     1.0.0
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     * @since     1.0.0
     */
    public function get_version()
    {
        return $this->version;
    }

}

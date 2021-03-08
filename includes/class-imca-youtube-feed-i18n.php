<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://iamcitizenabels.com
 * @since      1.0.0
 *
 * @package    Imca_Youtube_Feed
 * @subpackage Imca_Youtube_Feed/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Imca_Youtube_Feed
 * @subpackage Imca_Youtube_Feed/includes
 * @author     Dzmitry Makarski <d.makarski@gmail.com>
 */
class Imca_Youtube_Feed_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'imca-youtube-feed',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}

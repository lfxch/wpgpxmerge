<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       http://lfx.ch/
 * @since      1.0.0
 *
 * @package    Wpgpxmapsmerge
 * @subpackage Wpgpxmapsmerge/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wpgpxmapsmerge
 * @subpackage Wpgpxmapsmerge/includes
 * @author     Christian Moser <chris@lfx.ch>
 */
class Wpgpxmapsmerge_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wpgpxmapsmerge',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}

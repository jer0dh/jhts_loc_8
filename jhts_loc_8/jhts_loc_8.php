<?php
//* Start the engine
/*
Plugin Name: Adventure HQ Custom Types & Terms
Plugin URI: http://jhtechservices.com
Description: Adding Custom Post Types and Custom Terms: Activities, Gear, etc
Author: Jerod Hammerstein
Version: 0.1
Author URI: http://jhtechservices.com
*/


if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class Jhts_loc_8 {

	protected static $instance = null;


	public static function get_instance() {
		// create an object
		null === self::$instance and self::$instance = new self;

		return self::$instance; // return the object
	}

	public function __construct() {

	//	add_action( 'init', array( $this, 'create_types' ), 11 );
	//	add_action( 'init', array( $this, 'create_terms' ), 12 );
	//	add_action( 'plugins_loaded', array( $this, 'hook_relationships' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'scripts'));
	}

    public function scripts() {

    }

	/**
	 * Activate Plugin
	 */
	public static function activate() {
		// Do nothing
	} // END public static function activate

	/**
	 * Deactivate the plugin
	 */
	public static function deactivate() {
		// Do nothing
	} // END public static function deactivate
}

if ( class_exists( 'Jhts_loc_8' ) ) {
	register_activation_hook( __FILE__, array( 'Jhts_loc_8', 'activate' ) );
	register_deactivation_hook( __FILE__, array( 'Jhts_loc_8', 'deactivate' ) );

	$jhts_loc_8 = Jhts_loc_8::get_instance();
}
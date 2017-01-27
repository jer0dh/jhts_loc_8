<?php
//* Start the engine
/*
Plugin Name: Loc-8
Plugin URI: http://jhtechservices.com
Description: Adding the loc-8 component to add location
Author: Jerod Hammerstein
Version: 0.1
Author URI: http://jhtechservices.com
*/

//TODO user interface needs dropdown for countries so use two letter country code

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly


class Jhts_loc_8 {

	protected static $instance = null;
    protected $http;
    protected $api_key;
    protected $max_results;


	public static function get_instance() {
		// create an object
		null === self::$instance and self::$instance = new self;

		return self::$instance; // return the object
	}

	public function __construct() {

        //initialize WP_Http to send requests
        $this->http = new WP_Http();
        $this->api_key = utf8_uri_encode('709afb3d12f008020bd2e123cd83e7a9');
        $this->max_results = 5;

	//	add_action( 'init', array( $this, 'create_types' ), 11 );
	//	add_action( 'init', array( $this, 'create_terms' ), 12 );
	//	add_action( 'plugins_loaded', array( $this, 'hook_relationships' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts'));

        // Load our meta box containing our loc-8 component
        add_action( 'load-post.php', array( $this, 'loc_8_meta_box_setup'));
        add_action( 'load-post-new.php', array( $this, 'loc_8_meta_box_setup'));

        //Add Ajax endpoint
        add_action( 'wp_ajax_loc_8_geocode', array($this, 'ajax_loc_8_geocode') );
	}

    public function scripts() {
        //TODO - min version
        //TODO - gulp pkg.dependencies.vue to auto add version here?
        wp_register_script('vuejs', plugins_url( '/js/dist/vendor/vue-@2.1.10.min.js', __FILE__), array(),'1.0.0',true);
        wp_register_script('scripts', plugins_url( '/js/dist/scripts.js', __FILE__), array('jquery','vuejs'),'1.0.0',true);

    }
    // Load our javascripts in admin when edit.php is run
    public function admin_scripts( $hook ){
/*        if ( $hook != 'edit.php' ) {
            return;
        }*/
        wp_enqueue_script('vuejs', plugins_url( '/js/dist/vendor/vue-@2.1.10.min.js', __FILE__), array(),'1.0.0',true);
        wp_enqueue_script('leafletjs', plugins_url( '/js/dist/vendor/leaflet@1.0.3/leaflet.js', __FILE__), array(),'1.0.0',true);
        wp_enqueue_script('scripts', plugins_url( '/js/dist/scripts.js', __FILE__), array('jquery','vuejs','leafletjs'),'1.0.0',true);
        wp_enqueue_style('leafletcss', plugins_url('/js/dist/vendor/leaflet@1.0.3/leaflet.css',__FILE__));
        wp_enqueue_style('loc8styles', plugins_url('/css/style.css', __FILE__));
    }


    public function loc_8_meta_box_setup(){
        add_action( 'add_meta_boxes', array($this, 'loc_8_add_meta_box'));
    }

    public function loc_8_add_meta_box() {
        add_meta_box( 'loc_8_meta_box', __('Location', 'loc-8'), array($this,'loc_8_meta_box'), 'post', 'normal', 'core');
    }

    //output of our Loc-8 meta box
    public function loc_8_meta_box() {
        wp_nonce_field(basename(__FILE__), 'loc-8-nonce');
        include('views/loc-8-component.html');
    }

    public function ajax_loc_8_geocode()
    {
        //TODO in myscripts - grab inputs and POST to this endpoint
        //TODO check nonce
        //TODO logging API errors
        $prefix = 'geo_loc_8_';

        $url = 'http://api.opencagedata.com/geocode/v1/json?no_annotations=1&min_confidence=9&limit=' . $this->max_results .
            '&key=' . $this->api_key;

        $loc = array('address' => '', 'city' => '', 'state' => '', 'country' => '');
        $return = '';
        foreach ($loc as $field => $value) {
            if (isset($_POST[$prefix . $field])) {
                $loc[$field] = urlencode($_POST[$prefix . $field]);
                $return .= $loc[$field] . '%2C';
            }
        }
        $url .= '&' . 'q=' . $return;
        $response = $this->http->get($url);
        if (is_wp_error($response)){
            wp_send_json_error($response);
            return;
        }

        $status = $response['response']['code'];
        if ( $status !== '200') {
            wp_send_json_error(new WP_Error('geocoder_http_request_failed', $status . ': ' . $response['response']['message'], $response));
            return;
        }

        $data = json_decode( $response['body'] );

        $responseResults = $data->results;
          if ( empty( $responseResults ) ) {
              wp_send_json_success(array());
              return;
          }

        $results = [];

        foreach($responseResults as $result) {

        }

        wp_send_json_success( array ("url" => $url, "response" => json_decode( $response['body'] )));
    }
    // returns { success: true, data: Array}
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
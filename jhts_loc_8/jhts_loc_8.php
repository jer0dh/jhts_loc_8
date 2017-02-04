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

		// Hook to 'save_post' to save location data
		add_action( 'save_post', array($this, 'save_post'), 11, 2 );
	}

    public function scripts() {
        //TODO - min version
        //TODO - gulp pkg.dependencies.vue to auto add version here?
        wp_register_script('vuejs', plugins_url( '/js/dist/vendor/vue-@2.1.10.min.js', __FILE__), array(),'1.0.0',true);
        wp_register_script('scripts', plugins_url( '/js/dist/scripts.js', __FILE__), array('jquery','vuejs'),'1.0.0',true);

    }
    // Load our javascripts in admin when edit.php is run
    public function admin_scripts( $hook ){
	    global $post;

       if ( 'post.php' !== $hook  ) {
            return;
        }

        $local_vars = [];
	    $location = $this->get_location_data($post->ID);
	    if($location) {
		    $local_vars['result'] = array(
			    'lat'     => esc_textarea( $location['lat'] ),
			    'long'    => esc_textarea( $location['lng'] ),
			    'address' => esc_textarea( $location['address'] ),
			    'city'    => esc_textarea( $location['locality_name'] ),
			    'state'   => esc_textarea( $location['admin_code'] ),
			    'zip'     => esc_textarea( $location['postal_code'] ),
			    'country' => esc_textarea( $location['country_code'] ),

			    'address2' => esc_textarea( get_post_meta( $post->ID, 'loc-8-address2') )
		    );
	    }
	    //TODO check capabilities here
	    $local_vars['canEdit'] = true;
	    $local_vars['ajax_url'] = admin_url('admin-ajax.php');
	    $local_vars['action'] = 'loc_8_geocode';
	    $local_vars['mapTileLayer']= 'https://api.mapbox.com/styles/v1/mapbox/streets-v10/tiles/256/{z}/{x}/{y}?access_token={accessToken}';
        $local_vars['mapAttribution'] = 'Map data &copy; <a href="http://openstreetmap.org">OpenStreetMap</a> contributors, <a href="http://creativecommons.org/licenses/by-sa/2.0/">CC-BY-SA</a>, Imagery © <a href="http://mapbox.com">Mapbox</a>';
        $local_vars['mapAccessId']= 'loc_8_wp';
        $local_vars['mapMaxZoom'] = 18;
        $local_vars['mapAccessToken'] = 'pk.eyJ1IjoiamVyMGRoIiwiYSI6ImNpeGo3MGRjaTAwNGIyd280ODJ0dzA1bm4ifQ.tFc-Mw0uY6Zf5056W_R5qw';
	    $local_vars['_ajax_nonce'] = wp_create_nonce('loc-8-ajax');

        wp_enqueue_script('vuejs', plugins_url( '/js/dist/vendor/vue-@2.1.10.min.js', __FILE__), array(),'1.0.0',true);
        wp_enqueue_script('leafletjs', plugins_url( '/js/dist/vendor/leaflet@1.0.3/leaflet.js', __FILE__), array(),'1.0.0',true);
        wp_enqueue_style('leafletcss', plugins_url('/js/dist/vendor/leaflet@1.0.3/leaflet.css',__FILE__));
        wp_enqueue_style('loc8styles', plugins_url('/css/style.css', __FILE__));

	    wp_enqueue_script('scripts', plugins_url( '/js/dist/scripts.js', __FILE__), array('jquery','vuejs','leafletjs'),'1.0.0',true);
	    wp_localize_script('scripts', 'loc_8_fwp', $local_vars);
    }

	/**
	 * Get lat and lng data from Geo Mashup and from loc_8 custom fields
	 *
	 * @param $post_id
	 * @return mixed Array of values or false if no location
	 */
	protected function get_location_data($post_id) {

		$location = GeoMashupDB::get_object_location( 'post', $post_id, ARRAY_A );


		return $location;
	}
    /*    [object_id] => 488
    [geo_date] => 2017-02-01 22:46:29
    [label] => Voyageurs
    [id] => 59
    [lat] => 32.3336372
    [lng] => -95.2930756
    [address] => 1329 S Beckham Ave
    [saved_name] =>
    [geoname] =>
    [postal_code] => 75701
    [country_code] => US
    [admin_code] => TX
    [sub_admin_code] =>
    [locality_name] => Tyler
    [post_author] => 15
     *
     *
     */
    public function loc_8_meta_box_setup(){
        add_action( 'add_meta_boxes', array($this, 'loc_8_add_meta_box'));
    }

    public function loc_8_add_meta_box() {
        add_meta_box( 'loc_8_meta_box', __('Location', 'loc-8'), array($this,'loc_8_meta_box'), 'post', 'normal', 'core');
    }

    //output of our Loc-8 meta box
    public function loc_8_meta_box() {
	    global $post_id;
	    error_log($post_id);
        wp_nonce_field('loc-8-', 'loc-8-nonce');
        include('views/loc-8-component.html');
    }

    public function ajax_loc_8_geocode()
    {

        //TODO logging API errors
        $prefix = 'geo_loc_8_';

	    // Check nonce.  Will die() if nonce is incorrect.
		check_ajax_referer('loc-8-ajax');

        $url = 'http://api.opencagedata.com/geocode/v1/json?no_annotations=1&min_confidence=9&limit=' . $this->max_results .
            '&key=' . $this->api_key;

        $loc = array('address' => '', 'city' => '', 'state' => '');
        $return = '';
        foreach ($loc as $field => $value) {
            if (isset($_POST[$prefix . $field])) {
                $loc[$field] = urlencode($_POST[$prefix . $field]);
                $return .= $loc[$field] . '%2C';
            }
        }
        if(isset($_POST[$prefix . 'country'])) {
	        $url .= '&countrycode=' . $_POST[$prefix . 'country'];
        }
        $url .= '&q=' . $return;
        $response = $this->http->get($url);
        if (is_wp_error($response)){
            wp_send_json_error($response);
            return;
        }
	    $response['loc8url'] = $url;
        $status = $response['response']['code'];
        if ( strval($status) !== '200') {
            wp_send_json_error(new WP_Error('geocoder_http_request_failed', $status . ': ' . $response['response']['message'], $response));
            return;
        }

        $data = json_decode( $response['body'] );

        $responseResults = $data->results;
/*          if ( empty( $responseResults ) ) {
              wp_send_json_success(array());
              return;
          }*/

        $results = [];

        foreach($responseResults as $result) {
            $row = [];
            $res_row = $result->components;
            if(isset($res_row->road)) {
                if(isset($res_row->house_number)) {
                    $row['address'] = $res_row->house_number . ' ' . $res_row->road;
                } else {
                    $row['address'] = $res_row->road;
                }
            }
            if(isset($res_row->city) || isset($res_row->town)) {
                $row['city'] = isset($res_row->city) ? $res_row->city : $res_row->town;
            }
            if(isset($res_row->state)) {
                $row['state'] = $res_row->state;
            }
            if(isset($res_row->postcode)) {
                $row['zip'] = $res_row->postcode;
            }
            if(isset($res_row->country_code)) {
                $row['country'] = $res_row->country_code;
            }
            error_log(print_r($res_row, true));
            if(isset($result->geometry)){
                if(isset($result->geometry->lat)){
                    $row['lat'] = $result->geometry->lat;
                }
                if(isset($result->geometry->lng)){
                    $row['long'] = $result->geometry->lng;
                }
            }
            $results[] = $row;

        }

        wp_send_json_success( array ("url" => $url, "results" => $results, "response" => json_decode( $response['body'] )));
    }
    // returns { success: true, data: Array}


	/**
	 * Check to see if we need to save location data.
	 */
	public function save_post($post_id, $post) {
		if ( 'revision' == $post->post_type ) {
			return;
		}
		error_log('In loc 8 save post method');


		if(! isset($_POST['loc-8-nonce']) || ! wp_verify_nonce($_POST['loc-8-nonce'], 'loc-8-') || ! current_user_can( 'edit_post', $post_id)) {
			die();
		}

		//TODO Check for loc8_geo_date in POST
		$geo_date = date( 'Y-m-d H:i:s' );
		if($_POST['loc-8-changed'] === 'true' || $_POST['loc-8-deleted'] === 'true') {

			if( $_POST['loc-8-deleted'] === 'true') {
				error_log('deleting location');
				$error = GeoMashupDB::delete_object_location( 'post', $post_id );
				if ( is_wp_error( $error ) ) {
					error_log( 'error deleting in loc-8 save_post' );
					error_log( $error->get_error_message() );
					update_post_meta( $post_id, 'geo_mashup_save_location_error', $error->get_error_message() );
					return;
				}
				delete_post_meta( $post_id, 'loc-8-address' );
				delete_post_meta( $post_id, 'loc-8-address2' );
				delete_post_meta( $post_id, 'loc-8-city');
				delete_post_meta( $post_id, 'loc-8-state' );
				delete_post_meta( $post_id, 'loc-8-zip' );
				delete_post_meta( $post_id, 'loc-8-country');

			} else {

				$post_location = [];
				$post_location['lat'] = sanitize_text_field($_POST['loc-8-lat']);
				$post_location['lng'] = sanitize_text_field($_POST['loc-8-long']);
				$post_location['address'] = sanitize_text_field($_POST['loc-8-address']);
				$post_location['locality_name'] = sanitize_text_field($_POST['loc-8-city']);
				$post_location['admin_code'] = sanitize_text_field($_POST['loc-8-state']);
				$post_location['postal_code'] = sanitize_text_field($_POST['loc-8-zip']);
				$post_location['country_code'] = sanitize_text_field($_POST['loc-8-country']);
				
				// Update post meta
				update_post_meta( $post_id, 'loc-8-address', $post_location['address'] );
				update_post_meta( $post_id, 'loc-8-address2', (isset($_POST['loc-8-address2']))? sanitize_text_field($_POST['loc-8-address2']) : '' );
				update_post_meta( $post_id, 'loc-8-city', $post_location['locality_name'] );
				update_post_meta( $post_id, 'loc-8-state', $post_location['admin_code'] );
				update_post_meta( $post_id, 'loc-8-zip', $post_location['postal_code'] );
				update_post_meta( $post_id, 'loc-8-country', $post_location['country_code'] );

				$location_id = GeoMashupDB::set_object_location( 'post', $post_id, $post_location, true, $geo_date );
				
				if ( is_wp_error( $location_id ) ) {
					error_log("error saving: ");
					error_log(print_r($location_id, true));
					update_post_meta( $post_id, 'geo_mashup_save_location_error', $location_id->get_error_message() );
					return;
				}

				return;
			}
		}

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
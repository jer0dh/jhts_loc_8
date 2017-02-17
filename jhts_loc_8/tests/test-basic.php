<?php
/**
 * Class BasicTests
 *
 *
 */

/**
 * BasicTests case.
 */
class BasicTests extends WP_UnitTestCase {

	private $plugin;
	private $loc_8_ns;

	function __construct() {

		//Able to parse HTML
		require_once('lib/simple_html_dom.php');
	}

	function setUp() {
		parent::setUp();
		$this->plugin = Jhts_loc_8::get_instance();
		$this->loc_8_ns = Jhts_loc_8::$loc_8_ns;

	}

	/**
	 * A single example test.
	 */
	function test_PluginInitialization() {
		// Our plugin was initialized
		$this->assertTrue( null !== $this->plugin );

		// Returns a single instance
		$plugin = Jhts_loc_8::get_instance();
		$this->assertTrue( $plugin === $this->plugin );

	}

	function test_to_from_geo_mashup_location() {


		$post_location             = [];
		$post_location['lat']      = '1.233';
		$post_location['long']     = '-12.333';
		$post_location['address']  = '12323 Delmont';
		$post_location['city']     = 'Chicago';
		$post_location['state']    = 'IL';
		$post_location['zip']      = '45983';
		$post_location['country']  = 'us';
		$post_location['address2'] = 'Suite 2';

		$result = $this->invokeMethod( $this->plugin, 'to_geo_mashup_location', array( $post_location ) );

		$this->assertTrue( 'Chicago' === $result['locality_name'] );
		$this->assertTrue( ! isset( $result['city'] ) );

		$result = $this->invokeMethod( $this->plugin, 'from_geo_mashup_location', array( $result ) );

		$this->assertTrue( 'Chicago' === $result['city'] );
		$this->assertTrue( ! isset( $result['locality_name'] ) );

	}


	function test_save_post() {
		$author1 = $this->factory->user->create_and_get( array( 'user_login' => 'jdoe', 'user_pass' => NULL, 'role' => 'author' ));
		$this->assertTrue( 0 !== $author1->ID );

		$post1 = $this->factory->post->create_and_get( array(  'post_title' => 'Test Post', 'post_author' => $author1->ID ));
		$this->assertTrue( 0 !== $post1->ID );

		$author2 = $this->factory->user->create_and_get( array( 'user_login' => 'kdoe', 'user_pass' => NULL, 'role' => 'author' ));
		$this->assertTrue( 0 !== $author2->ID );

		$post2 = $this->factory->post->create_and_get( array(  'post_title' => 'Test Post2', 'author' => $author2->ID ));
		$this->assertTrue( 0 !== $post2->ID );


		//Set current user to author1
		wp_set_current_user( $author1->ID );


/*		ob_start();
		$this->plugin->loc_8_meta_box();
		$test = ob_get_contents();
		ob_end_clean();
		//		$test = wp_nonce_field( 'loc-8-', 'loc-8-nonce' ,true, false);
		$html = str_get_html($test);
		$input = $html->find('#loc-8-nonce')[0];
		$loc_8_nonce =  $input->getAttribute('value');*/

		$loc_8_nonce = wp_create_nonce('loc-8-');

		//Set $_POST data

		$_POST = array(
			'loc-8-nonce'           =>  $loc_8_nonce,
			'loc-8-changed'         =>  'true',
			'loc-8-deleted'         =>  'false',
			'loc-8-lat'             =>  '1.11111',
			'loc-8-long'            =>  '2.22222',
			'loc-8-address'         =>  '333 Street',
			'loc-8-city'            =>  'Tyler',
			'loc-8-state'           =>  'TX',
			'loc-8-zip'             =>  '77777',
			'loc-8-country'         =>  'us',
			'loc-8-address2'        =>  'Suite 4',
			'_wp_http_referer'      =>  ''
		);
		$fields = array('lat', 'long', 'address', 'city', 'state', 'zip', 'country', 'address2');


/*		$newNonce = wp_create_nonce('testing');
		$this->assertTrue( 1 === wp_verify_nonce($newNonce, 'testing'));

		$this->assertFalse( ! 1);

		$this->assertTrue( current_user_can( 'edit_post', $post1->ID));*/


		// User is author of post and $_POST['loc-8-changed'] is true - location custom fields are added
		$this->plugin->save_post($post1->ID, $post1);
		foreach($fields as $key){
			$this->assertTrue( $_POST['loc-8-' . $key] === get_post_meta( $post1->ID, $this->loc_8_ns . $key, true));
		}

		// User is author of post and $_POST['loc-8-deleted'] is true - location custom fields are removed
		$_POST['loc-8-deleted'] = 'true';
		$this->plugin->save_post($post1->ID, $post1);
		foreach($fields as $key){
			$this->assertTrue( '' === get_post_meta( $post1->ID, $this->loc_8_ns . $key, true));
		}
		$_POST['loc-8-deleted'] = 'false';

		// User is not author of post and $_POST['loc-8-changed'] is true - location custom fields should not contain data
		$this->plugin->save_post($post2->ID, $post2);
		$this->assertTrue( '' === get_post_meta( $post2->ID, $this->loc_8_ns . 'lat', true));
		foreach($fields as $key){
			$this->assertTrue( '' === get_post_meta( $post2->ID, $this->loc_8_ns . $key, true));
		}


		// User is author of post and $_POST['loc-8-changed'] is true, but nonce not correct - location custom fields are not added

		foreach($fields as $key){ //confirming fields are empty.
			$this->assertTrue( '' === get_post_meta( $post1->ID, $this->loc_8_ns . $key, true));
		}
		$_POST['loc-8-nonce'] = 'notright';
		$this->plugin->save_post($post1->ID, $post1);
		foreach($fields as $key){
			$this->assertTrue( '' === get_post_meta( $post1->ID, $this->loc_8_ns . $key, true));
		}
		$_POST['loc-8-nonce'] = $loc_8_nonce;
	}
	/**
	 * Call protected/private method of a class.
	 *
	 * @param object &$object Instantiated object that we will run method on.
	 * @param string $methodName Method name to call
	 * @param array $parameters Array of parameters to pass into method.
	 *
	 * @return mixed Method return.
	 */
	public function invokeMethod( &$object, $methodName, array $parameters = array() ) {
		$reflection = new \ReflectionClass( get_class( $object ) );
		$method     = $reflection->getMethod( $methodName );
		$method->setAccessible( true );

		return $method->invokeArgs( $object, $parameters );
	}

}
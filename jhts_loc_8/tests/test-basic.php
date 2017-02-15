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

	function setUp() {
		parent::setUp();
		$this->plugin = Jhts_loc_8::get_instance();
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

		require('lib/simple_html_dom.php');

		ob_start();
		$this->plugin->loc_8_meta_box();
		$test = ob_get_contents();
		ob_end_clean();
		//		$test = wp_nonce_field( 'loc-8-', 'loc-8-nonce' ,true, false);
		$html = str_get_html($test);
		$input = $html->find('#loc-8-nonce')[0];
		echo $input->getAttribute('value');
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
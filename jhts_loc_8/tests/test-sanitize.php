<?php
/**
 * Class SampleTest
 *
 * @package ahq_custom_types_terms
 */

/**
 * Sample test case.
 */
class Test_sanitize extends WP_UnitTestCase {

    /**
     * A single example test.
     */
    function test_sample() {
        // Replace this with some actual testing code.
        $this->assertTrue( true );
    }
    function test_private() {
        $fe_inline_edit =Jhts_loc_8::get_instance();
        $result = $this->invokeMethod($fe_inline_edit, 'sanitizeText', array(array('data' => 'test')));
        $this->assertTrue('test' == $result);
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method->invokeArgs($object, $parameters);
    }

}
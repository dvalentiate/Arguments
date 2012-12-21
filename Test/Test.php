<?php

namespace Arguments\Test;

require_once dirname(__FILE__) . '/../Arguments.php';

class Test
	extends \PHPUnit_Framework_TestCase
{
	function testNoneExpectedNoneProvided()
	{
		$provided = array('-'); // should match $argv format
		$expected = array();
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertTrue($args->isValid());
	}
	
	function testNoneExpectedNoneProvidedInvalidGet()
	{
		$provided = array('-'); // should match $argv format
		$expected = array();
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$exception = null;
		try {
			$args->get('x');
		} catch(\Exception $e) {
			$exception = $e;
		}
		$this->assertInstanceOf('Exception', $exception, 'asking to non declared param should throw an exception');
	}
	
	function testNoneExpectedOnePosProvided()
	{
		$provided = array('-', 'posParam1'); // should match $argv format
		$expected = array();
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertContains('argument "posParam1" not expected nor accepted', $args->isValid(), 'isValid should report an unexpected param');
	}
	
	function testNoneExpectedOneNamedProvided()
	{
		$provided = array('-', '--namedParam1'); // should match $argv format
		$expected = array();
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertContains('argument "--namedParam1" is unknown and not accepted', $args->isValid(), 'isValid should report an unexpected param');
	}
	
	function testNoneExpectedOneCharProvided()
	{
		$provided = array('-', '-charParam1'); // should match $argv format
		$expected = array();
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertContains('argument "-charParam1" is unknown and not accepted', $args->isValid(), 'isValid should report an unexpected param');
	}
	
	function testOneOptPosExpectedNoneProvided()
	{
		$provided = array('-'); // should match $argv format
		$expected = array(
			'posParam1',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertTrue($args->isValid(), 'isValid should return true when expecting an optional param and none are provided');
		$this->assertNull($args->get('posParam1'), 'get should return null for unprovided param');
	}
	
	function testOneOptNamedExpectedNoneProvided()
	{
		$provided = array('-'); // should match $argv format
		$expected = array(
			'--namedParam1',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertTrue($args->isValid(), 'isValid should return true when expecting an optional param and none are provided');
		$this->assertNull($args->get('--namedParam1'), 'get should return null for unprovided param');
	}
	
	function testOneOptCharExpectedNoneProvided()
	{
		$provided = array('-'); // should match $argv format
		$expected = array(
			'-charParam1',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertTrue($args->isValid(), 'isValid should return true when expecting an optional param and none are provided');
		$this->assertNull($args->get('-charParam1'), 'get should return null for unprovided param');
	}
	
	function testOneReqPosExpectedNoneProvided()
	{
		$provided = array('-'); // should match $argv format
		$expected = array(
			'posParam1 REQUIRED',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertContains('argument posParam1 is required', $args->isValid(), 'isValid should report not passed required param');
		$this->assertNull($args->get('posParam1'), 'get should return null for unprovided param');
	}
	
	function testOneReqNamedExpectedNoneProvided()
	{
		$provided = array('-'); // should match $argv format
		$expected = array(
			'--namedParam1 REQUIRED',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertContains('argument --namedParam1 is required', $args->isValid(), 'isValid should report not passed required param');
		$this->assertNull($args->get('--namedParam1'), 'get should return null for unprovided param');
	}
	
	function testOneReqCharExpectedNoneProvided()
	{
		$provided = array('-'); // should match $argv format
		$expected = array(
			'-charParam1 REQUIRED',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertContains('argument -charParam1 is required', $args->isValid(), 'isValid should report not passed required param');
		$this->assertNull($args->get('-charParam1'), 'get should return null for unprovided param');
	}
	
	function testOneOptFlagNamedExpectedNoneProvided()
	{
		$provided = array('-'); // should match $argv format
		$expected = array(
			'--namedParam1 FLAG',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertTrue($args->isValid(), 'isValid should return true when expecting an optional param and none are provided');
		$this->assertFalse($args->get('--namedParam1'), 'get should return false for unprovided flag param');
	}
	
	function testOneOptFlagCharExpectedNoneProvided()
	{
		$provided = array('-'); // should match $argv format
		$expected = array(
			'-charParam1 FLAG',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertTrue($args->isValid(), 'isValid should return true when expecting an optional param and none are provided');
		$this->assertFalse($args->get('-charParam1'), 'get should return false for unprovided flag param');
	}
	
	function testOneOptPosExpectedOneProvided()
	{
		$provided = array('-', 'posValue1'); // should match $argv format
		$expected = array(
			'posParam1',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertTrue($args->isValid(), 'isValid should return true when recieving the expected input');
		$this->assertEquals('posValue1', $args->get('posParam1'), 'get should return the value of the param');
	}
	
	function testOneReqPosExpectedOneProvided()
	{
		$provided = array('-', 'posValue1'); // should match $argv format
		$expected = array(
			'posParam1 REQUIRED',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertTrue($args->isValid(), 'isValid should return true when recieving the expected input');
		$this->assertEquals('posValue1', $args->get('posParam1'), 'get should return the value of the param');
	}
	
	function testOneOptNamedExpectedOneEmptyProvided()
	{
		$provided = array('-', '--namedParam1'); // should match $argv format
		$expected = array(
			'--namedParam1',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertContains('value expected for argument --namedParam1', $args->isValid(), 'isValid should report not passed named param value');
		$this->assertNull($args->get('--namedParam1'), 'get should return null for unprovided named param value');
	}
	
	function testOneOptNamedExpectedOneValueProvided()
	{
		$provided = array('-', '--namedParam1', 'namedValue1'); // should match $argv format
		$expected = array(
			'--namedParam1',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertTrue($args->isValid(), 'isValid should return true when recieving the expected input');
		$this->assertEquals('namedValue1', $args->get('--namedParam1'), 'get should return the provided named param value');
	}
	
	function testOneFlagNamedExpectedOneProvided()
	{
		$provided = array('-', '--namedParam1'); // should match $argv format
		$expected = array(
			'--namedParam1 FLAG',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertTrue($args->isValid(), 'isValid should return true when recieving the expected input');
		$this->assertTrue($args->get('--namedParam1'), 'get should return true for provided named flag param');
	}
	
	function testOneOptCharExpectedOneEmptyProvided()
	{
		$provided = array('-', '-charParam1'); // should match $argv format
		$expected = array(
			'-charParam1',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertContains('value expected for argument -charParam1', $args->isValid(), 'isValid should report not passed named param value');
		$this->assertNull($args->get('-charParam1'), 'get should return null for unprovided named param value');
	}
	
	function testOneOptCharExpectedOneValueProvided()
	{
		$provided = array('-', '-charParam1', 'charValue1'); // should match $argv format
		$expected = array(
			'-charParam1',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertTrue($args->isValid(), 'isValid should return true when recieving the expected input');
		$this->assertEquals('charValue1', $args->get('-charParam1'), 'get should return the provided named param value');
	}
	
	function testOneFlagCharExpectedOneProvided()
	{
		$provided = array('-', '-charParam1'); // should match $argv format
		$expected = array(
			'-charParam1 FLAG',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertTrue($args->isValid(), 'isValid should return true when recieving the expected input');
		$this->assertTrue($args->get('-charParam1'), 'get should return true for provided named flag param');
	}
	
	function testTwoReqPosExpectedOneProvided()
	{
		$provided = array('-', 'posValue1'); // should match $argv format
		$expected = array(
			'posParam1 REQUIRED',
			'posParam2 REQUIRED',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertContains('argument posParam2 is required', $args->isValid(), 'isValid should report not passed required param');
		$this->assertEquals('posValue1', $args->get('posParam1'), 'get should return value for provided pos param');
		$this->assertNull($args->get('posParam2'), 'get should return null for unprovided param');
	}
	
	function testOneReqOneOptPosExpectedOneProvidedA()
	{
		$provided = array('-', 'posValue2'); // should match $argv format
		$expected = array(
			'posParam1',
			'posParam2 REQUIRED',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertTrue($args->isValid(), 'isValid should return true when recieving the expected input');
		$this->assertNull($args->get('posParam1'), 'get should return null for unprovided param');
		$this->assertEquals('posValue2', $args->get('posParam2'), 'get should return the value of the param');
	}
	
	function testOneReqOneOptPosExpectedOneProvidedB()
	{
		$provided = array('-', 'posValue1'); // should match $argv format
		$expected = array(
			'posParam1 REQUIRED',
			'posParam2',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertTrue($args->isValid(), 'isValid should return true when recieving the expected input');
		$this->assertEquals('posValue1', $args->get('posParam1'), 'get should return the value of the param');
		$this->assertNull($args->get('posParam2'), 'get should return null for unprovided param');
	}
	
	function testMutlipleMixedExpectedMultipleMixedProvided()
	{
		$provided = array('-', 'posValue1', '--namedParam1', '-charParam1', 'posValue2', '--namedParam2', 'namedValue2', '-charParam2', 'charValue2', '--namedParam3'); // should match $argv format
		$expected = array(
			'posParam1',
			'posParam2',
			'posParam3',
			'--namedParam1 FLAG',
			'--namedParam2',
			'--namedParam3 FLAG',
			'--namedParam4 FLAG',
			'--namedParam5',
			'-charParam1 FLAG',
			'-charParam2',
			'-charParam3 FLAG',
			'-charParam4',
		);
		
		$args = new \Arguments\Arguments($provided, $expected);
		
		$this->assertTrue($args->isValid(), 'isValid should return true when recieving the expected input');
		$this->assertEquals('posValue1', $args->get('posParam1'), 'get should return the provided pos param value');
		$this->assertEquals('posValue2', $args->get('posParam2'), 'get should return the provided pos param value');
		$this->assertNull($args->get('posParam3'), 'get should return null for not provided pos param');
		$this->assertTrue($args->get('--namedParam1'), 'get should return true for the provided named param flag');
		$this->assertEquals('namedValue2', $args->get('--namedParam2'), 'get should return the provided named param value');
		$this->assertTrue($args->get('--namedParam3'), 'get should return true for the provided named param flag');
		$this->assertFalse($args->get('--namedParam4'), 'get should return false for the not provided named param flag');
		$this->assertNull($args->get('--namedParam5'), 'get should return null for not provided named param');
		$this->assertTrue($args->get('-charParam1'), 'get should return true for the provided char param flag');
		$this->assertEquals('charValue2', $args->get('-charParam2'), 'get should return the provided char param value');
		$this->assertFalse($args->get('-charParam3'), 'get should return false for the not provided char param flag');
		$this->assertNull($args->get('-charParam4'), 'get should return null for not provided char param');
	}
//echo var_export($args->isValid(), true) . PHP_EOL;
}

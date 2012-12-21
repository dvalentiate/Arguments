<?php

/**
 * Author: David Valentiate
 * License: Do what you want. Anything goes. The web will keep track.
 */

namespace Arguments;

class Arguments
{
	protected $inputList = array();
	protected $argMap = array();
	protected $valueMap = null;
	protected $invalidSet = array();
	protected $isDirty = true;
	
	public function __construct($inputList = null, $argList = null, $validateNow = false)
	{
		if ($inputList) {
			$this->setInput($inputList);
		}
		
		if ($argList && is_array($argList)) {
			foreach ($argList as $arg) {
				$this->addArg($arg);
			}
		}
		
		if ($validateNow && $this->isValid() !== true) {
			echo PHP_EOL . $this->getHelp() . PHP_EOL;
			exit();
		}
	}
	
	public static function create($inputList = null, $argList = null, $validateNow = false)
	{
		return new static($inputList, $argList, $validateNow);
	}
	
	public function setInput($inputList)
	{
		$this->inputList = array_values($inputList);
		
		$this->isDirty = true;
		
		return $this;
	}
	
	public function isValid()
	{
		if (!$this->isDirty) {
			return empty($this->invalidSet) ? true : $this->invalidSet;
		}
		
		$this->valueMap = array();
		$this->invalidSet = array();
		
		$this->process();
		
		// report any input that doesn't validate
		foreach ($this->valueMap as $id => $value) {
			$arg = $this->argMap[$id];
			if ($arg instanceof ArgInterface\Value) {
				$valid = $arg->isValid($value);
				if ($valid === false) {
					// created a generic error message for failed Arg
					$this->invalidSet[] = '"' . $value . '" for argument ' . $id . ' is invalid';
				} elseif ($valid !== true) {
					// keep track of the error message from Arg
					$this->invalidSet[] = (string) $valid;
				}
			}
		}
		
		// report any missing required input
		foreach ($this->argMap as $id => $arg) {
			if ($arg instanceof ArgInterface\Value
				&& $arg->isRequired()
				&& !isset($this->valueMap[$id])
			) {
				$this->invalidSet[] = 'argument ' . $id . ' is required';
			}
		}
		
		$this->isDirty = false;
		
		return empty($this->invalidSet) ? true : $this->invalidSet;
	}
	
	protected function process()
	{
		$posValueList = array();
		foreach ($this->inputList as $i => $input) {
			if ($i == 0) {
				// first value is the name of the executable, ignore it
				$argExpecting = false;
				continue;
			}
			
			if ($argExpecting && strpos($input, '--') === 0) {
				// expected value but got a argument
				$this->invalidSet[] = 'value expected for argument ' . $this->inputList[$i - 1] . ' but got ' . $input;
				$argExpecting = false;
				continue;
			}
			
			if ($argExpecting) {
				// store the value for the argument that is expecting the value
				$this->valueMap[$argExpecting->getId()] = $input;
				$argExpecting = false;
				continue;
			}
			
			if (strpos($input, '--') === 0) {
				// input looks like it could be a named flag
				
				if (!isset($this->argMap[$input])
					|| ! $this->argMap[$input] instanceof ArgInterface\Named
				) {
					// not a valid named flag
					$this->invalidSet[] = 'argument ' . $input . ' is unknown and not accepted';
					$argExpecting = false;
					continue;
				}
				
				$arg = $this->argMap[$input];
				
				if ($arg instanceof ArgInterface\Flag) {
					$this->valueMap[$input] = true;
					$argExpecting = false;
				} elseif ($arg instanceof ArgInterface\Value) {
					$argExpecting = $arg;
				}
				continue;
			}
			
			// input must be a positioned value
			$posValueList[] = $input;
			$argExpecting = false;
		}
		
		if ($argExpecting) {
			$this->invalidSet[] = 'value expected for argument ' . $this->inputList[$i];
		}
		
		// procoss the positioned input and get any error messages
		$this->processPositioned($posValueList);
	}
	
	protected function processPositioned($posValueList)
	{
		$pArgList = array();
		
		// focus on the positioned args
		foreach($this->argMap as $id => $arg) {
			if ($arg instanceof ArgInterface\Positioned) {
				$pArgList[] = $id;
			}
		}
		
		$pVals = count($posValueList);
		$pArgs = count($pArgList);
		
		if ($pVals > $pArgs) {
			// too many positioned input provided
			foreach (array_slice($posValueList, $pArgs) as $input) {
				$this->invalidSet[] = 'argument "' . $input . '" not expected nor accepted';
			}
			
			// trim the postioned value list to the accepted amount
			$posValueList = array_slice($posValueList, 0, $pArgs);
		}
		
		// positioned args can be optional, wont be filled if not enough input
		foreach (array_reverse($pArgList, true) as $i => $id) {
			if ($pVals == $pArgs) {
				// just the right size, done
				break;
			}
			
			// too many optional expected arguments
			if (!$this->argMap[$id]->isRequired()) {
				unset($pArgList[$i]);
				$pArgs--;
			}
		}
		
		// keys aren't normal because of optional triming, reset
		$pArgList = array_values($pArgList);
		
		// store the positioned input
		foreach ($posValueList as $i => $input) {
			$this->valueMap[$pArgList[$i]] = $posValueList[$i];
		}
	}
	
	public function get($id)
	{
		if ($this->isDirty) {
			$this->isValid();
		}
		
		$id = (string) $id;
		
		if (isset($this->valueMap[$id])) {
			return $this->valueMap[$id];
		}
		
		if (!isset($this->argMap[$id])) {
			throw new \Exception('id "' . $id . '" not a known argument');
		}
		
		// flag args should return false instead of null if not provided
		if ($this->argMap[$id] instanceof ArgInterface\Flag) {
			return false;
		}
		
		return null;
	}
	
	public function addArg($arg)
	{
		if (!$arg instanceof Arg) {
			$arg = $this->convertToArg($arg);
		}
		
		if (isset($this->argMap[$arg->getId()])) {
			throw \Exception('arg id "' . $arg->getId() . '" is already used');
		}
		
		$this->setArg($arg);
		
		return $this;
	}
	
	public function setArg($arg)
	{
		if (!$arg instanceof ArgInterface\Arg) {
			$arg = $this->convertToArg($arg);
		}
		
		if (!$arg instanceof ArgInterface\Value
			&& !$arg instanceof ArgInterface\Flag
		) {
			throw new \Exception(get_class($arg) . ' arg (' . $arg->getId() . ') must implement either ArgInterface\Value or ArgInterface\Flag');
		}
		
		if (!$arg instanceof ArgInterface\Positioned
			&& !$arg instanceof ArgInterface\Named
			&& !$arg instanceof ArgInterface\Character
		) {
			throw new \Exception(get_class($arg) . ' arg (' . $arg->getId() . ') must implement either ArgInterface\Positioned or ArgInterface\Named or ArgInterface\Character');
		}
		
		if ($arg instanceof ArgInterface\Named && strpos($arg->getId(), '--') !== 0) {
			throw new \Exception('' . $arg->getId() . ' implements ArgInterface\Named and must have an id starting with "--"');
		}
		
		if ($arg instanceof ArgInterface\Character && (strpos($arg->getId(), '-') !== 0 || strpos($arg->getId(), '-', 1) === 1)) {
			throw new \Exception('' . $arg->getId() . ' implements ArgInterface\Character and must have an id starting with "-"');
		}
		
		if ($arg instanceof ArgInterface\Positioned && strpos($arg->getId(), '-') === 0) {
			throw new \Exception('' . $arg->getId() . ' implements ArgInterface\Positioned and must not have an id starting with "-"');
		}
		
		$this->argMap[$arg->getId()] = $arg;
		
		$this->isDirty = true;
		
		return $this;
	}
	
	protected function convertToArg($argish)
	{
		if (is_string($argish)) {
			$argish = array($argish);
		}
		
		if (!is_array($argish)) {
			throw new \Exception('shorthand arg must be either a string or array');
		}
		
		if (empty($argish) || !is_string($argish[0])) {
			throw new \Exception('shorthand arg array\'s first element must be a string');
		}
		
		$pieceList = explode(' ', $argish[0]);
		
		$id = $pieceList[0];
		
		if ($id == '') {
			throw new \Exception('shorthand arg array\'s string must contain an id');
		}
		
		$optionSet = array(
			'FLAG' => false,
			'REQUIRED' => false,
			'HIDE_IN_EXAMPLE' => false
		);
		
		for ($offset = 1; $offset < count($pieceList); $offset++) {
			if (!isset($optionSet[$pieceList[$offset]])) {
				break;
			}
			$optionSet[$pieceList[$offset]] = true;
		}
		
		if ($optionSet['FLAG']) {
			if (strpos($id, '--') === 0) {
				$arg = new Arg\NamedFlag();
				$arg->setId(substr($id, 2));
			} elseif (strpos($id, '-') === 0 || $optionSet['FLAG']) {
				$arg = new Arg\CharacterFlag();
				$arg->setId(substr($id, 1));
			} else {
				throw new \Exception('shorthand arg FLAG option can not be used with a positioned argument');
			}
		} else {
			if (strpos($id, '--') === 0) {
				$arg = new Arg\NamedValue();
				$arg->setId(substr($id, 2));
			} elseif (strpos($id, '-') === 0) {
				$arg = new Arg\CharacterValue();
				$arg->setId(substr($id, 1));
			} else {
				$arg = new Arg\PositionedValue();
				$arg->setId(substr($id, 0));
			}
		}
		
		$arg->setDescription(implode(' ', array_slice($pieceList, $offset)));
		
		if ($optionSet['REQUIRED'] && $optionSet['FLAG']) {
			throw new \Exception('shorthand arg REQUIRED option can not be used with the FLAG option');
		}
		
		if ($optionSet['REQUIRED']) {
			$arg->setRequired();
		}
		
		if ($optionSet['HIDE_IN_EXAMPLE']) {
			$arg->setHideInExample();
		}
		
		if (isset($argish[1]) && $optionSet['FLAG']) {
			throw new \Exception('shorthand arg array\'s 2nd element can not be used with the FLAG option');
		}
		
		if (isset($argish[1]) && !is_callable($argish[1])) {
			throw new \Exception('shorthand arg array\'s 2nd element is expected to be a function for validation');
		}
		
		if (isset($argish[1])) {
			$arg->setValidator($argish[1]);
		}
		
		return $arg;
	}
	
	public function getHelp()
	{
		$output = array();
		
		if ($this->valueMap === null) {
			$this->isValid();
		}
		
		// show any errors
		if (!empty($this->invalidSet)) {
			$output += $this->invalidSet;
			$output[] = '';
		}
		
		// put together the main command line example
		$commandLine = array();
		$commandLine[] = $this->inputList[0];
		foreach ($this->argMap as $id => $arg) {
			if ($arg->getHideInExample()) {
				continue;
			}
			$optional = $arg instanceof ArgInterface\Flag
				|| $arg instanceof ArgInterface\Value && !$arg->isRequired()
			;
			$expectsValue = $arg instanceof ArgInterface\Value
				&& ($arg instanceof ArgInterface\Named
					|| $arg instanceof ArgInterface\Character
				)
			;
			$commandLine[] = ''
				. ($optional ? '[' : '')
				. $id
				. ($expectsValue ? ' ___' : '')
				. ($optional ? ']' : '')
			;
		}
		$output[] =  implode(' ', $commandLine);
		
		// show the desciption for each argument
		$descriptionOffset = 4 + array_reduce(array_keys($this->argMap), function($v, $w) {
			$w = strlen($w);
			return $w > $v ? $w : $v;
		}, 0);
		if (!empty($this->argMap)) {
			$output[] = '';
			foreach ($this->argMap as $id => $arg) {
				$required = $arg instanceof ArgInterface\Value && $arg->isRequired();
				$output[] = "    " . trim($id . str_repeat(' ', $descriptionOffset - strlen($id)) . ($required ? '(required) ' : '') . $arg->getDescription());
			}
		}
		
		return implode(PHP_EOL, $output);
	}
}


namespace Arguments\ArgInterface;

interface Arg
{
	public function getId();
	public function getDescription();
	public function getHideInExample();
}

interface Value extends Arg
{
	public function isValid($value);
	public function isRequired();
}

interface Flag extends Arg {}

interface Positioned extends Arg {}

interface Named extends Arg {}

interface Character extends Arg {}


namespace Arguments\Arg;

use Arguments\ArgInterface;

class ArgAbstract
	implements ArgInterface\Arg
{
	protected $id = null;
	protected $description = null;
	protected $hideInExample = false;
	
	public static function create()
	{
		return new static();
	}
	
	public function setId($id)
	{
		$this->id = $id;
		return $this;
	}
	
	public function getId()
	{
		return $this->id;
	}
	
	public function setDescription($description)
	{
		$this->description = $description;
		return $this;
	}
	
	public function getDescription()
	{
		return $this->description;
	}
	
	public function setHideInExample($hideInExample = true)
	{
		$this->hideInExample = $hideInExample;
		return $this;
	}
	
	public function getHideInExample()
	{
		return $this->hideInExample;
	}
	
}

class Value extends ArgAbstract
	implements ArgInterface\Value
{
	protected $required = false;
	protected $validatorCallback = null;
	
	public function setValidator($callback)
	{
		$this->validatorCallback = $callback;
		return $this;
	}
	
	public function isValid($value)
	{
		if ($this->validatorCallback === null) {
			return true;
		}
		
		$f = $this->validatorCallback;
		return $f($value);
	}
	
	public function setRequired($required = true)
	{
		$this->required = $required;
		return $this;
	}
	
	public function isRequired()
	{
		return $this->required;
	}
}

class NamedFlag extends ArgAbstract
	implements ArgInterface\Named, ArgInterface\Flag
{
	public function setId($id)
	{
		return parent::setId('--' . $id);
	}
}

class NamedValue extends Value
	implements ArgInterface\Named
{
	public function setId($id)
	{
		return parent::setId('--' . $id);
	}
}

class PositionedValue extends Value
	implements ArgInterface\Positioned
{
}

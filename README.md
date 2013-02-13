Arguments
=========

PHP command line arguments parser

example usage
-------------

### from a nicely autoloaded script

```
$args = new \Arguments\Arguments($argv, array(
	'resource REQUIRED resource url',
	'--role role id',
	'--time FLAG provide time usage for script in output',
), true);

$request = $args->get('resource');

$role = $args->get('--role');
```

### from a simple script needing an optional --APP_ENV parameter

```
#!/usr/bin/env php
<?php

require_once dirname(__FILE__) . '/../library/Arguments/Arguments.php';
$args = new Arguments\Arguments($argv, array(
	'--APP_ENV',
), true);

if ($args->get('--APP_ENV')) {
	define('APP_ENV', $args->get('--APP_ENV'));
} elseif (!defined('APP_ENV')) {
	define('APP_ENV', (getenv('APP_ENV') ? getenv('APP_ENV') : 'local'));
}
```

### from a po file utility:
```
#!/usr/bin/env php
<?php

// run it from the command line

require_once dirname(__FILE__) . '/../library/Arguments/Arguments.php';
$args = new Arguments\Arguments($argv, array(
	array(
		'source REQUIRED po file to read for new key/literal pairs',
		function($x) {
			return file_exists($x)
				? true
				: 'source file does not exist'
			;
		},
	),
	array(
		'original po file to read for original key/literal pairs',
		function($x) {
			return file_exists($x)
				? true
				: 'original file does not exist'
			;
		},
	),
	array(
		'--translate lorem80, lorem100, lorem120, lorem140, lorem160',
		function($x) {
			return in_array(strtolower($x), array('lorem80', 'lorem100', 'lorem120', 'lorem140', 'lorem160'));
		},
	),
	'--showRemoved FLAG',
	'--showAdded FLAG',
	'--showChanged FLAG',
	array(
		'--maxLineLength (integer) default 80',
		function($x) {
			return intval($x) > 0
				? true
				: 'integer must be provided to argument --maxLineLength'
			;
		},
	),
));

// settings
$optionSet = array(
	'source' => $args->get('source'),
	'original' => $args->get('original'),
	'showRemoved' => $args->get('--showRemoved'),
	'showAdded' => $args->get('--showAdded'),
	'showChanged' => $args->get('--showChanged'),
	'maxLineLength' => $args->get('--maxLineLength'),
	'translate' => $args->get('--translate'),
	'generatePo' => true,
);

// validate the config options passed at the command line and give instructions if incorrect
$error = null;
if (($optionSet['showRemoved'] || $optionSet['showAdded'] || $optionSet['showChanged']) && !$optionSet['original']) {
	$error = 'must provide original if using --showRemoved, --showAdded, or --showChanged' . PHP_EOL . PHP_EOL;
} elseif (($optionSet['showRemoved'] || $optionSet['showAdded'] || $optionSet['showChanged']) && $optionSet['maxLineLength']) {
	$error = '--maxLineLength can not be used with --showRemoved, --showAdded, or --showChanged' . PHP_EOL . PHP_EOL;
} elseif (($optionSet['showRemoved'] || $optionSet['showAdded'] || $optionSet['showChanged']) && $optionSet['translate']) {
	$error = '--translate can not be used with --showRemoved, --showAdded, or --showChanged' . PHP_EOL . PHP_EOL;
}

if ($error || $args->isValid() !== true) {
	echo PHP_EOL . $error . $args->getHelp() . PHP_EOL;
	exit();
}
```

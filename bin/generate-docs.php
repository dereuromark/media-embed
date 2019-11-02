#!/usr/bin/php -q
<?php

$options = [
	__DIR__ . '/../vendor/autoload.php',
	__DIR__ . '/vendor/autoload.php'
];
if (!empty($_SERVER['PWD'])) {
	array_unshift($options, $_SERVER['PWD'] . '/vendor/autoload.php');
}

foreach ($options as $file) {
	if (file_exists($file)) {
		define('MEDIA_EMBED_COMPOSER_INSTALL', $file);
		break;
	}
}
require MEDIA_EMBED_COMPOSER_INSTALL;

$generator = new \MediaEmbed\Docs\Generator($argv);
$exitCode = $generator->generate();
exit($exitCode);

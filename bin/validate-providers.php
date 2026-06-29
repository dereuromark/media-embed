#!/usr/bin/php -q
<?php

$options = [
	__DIR__ . '/../vendor/autoload.php',
	__DIR__ . '/vendor/autoload.php',
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

$path = $argv[1] ?? dirname(__DIR__) . '/data/stubs.php';
$providers = include $path;
if (!is_array($providers)) {
	fwrite(STDERR, 'Provider file must return an array.' . PHP_EOL);
	exit(1);
}

$validator = new \MediaEmbed\Provider\ProviderValidator();
$errors = $validator->validate($providers);
if (!$errors) {
	echo 'Provider configuration is valid.' . PHP_EOL;
	exit(0);
}

foreach ($errors as $error) {
	fwrite(STDERR, $error . PHP_EOL);
}

exit(1);

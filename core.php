<?php
error_reporting(E_ALL);
header('Content-type: text/html; charset=utf-8');

function referer() {
	if (!isset($_SERVER["SCRIPT_FILENAME"]) or empty($_SERVER["SCRIPT_FILENAME"])) {
		return 'unknown';
	}
	$path = $_SERVER["SCRIPT_FILENAME"];
	return basename($path, ".php");
}

define('CORE', __DIR__);
define('PARSER_NAME', referer());
define('ATTACHMENTS', CORE.'/attachments');
define('PARSER_ATTACHMENTS', ATTACHMENTS.'/'.PARSER_NAME);
define('PARSERS', CORE.'/app');
define('LOCKS', CORE.'/locks');
define('CLASSES', CORE.'/classes');
define('LOGS', CORE.'/logs');
define('PARSER_LOGS', LOGS.'/'.PARSER_NAME);
define('PARSER_RESOURCES', PARSERS.'/'.PARSER_NAME);
define('COMPOSER', CORE.'/vendor/autoload.php');
define('CSV', CORE.'/csv');
define('CSV_PARSER', CSV.'/'.PARSER_NAME);

spl_autoload_register(function ($class) {
	require CLASSES.'/'.$class.'.class.php';
});

if (!file_exists(COMPOSER) or is_dir(COMPOSER)) {
	Logger::send("|START|ERROR| - There are no dependencies on the composer");
	exit();
}
require COMPOSER;

if (!file_exists(LOCKS) or !is_dir(LOCKS)) {
	mkdir(LOCKS);
}

$factory = new TH\Lock\FileFactory(LOCKS);
$lock = $factory->create(PARSER_NAME);
try {
	$lock->acquire();
} catch (Exception $ex) {
	Logger::send("|START|BLOCK| - Script ".PARSER_NAME." already started");
	exit();
}
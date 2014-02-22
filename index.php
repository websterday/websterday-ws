<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

require 'vendor/autoload.php';
require 'config/connection.php';
require 'routes/links.php';
require 'routes/folders.php';
require 'routes/users.php';

$app = new \Slim\Slim(array(
	'mode' => 'development',
	'log.writer' => new \Slim\Extras\Log\DateTimeFileWriter(array(
		'path'           => './logs',
		'name_format'    => 'Y-m-d',
		'message_format' => '%label% - %date% - %message%'
    )),
));

$app->configureMode('production', function () use ($app) {
	$app->config(array(
	    'log.level'  => \Slim\Log::ERROR,
	    // 'log.enable' => true,
        'debug' => false
	));
});

$app->configureMode('development', function () use ($app) {
	$app->config(array(
	    'log.level'  => \Slim\Log::DEBUG,
	    // 'log.enable' => false,
        'debug' => true
	));
});

$app->contentType('application/json');

$app->get('/links', 'getLinks');

$app->get('/folders', 'getFolders');

$app->get('/folders/:id', 'getFolder');

$app->get('/folders/:id/tree', 'getFolderTree');

$app->post('/folders', 'addFolder');

$app->post('/users/authenticate', 'authenticate');

$app->run();
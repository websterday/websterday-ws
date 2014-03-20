<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

date_default_timezone_set('Europe/Paris');		// for 1&1

require 'vendor/autoload.php';
require 'config/connection.php';
require 'lib/functions.php';
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

$app->notFound(function () use ($app) {
	echo 'Not found...';
});

$app->contentType('application/json');

// Links
$app->get('/links/folder/', 'getLinks');

$app->get('/links/folder/:folderId', 'getLinks');

$app->get('/links/folder', 'getFolderLink');

$app->post('/links', 'addLink');
$app->post('/links/', 'addLink');

$app->post('/links/move', 'moveLink');

$app->delete('/links/:id', 'deleteLink');

$app->get('/links/search/:value', 'search');

// Folders
$app->get('/folders', 'getFolders');

$app->get('/folders/:id', 'getFolder');

$app->post('/folders', 'addFolder');
$app->post('/folders/', 'addFolder');

$app->put('/folders/:id', 'updateFolder');

// Users
$app->post('/users/authenticate', 'authenticate');

$app->run();
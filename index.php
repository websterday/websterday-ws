<?php
require 'vendor/autoload.php';
require 'config/connection.php';
require 'routes/links.php';
require 'routes/folders.php';
require 'routes/users.php';

$app = new \Slim\Slim();

$app->contentType('application/json');

$app->get('/links', 'getLinks');

$app->get('/folders', 'getFolders');

$app->get('/folders/:id', 'getFolder');

$app->post('/users/authenticate', 'authenticate');

$app->run();
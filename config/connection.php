<?php

function getConnection() {
	$app = \Slim\Slim::getInstance();

	$host     = 'localhost';
	$user     = 'root';
	$password = '';
	$name     = 'bookmarks';

	try {
		$pdo = new PDO('mysql:host=' . $host . ';dbname=' . $name, $user, $password);
	    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		return $pdo;
	} catch (PDOException $e) {
		$app->log->error('database error : ' . $e->getMessage());

		throw new Exception('database error');
	}
}
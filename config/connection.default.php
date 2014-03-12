<?php

function getConnection() {
	$app = \Slim\Slim::getInstance();

	$host     = 'localhost';
	$user     = 'user';
	$password = 'password';
	$name     = 'bookmarks';

	try {
		$pdo = new PDO(
			'mysql:host=' . $host . ';dbname=' . $name,
			$user,
			$password,
			array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8')
		);
	    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		return $pdo;
	} catch (PDOException $e) {
		$app->log->error('database error : ' . $e->getMessage());

		throw new Exception('database error');
	}
}
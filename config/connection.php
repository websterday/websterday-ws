<?php

function getConnection() {
	$host     = '127.0.0.1';
	$user     = 'root';
	$password = '';
	$name     = 'bookmarks';
	$pdo      = new PDO('mysql:host=' . $host . ';dbname=' . $name, $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}
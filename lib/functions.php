<?php

function debug($data) {
	global $app;

	if ($app->mode == 'development') {
		echo '<pre>';
		print_r($data);
		echo '</pre>';
	} else {
		$app->log->debug($data);
	}
}

function checkTimeStamp($timestamp) {
    return ((string) (int) $timestamp === $timestamp) 
        && ($timestamp <= PHP_INT_MAX)
        && ($timestamp >= ~PHP_INT_MAX);
}

function error($message) {
	echo '{"error":"' . $message . '"}';
}

function randomString() {
	$length = 8;

    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $randomString;
}
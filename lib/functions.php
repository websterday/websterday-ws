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
<?php
function authenticate() {
	$app = \Slim\Slim::getInstance();
	// var_dump($app->request()->post('password')); die();
	if (!is_null($app->request()->post('username')) and !is_null($app->request()->post('password'))) {
		try {
			$db = getConnection();

			$sql = 'SELECT token FROM users WHERE username = :username AND password = :password';
			$stmt = $db->prepare($sql);

			$username = $app->request()->post('username');
			$password = sha1($app->request()->post('password'));
			
			$stmt->bindParam(':username', $username);
			$stmt->bindParam(':password', $password);

			$stmt->execute();

			$user = $stmt->fetch(PDO::FETCH_OBJ);
			$db = null;

			if ($user) {
				echo '{"token": "' . $user->token . '"}';
			} else {
				echo '{"error":"Wrong credentials"}';
				// TODO logs + manage error
			}
		} catch(PDOException $e) {
			echo '{"error":"Database connection problem"}';
			// TODO logs + manage error
			// $e->getMessage()
		}
	} else {
		echo '{"error":"wrong params"}';
	}
}

function getUser($token) {
	if (!is_null($token)) {
		try {
			$db = getConnection();

			$sql = 'SELECT id FROM users WHERE token = :token';
			$stmt = $db->prepare($sql);

			$stmt->bindParam(':token', $token);

			$stmt->execute();

			$user = $stmt->fetch(PDO::FETCH_OBJ);
			$db = null;

			if ($user) {
				return $user->id;
			} else {
				return false;
			}
		} catch(PDOException $e) {
			echo '{"error":"Database connection problem"}';
			// TODO logs + manage error
			// $e->getMessage()
		}
	} else {
		return false;
	}
}
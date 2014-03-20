<?php
function authenticate() {
	$app = \Slim\Slim::getInstance();

	$body = json_decode($app->request()->getBody());

	if ((!is_null($app->request()->post('username')) && !is_null($app->request()->post('password'))) or
		(!is_null($body->username) && !is_null($body->password))) {
		try {
			$db = getConnection();

			$sql = 'SELECT token FROM users WHERE username = :username AND password = :password';
			$stmt = $db->prepare($sql);

			if ((!is_null($app->request()->post('username')) && !is_null($app->request()->post('password')))) {
				$username = $app->request()->post('username');
				$password = sha1($app->request()->post('password'));
			} else {
				$username = $body->username;
				$password = sha1($body->password);
			}

			$stmt->bindParam(':username', $username);
			$stmt->bindParam(':password', $password);

			$stmt->execute();

			$user = $stmt->fetch(PDO::FETCH_OBJ);
			$db = null;

			if ($user) {
				echo '{"token": "' . $user->token . '"}';
			} else {
				echo '{"error":"Wrong credentials"}';
				$app->log->error('wrong credential : ' . $username . ' - ' . $password);
			}
		} catch(Exception $e) {
			error($e->getMessage());
		}
	} else {
		error('Wrong parameters');
		// $app->log->error('wrong parameters');
	}
}

function getUser($token, $db) {
	$app = \Slim\Slim::getInstance();

	if (!is_null($token)) {
		$sql = 'SELECT id FROM users WHERE token = :token';
		$stmt = $db->prepare($sql);

		$stmt->bindParam(':token', $token);

		$stmt->execute();

		$user = $stmt->fetch(PDO::FETCH_OBJ);
		$db = null;

		if ($user) {
			return $user->id;
		} else {
			$app->log->error('wrong token : ' . $token);

			throw new Exception('wrong token');
		}
	} else {
		$app->log->error('token null');

		throw new Exception('token null');
	}
}
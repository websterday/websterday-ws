<?php
function authenticate() {
	global $salt;

	$app = \Slim\Slim::getInstance();

	$body = json_decode($app->request()->getBody());

	if ((!is_null($app->request()->post('username')) && !is_null($app->request()->post('password'))) or
		(!is_null($body->username) && !is_null($body->password))) {
		try {
			$db = getConnection();

			$sql = 'SELECT id, token FROM users WHERE username = :username AND password = :password AND status = 1';
			$stmt = $db->prepare($sql);

			if ((!is_null($app->request()->post('username')) && !is_null($app->request()->post('password')))) {
				$username = $app->request()->post('username');
				$password = sha1($app->request()->post('password') . $salt);
			} else {
				$username = $body->username;
				$password = sha1($body->password . $salt);
			}

			$stmt->bindParam(':username', $username);
			$stmt->bindParam(':password', $password);

			$stmt->execute();

			$user = $stmt->fetch(PDO::FETCH_OBJ);
			$db = null;

			if ($user) {
				echo '{"id": ' . $user->id . ', "token": "' . $user->token . '"}';
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

function checkAuth($id, $token) {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$sql = 'SELECT COUNT(*) FROM users WHERE id = :id AND token = :token';

		$stmt = $db->prepare($sql);

		$stmt->bindParam(':id', $id);
		$stmt->bindParam(':token', $token);

		$stmt->execute();

		echo $stmt->fetchColumn();

	} catch(Exception $e) {
		error($e->getMessage());
	}
}

function getUser($token, $db) {
	global $salt;

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

function addUser() {
	global $salt;

	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$json = json_decode($app->getInstance()->request()->getBody());

		if (isset($json->user, $json->user->username, $json->user->email, $json->user->password)) {
			// Check if the username is already taken
			$username = $json->user->username;

			$sql = 'SELECT COUNT(*) FROM users WHERE username = :username AND status = 1';
			$stmt = $db->prepare($sql);

			$stmt->bindParam(':username', $username);

			$stmt->execute();

			if ($stmt->fetchColumn() == 0) {

				// Check if the email is already taken
				$email = $json->user->email;

				$sql = 'SELECT COUNT(*) FROM users WHERE email = :email AND status = 1';
				$stmt = $db->prepare($sql);

				$stmt->bindParam(':email', $email);

				$stmt->execute();

				if ($stmt->fetchColumn() == 0) {
					$sql = 'INSERT INTO users (username, password, email, token, created) VALUES (:username, :password, :email, :token, :created)';
					$stmt = $db->prepare($sql);

					$password = sha1($json->user->password . $salt);
					$token    = md5(uniqid(rand(), true));
					$created  = date('Y-m-d H:i:s');

					$stmt->bindParam(':username', $username);
					$stmt->bindParam(':email', $email);
					$stmt->bindParam(':password', $password);
					$stmt->bindParam(':token', $token);
					$stmt->bindParam(':created', $created);

					echo $stmt->execute();
					
					// $result = $stmt->execute();

					// $subject = 'Websterday - Account activation';
					// $message = "Hello $username,\n\nYour account has been created on Websterday but you need to activate it with this link:\n\n";
					// $message .= 'http://websterday.skurty.com/ws/users/activation?email=' . $user . '&key=' . $key;

					// if (mail($email, $subject, $message)) {
					// 	// ...
					// } else {
					// 	// ...
					// }
				} else {
					throw new Exception('Email already taken');
				}
			} else {
				throw new Exception('Username already taken');
			}
		} else {
			throw new Exception('Wrong parameters');
		}
	} catch(Exception $e) {
		error($e->getMessage());
	}
}
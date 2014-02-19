<?php
function getFolders() {
	$app = \Slim\Slim::getInstance();

	$userId = getUser($app->request()->get('token'));

	if ($userId) {
		try {
			$db = getConnection();

			$sql = 'SELECT id, name FROM folders WHERE user_id = :userId';
			$stmt = $db->prepare($sql);

			$stmt->bindParam(':userId', $userId);

			$stmt->execute();

			$folders = $stmt->fetch(PDO::FETCH_OBJ);
			$db = null;

			echo '{"folders": ' . json_encode($folders) . '}';
		} catch(PDOException $e) {
			echo '{"error":"Database connection problem"}';
			// TODO logs + manage error
			// $e->getMessage()
		}
	} else {
		echo '{"error":"Wrong token"}';
	}
}
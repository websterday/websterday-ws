<?php
function getLinks() {
	$app = \Slim\Slim::getInstance();
	$userId = getUser($app->request()->get('token'));

	if ($userId) {
		try {
			$db = getConnection();

			$sql = 'SELECT * FROM links';
			$stmt = $db->query($sql);
			$links = $stmt->fetchAll(PDO::FETCH_OBJ);
			$db = null;
			echo '{"links": ' . json_encode($links) . '}';
		} catch(PDOException $e) {
			echo '{"error":"Database connection problem"}';
			// TODO logs + manage error
			// $e->getMessage()
		}
	} else {
		echo '{"error":"Wrong token"}';
	}
}
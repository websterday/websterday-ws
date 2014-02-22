<?php
function getLinks() {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUser($app->request()->get('token'), $db);

		$sql = 'SELECT * FROM links';

		$stmt = $db->query($sql);

		$links = $stmt->fetchAll(PDO::FETCH_OBJ);

		echo json_encode($links);
	} catch(Exception $e) {
		echo '{"error":"' . $e->getMessage() . '"}';
	}
}
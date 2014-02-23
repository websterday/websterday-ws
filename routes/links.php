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

function search() {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUser($app->request()->get('token'), $db);

		if (!is_null($app->request()->get('value'))) {
			$sql = 'SELECT id, url FROM links WHERE url LIKE :url';

			$stmt = $db->prepare($sql);

			$url = '%' . $app->request()->get('value') . '%';

			$stmt->bindParam(':url', $url);

			$stmt->execute();

			$links = $stmt->fetchAll(PDO::FETCH_OBJ);

			echo json_encode($links);
		} else {
			throw new Exception('wrong parameters');
		}
	} catch(Exception $e) {
		echo '{"error":"' . $e->getMessage() . '"}';
	}
}

function addLink() {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUser($app->request()->get('token'), $db);

		$sql = 'INSERT INTO links (url, domain, created, user_id, folder_id) VALUES (:url, :domain, NOW(), :userId, :folderId)';
		$stmt = $db->prepare($sql);

		$url = $app->request()->post('url');
		// TODO parse url for domain
		$folderId = ($app->request()->post('folder_id') ? $app->request()->post('folder_id') : null);

		$stmt->bindParam(':url', $url);
		$stmt->bindParam(':domain', $url);
		$stmt->bindParam(':userId', $userId);
		$stmt->bindParam(':folderId', $folderId);
		
		echo $stmt->execute();
	} catch(Exception $e) {
		echo '{"error":"' . $e->getMessage() . '"}';
	}
}
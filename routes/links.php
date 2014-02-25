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

function search($value) {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUser($app->request()->get('token'), $db);

		$sql = 'SELECT id, url FROM links WHERE url LIKE :url';

		$stmt = $db->prepare($sql);

		$url = '%' . $value . '%';

		$stmt->bindParam(':url', $url);

		$stmt->execute();

		$links = $stmt->fetchAll(PDO::FETCH_OBJ);

		echo json_encode($links);
	} catch(Exception $e) {
		echo '{"error":"' . $e->getMessage() . '"}';
	}
}

function addLink() {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUser($app->request()->get('token'), $db);
		$url = $app->request()->post('url');
		$parsed_url = parse_url($url);
		$folderId = ($app->request()->post('folder_id') ? $app->request()->post('folder_id') : null);
		
		// Ckecking if link already exist in database
		$sql = 'SELECT COUNT(*) AS exist FROM links WHERE url = "'.$url.'";';
		$exist = $db->query($sql)->fetchColumn();
		
		if ($exist) {	   // link already exist
			$sql = 'UPDATE links SET count = count + 1, updated = NOW() WHERE url = :url;';
			$stmt = $db->prepare($sql);

			$stmt->bindParam(':url', $url);
		} else {		   // link doesn't exist
			$sql = 'INSERT INTO links (url, domain, created, updated, user_id, folder_id) VALUES (:url, :domain, NOW(), NOW(), :userId, :folderId);';
			$stmt = $db->prepare($sql);

			$stmt->bindParam(':url', $url);
			$stmt->bindParam(':domain', $parsed_url['host']);
			$stmt->bindParam(':userId', $userId);
			$stmt->bindParam(':folderId', $folderId);
		}
		echo $stmt->execute();

		
	} catch(Exception $e) {
		echo '{"error":"' . $e->getMessage() . '"}';
	}
}
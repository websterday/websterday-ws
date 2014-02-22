<?php
function getChildrenFolders($folders, $userId, $db) {
	$nbFolders = count($folders);

	for ($i = 0; $i < $nbFolders; $i++) {
		$sql = 'SELECT id, name FROM folders WHERE user_id = :userId AND parent_id = :folderId AND status = 1';
		$stmt = $db->prepare($sql);

		$stmt->bindParam(':userId', $userId);
		$stmt->bindParam(':folderId', $folders[$i]->id);

		$stmt->execute();

		$folders[$i]->folders = getChildrenFolders($stmt->fetchAll(PDO::FETCH_OBJ), $userId, $db);
	}

	return $folders;
}

function getFolders() {
	$app = \Slim\Slim::getInstance();

	$userId = getUser($app->request()->get('token'));

	if ($userId) {
		try {
			$db = getConnection();

			$sql = 'SELECT id, name FROM folders WHERE user_id = :userId AND parent_id IS NULL AND status = 1';
			$stmt = $db->prepare($sql);

			$stmt->bindParam(':userId', $userId);

			$stmt->execute();

			$folders = $stmt->fetchAll(PDO::FETCH_OBJ);

			getChildrenFolders($folders, $userId, $db);

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
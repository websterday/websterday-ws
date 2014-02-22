<?php
/**
 * Function to get the children of a folders list
 * @param  array 	$folders Folder list to get the children
 * @param  int 		$userId  The authenticated user
 * @param  object 	$db      PDO object
 * @return array          	 The list with the children of each folder
 */
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

			$folders = getChildrenFolders($folders, $userId, $db);

			echo json_encode($folders);
		} catch(PDOException $e) {
			echo '{"error":"Database connection problem"}';
			// TODO logs + manage error
			// $e->getMessage()
		}
	} else {
		echo '{"error":"Wrong token"}';
	}
}

/**
 * Get the folder details and its children
 * @param  int $id Folder id
 * @return json     [description]
 */
function getFolder($id) {
	$app = \Slim\Slim::getInstance();

	$userId = getUser($app->request()->get('token'));

	if ($userId) {
		try {
			$db = getConnection();

			$sql = 'SELECT id, name FROM folders WHERE user_id = :userId AND id = :id AND status = 1';
			$stmt = $db->prepare($sql);

			$stmt->bindParam(':userId', $userId);
			$stmt->bindParam(':id', $id);

			$stmt->execute();

			$folder = $stmt->fetch(PDO::FETCH_OBJ);


			$sql = 'SELECT id, name FROM folders WHERE user_id = :userId AND parent_id = :folderId AND status = 1';
			$stmt = $db->prepare($sql);

			$stmt->bindParam(':userId', $userId);
			$stmt->bindParam(':folderId', $folder->id);

			$stmt->execute();

			$folder->folders = $stmt->fetchAll(PDO::FETCH_OBJ);

			$folder->folders = getChildrenFolders($folder->folders, $userId, $db);

			echo json_encode($folder);
		} catch(PDOException $e) {
			echo '{"error":"Database connection problem"}';
			// TODO logs + manage error
			// $e->getMessage()
		}
	} else {
		echo '{"error":"Wrong token"}';
	}
}

function addFolder() {
	$app = \Slim\Slim::getInstance();

	$userId = getUser($app->request()->get('token'));

	if ($userId) {
		if ($app->request()->post('name')) {
			try {
				$db = getConnection();

				$sql = 'INSERT INTO folders (name, created, user_id, parent_id) VALUES (:name, NOW(), :userId, :parentId)';
				$stmt = $db->prepare($sql);

				$name = $app->request()->post('name');
				$parentId = ($app->request()->post('parent_id') ? $app->request()->post('parent_id') : NULL);

				$stmt->bindParam(':name', $name);
				$stmt->bindParam(':userId', $userId);
				$stmt->bindParam(':parentId', $parentId);
				
				echo $stmt->execute();
			} catch(PDOException $e) {
				echo '{"error":"Database connection problem"}';
				// TODO logs + manage error
				// $e->getMessage()
			}
		} else {
			echo '{"error":"Wrong parameters"}';
		}
	} else {
		echo '{"error":"Wrong token"}';
	}
}
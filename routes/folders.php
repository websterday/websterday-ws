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

// function createTree($folders, $parent) {
// 	$tree = array();

// 	foreach ($folders as $f) {
// 		print_r($f);
// 		// if ($f->parent_id == $parent) {
// 		// 	$f->children = createTree($folders, $f);
// 		// }
// 		$tree[] = $f;
// 	}

// 	return $tree;
// }

/**
 * Create a tree recursively
 * @param  [type] $list   [description]
 * @param  [type] $parent [description]
 * @return [type]         [description]
 */
function createTree(&$list, $parent) {
    $tree = array();

    foreach ($parent as $k=>$l) {
        if (isset($list[$l->id])) {
            $l->folders = createTree($list, $list[$l->id]);
        } else {
        	$l->folders = array();
        }
        unset($l->parent_id);
        $tree[] = $l;
    } 
    return $tree;
}

function getFolders() {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUser($app->request()->get('token'), $db);

		$tree = array();
		$getTree = true;

		// If the "last" parameter is used, we check if there is changes since the last time
		if ($app->request()->get('last') != '' && checkTimeStamp($app->request()->get('last')) && $app->request()->get('last') < time()) {
			$sql = 'SELECT count(*) AS count FROM folders WHERE user_id = :userId AND updated > :last';
			$stmt = $db->prepare($sql);

			$last = date('Y-m-d H:i:s', $app->request()->get('last'));

			$stmt->bindParam(':userId', $userId);
			$stmt->bindParam(':last', $last);

			$stmt->execute();

			if ($stmt->fetchColumn() == 0) {
				$getTree = false;
			}
		}
		
		if ($getTree) {
			// get the last date
			$sql = 'SELECT MAX(updated) AS updated FROM folders WHERE user_id = :userId';
			$stmt = $db->prepare($sql);

			$stmt->bindParam(':userId', $userId);

			$stmt->execute();

			$lastFolder = $stmt->fetch(PDO::FETCH_OBJ);

			if (!empty($lastFolder)) {
				$lastDate = strtotime($lastFolder->updated);
			}


			// get the tree
			$sql = 'SELECT id, name, parent_id FROM folders WHERE user_id = :userId AND status = 1';
			$stmt = $db->prepare($sql);

			$stmt->bindParam(':userId', $userId);

			$stmt->execute();

			$folders = $stmt->fetchAll(PDO::FETCH_OBJ);

			$new = array();

			foreach ($folders as $a){
				if (is_null($a->parent_id)) {
					$a->parent_id = 0;
				}

				$new[$a->parent_id][] = $a;
			}

			$tree = createTree($new, $new[0]);
		}


		$res = array();

		if (!empty($tree)) {
			$res['folders'] = $tree;
		}

		if (isset($lastDate)) {
			$res['last'] = $lastDate;
		}

		echo json_encode($res);
	} catch(Exception $e) {
		echo '{"error":"' . $e->getMessage() . '"}';
	}
}

/**
 * Get the folder details and its children
 * @param  int $id 	Folder id
 * @return json     The folder details
 */
function getFolder($id) {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUser($app->request()->get('token'), $db);

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
	} catch(Exception $e) {
		echo '{"error":"' . $e->getMessage() . '"}';
	}
}

/**
 * Get the parent folder recursively to get the tree of a folder
 * @param  object 	$folder  The folder to get the parent
 * @param  int 		$userId  The authenticated user
 * @param  object 	$db      PDO object
 * @return array         	 The parent folder with his child
 */
function getTree($folder, $userId, $db) {
	$sql = 'SELECT id, name, parent_id FROM folders WHERE user_id = :userId AND id = :id AND status = 1';
	$stmt = $db->prepare($sql);

	$stmt->bindParam(':userId', $userId);
	$stmt->bindParam(':id', $folder->parent_id);

	$stmt->execute();

	$parentFolder = $stmt->fetch(PDO::FETCH_OBJ);

	if ($parentFolder) {
		unset($folder->parent_id);

		$parentFolder->folder = $folder;

		return getTree($parentFolder, $userId, $db);
	} else {
		return $folder;
	}
}

function addFolder() {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUser($app->request()->get('token'), $db);

		$sql = 'INSERT INTO folders (name, created, user_id, parent_id) VALUES (:name, NOW(), :userId, :parentId)';
		$stmt = $db->prepare($sql);

		$name = $app->request()->post('name');
		$parentId = ($app->request()->post('parent_id') ? $app->request()->post('parent_id') : null);

		$stmt->bindParam(':name', $name);
		$stmt->bindParam(':userId', $userId);
		$stmt->bindParam(':parentId', $parentId);
		
		echo $stmt->execute();
	} catch(Exception $e) {
		echo '{"error":"' . $e->getMessage() . '"}';
	}
}
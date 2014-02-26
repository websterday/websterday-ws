<?php
/**
 * Create a tree recursively
 * @param  [type] $list   [description]
 * @param  [type] $parent [description]
 * @return [type]         [description]
 */
function createLinksTree(&$folders, $parent, $db) {
    $tree = array();

    foreach ($parent as $k=>$f) {
        if (isset($folders[$f->id])) {
            $f->folders = createLinksTree($folders, $folders[$f->id], $db);
        } else {
        	$f->folders = array();
        }

        $f->id = (int)$f->id;

        // Get the links
        $sql = 'SELECT id, url FROM links WHERE folder_id = :folderId AND status = 1';
		$stmt = $db->prepare($sql);

		$stmt->bindParam(':folderId', $f->id);

		$stmt->execute();

		$f->links = $stmt->fetchAll(PDO::FETCH_OBJ);

		// $f->links = array();
		foreach ($f->links as $l) {
			$l->id = (int)$l->id;
		}

        unset($f->parent_id);
        $tree[] = $f;
    } 
    return $tree;
}


/**
 * Get all the links with their folders
 * @return [type] [description]
 */
function getLinks() {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUser($app->request()->get('token'), $db);

		// get the tree
		$sql = 'SELECT id, name, parent_id FROM folders WHERE user_id = :userId AND status = 1';
		$stmt = $db->prepare($sql);

		$stmt->bindParam(':userId', $userId);

		$stmt->execute();

		$folders = $stmt->fetchAll(PDO::FETCH_OBJ);

		// print_r($folders); die();

		$new = array();

		foreach ($folders as $a){
			if (is_null($a->parent_id)) {
				$a->parent_id = 0;
			}

			$new[$a->parent_id][] = $a;
		}

		$tree = createLinksTree($new, $new[0], $db);

		// print_r($tree); die();


		// $sql = 'SELECT * FROM links';

		// $stmt = $db->query($sql);

		// $links = $stmt->fetchAll(PDO::FETCH_OBJ);

		echo json_encode($tree);
	} catch(Exception $e) {
		echo '{"error":"' . $e->getMessage() . '"}';
	}
}

function search($value) {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUser($app->request()->get('token'), $db);

		// get the links with url corresponding to the search
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

		$parsedUrl = parse_url($url);

		$folderId = ($app->request()->post('folder_id') ? $app->request()->post('folder_id') : null);
		
		// Checking if the link already exist in database
		$sql = 'SELECT * FROM links WHERE url = :url AND user_id = :userId AND status = 1';
		$stmt = $db->prepare($sql);

		$stmt->bindParam(':url', $url);
		$stmt->bindParam(':userId', $userId);
		
		$stmt->execute();

		$exist = $stmt->fetchColumn();
		
		if ($exist) {	   // link already exist
			$sql = 'UPDATE links SET count = count + 1, updated = NOW() WHERE url = :url;';
			$stmt = $db->prepare($sql);

			$stmt->bindParam(':url', $url);
		} else {		   // link doesn't exist
			$sql = 'INSERT INTO links (url, domain, created, updated, user_id, folder_id) VALUES (:url, :domain, NOW(), NOW(), :userId, :folderId);';
			$stmt = $db->prepare($sql);

			$stmt->bindParam(':url', $url);
			$stmt->bindParam(':domain', $parsedUrl['host']);
			$stmt->bindParam(':userId', $userId);
			$stmt->bindParam(':folderId', $folderId);
		}
		echo $stmt->execute();

		
	} catch(Exception $e) {
		echo '{"error":"' . $e->getMessage() . '"}';
	}
}
<?php

/**
 * Get the links and folders of a folder
 * @param  int $folderId The concerned folder (if null => root)
 * @return [type]           [description]
 */
function getLinks($folderId = null) {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUser($app->request()->get('token'), $db);

		if (!is_null($folderId)) {
			$sql = 'SELECT parent_id FROM folders WHERE id = :folderId AND status = 1';

			$stmt = $db->prepare($sql);

			$stmt->bindParam(':folderId', $folderId);

			$stmt->execute();

			$folder = $stmt->fetch(PDO::FETCH_OBJ);
		}

		// get the folders
		$sql = 'SELECT id, name FROM folders WHERE user_id = :userId AND status = 1';

		if (!is_null($folderId)) {
			$sql .= ' AND parent_id = :folderId';
		}
		
		$stmt = $db->prepare($sql);

		$stmt->bindParam(':userId', $userId);

		if (!is_null($folderId)) {
			$stmt->bindParam(':folderId', $folderId);
		}

		$stmt->execute();

		$tree['folders'] = $stmt->fetchAll(PDO::FETCH_OBJ);

		// Get links in Home
		
		if (!is_null($folderId)) {
			$sql = 'SELECT id, url FROM links WHERE folder_id = :folderId AND status = 1';
		} else {
			$sql = 'SELECT id, url FROM links WHERE folder_id IS NULL AND status = 1';
		}

		$stmt = $db->prepare($sql);

		if (!is_null($folderId)) {
			$stmt->bindParam(':folderId', $folderId);
		}

		$stmt->execute();

		$tree['links'] = $stmt->fetchAll(PDO::FETCH_OBJ);

		// Get the breadcrumb
		if (isset($folder->parent_id) and !is_null($folder->parent_id)) {
			$tree['breadcrumb'] = array();
			getParentFolder($tree['breadcrumb'], $folder->parent_id, $db);
		}

		echo json_encode($tree);
	} catch(Exception $e) {
		echo '{"error":"' . $e->getMessage() . '"}';
	}
}

function getParentFolder(&$tree, $id, $db) {
	$sql = 'SELECT id, name, parent_id FROM folders WHERE id = :parentId';

	$stmt = $db->prepare($sql);

	$stmt->bindParam(':parentId', $id);

	$stmt->execute();

	$folder = $stmt->fetch(PDO::FETCH_OBJ);

	if (!empty($folder)) {
		getParentFolder($tree, $folder->parent_id, $db);

		unset($folder->parent_id);

		$tree[] = $folder;
	}
}

function getFolderLink() {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUser($app->request()->get('token'), $db);
		$url = $app->request()->get('url');

		// get the links with url corresponding to the search
		$sql = 'SELECT links.folder_id AS id, folders.name AS name FROM links LEFT JOIN folders ON links.folder_id = folders.id WHERE links.url = :url AND links.user_id = :userId';

		$stmt = $db->prepare($sql);

		$url = urldecode($url);
		$stmt->bindParam(':url', $url);
		$stmt->bindParam(':userId', $userId);

		$stmt->execute();

		$folder = $stmt->fetch(PDO::FETCH_OBJ);

		echo json_encode($folder);
		
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
		$domain = $parsedUrl['host'];

		$folderId = ($app->request()->post('folder_id') ? $app->request()->post('folder_id') : null);
		
		// Checking if the domain already exist in database and is enabled for the user
		$sql = 'SELECT allowed, d.id FROM domains d LEFT JOIN domains_users du ON d.id = domain_id AND du.status = 1 AND user_id = :userId WHERE url = :url AND d.status = 1;;';
		$stmt = $db->prepare($sql);

		$stmt->bindParam(':url', $domain);
		$stmt->bindParam(':userId', $userId);
		
		$stmt->execute();

		$domainResult = $stmt->fetch(PDO::FETCH_OBJ);
		
		if ($domainResult && $domainResult->allowed) {	   // domain already exist and is allowed
			 // Checking if the link already exist in database
			 $sql = 'SELECT * FROM links WHERE url = :url AND user_id = :userId AND status = 1;';
			 $stmt = $db->prepare($sql);

			 $stmt->bindParam(':url', $url);
			 $stmt->bindParam(':userId', $userId);

			 $stmt->execute();

			 $link = $stmt->fetchColumn();
		
			 if (!empty($link)) {	   // link already exist
				 $sql = 'UPDATE links SET count = count + 1, updated = NOW() WHERE url = :url AND user_id = :userId AND status = 1;';
				 $stmt = $db->prepare($sql);

				 $stmt->bindParam(':url', $url);
				 $stmt->bindParam(':userId', $userId);
			 } else {		   // link doesn't exist
				 $sql = 'INSERT INTO links (url, domain_id, created, updated, user_id, folder_id) VALUES (:url, :domainId, NOW(), NOW(), :userId, :folderId);';
				 $stmt = $db->prepare($sql);

				 $stmt->bindParam(':url', $url);
				 $stmt->bindParam(':domainId', $domain->id);
				 $stmt->bindParam(':userId', $userId);
				 $stmt->bindParam(':folderId', $folderId);
			 }
			 $stmt->execute();
		    
			 $sql = 'UPDATE links SET count = count + 1, updated = NOW() WHERE url = :url AND user_id = :userId AND status = 1;';
			 $stmt = $db->prepare($sql);

			 $stmt->bindParam(':url', $url);
			 $stmt->bindParam(':userId', $userId);
			 
			 echo $stmt->execute();
			 
		} elseif (is_null($domainResult->allowed)) {			   // domain doesn't exist for the user or not at all
			if (isset($domainResult->id)) {
				$domainId = $domainResult->id;
			} else {			// new domain
				$sql = 'INSERT INTO domains (url, created, updated) VALUES (:url, NOW(), NOW());';
				$stmt = $db->prepare($sql);

				$stmt->bindParam(':url', $domain);

				$stmt->execute();

				$domainId = $db->lastInsertId();
			}

			// Link the domain to the user
			// $sql = 'INSERT INTO domains_users (created, updated, domain_id, user_id) VALUES (NOW(), NOW(), :domainId, :userId);';
			// $stmt = $db->prepare($sql);

			// $stmt->bindParam(':domainId', $domainId);
			// $stmt->bindParam(':userId', $userId);

			// $stmt->execute();
			
			// Create the link
			$sql = 'INSERT INTO links (url, domain_id, created, updated, user_id, folder_id) VALUES (:url, :domainId, NOW(), NOW(), :userId, :folderId);';
			$stmt = $db->prepare($sql);

			$stmt->bindParam(':url', $url);
			$stmt->bindParam(':domainId', $domainId);
			$stmt->bindParam(':userId', $userId);
			$stmt->bindParam(':folderId', $folderId);
			 
			echo $stmt->execute();
		}
		
	} catch(Exception $e) {
		echo '{"error":"' . $e->getMessage() . '"}';
	}
}

function moveLink() {
	$app = \Slim\Slim::getInstance();

	try {
	   $db = getConnection();

	   $userId = getUser($app->request()->get('token'), $db);
	   $url = $app->request()->post('url');
	   $folderId = ($app->request()->post('folder_id') ? $app->request()->post('folder_id') : null);
	   
	   $sql = 'UPDATE links SET folder_id = :folderId, updated = NOW() WHERE url = :url AND user_id = :userId AND status = 1;';
	   $stmt = $db->prepare($sql);

	   $stmt->bindParam(':url', $url);
	   $stmt->bindParam(':userId', $userId);
	   $stmt->bindParam(':folderId', $folderId);
	   
	   echo $stmt->execute();
		
	} catch(Exception $e) {
		echo '{"error":"' . $e->getMessage() . '"}';
	}
}
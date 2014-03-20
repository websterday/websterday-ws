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

		$tree = array();

		if (!is_null($folderId)) {
			$sql = 'SELECT name, parent_id FROM folders WHERE id = :folderId AND status = 1';

			$stmt = $db->prepare($sql);

			$stmt->bindParam(':folderId', $folderId);

			$stmt->execute();

			$folder = $stmt->fetch(PDO::FETCH_OBJ);

			if (!empty($folder)) {
				$tree['folder'] = array('name' => $folder->name);
			}
		}

		// get the folders
		$sql = 'SELECT id, name, created, updated FROM folders WHERE user_id = :userId AND status = 1';

		if (!is_null($folderId)) {
			$sql .= ' AND parent_id = :folderId';
		} else {
			$sql .= ' AND parent_id IS NULL';
		}
		
		$stmt = $db->prepare($sql);

		$stmt->bindParam(':userId', $userId);

		if (!is_null($folderId)) {
			$stmt->bindParam(':folderId', $folderId);
		}

		$stmt->execute();

		$folders = $stmt->fetchAll(PDO::FETCH_OBJ);

		foreach ($folders as $f) {
			if ($f->updated) {
				$f->date = strtotime($f->updated);
			} else {
				$f->date = strtotime($f->created);
			}

			$f->date = time();

			unset($f->created);
			unset($f->updated);
		}

		$tree['folders'] = $folders;

		// Get links in Home
		
		if (!is_null($folderId)) {
			$sql = 'SELECT id, url, created, updated FROM links WHERE folder_id = :folderId AND status = 1';
		} else {
			$sql = 'SELECT id, url, created, updated FROM links WHERE folder_id IS NULL AND status = 1';
		}

		$stmt = $db->prepare($sql);

		if (!is_null($folderId)) {
			$stmt->bindParam(':folderId', $folderId);
		}

		$stmt->execute();

		$links = $stmt->fetchAll(PDO::FETCH_OBJ);

		foreach ($links as $l) {
			if (is_null($l->updated)) {
				$l->date = strtotime($l->updated);
			} else {
				$l->date = strtotime($l->created);
			}

			unset($l->created);
			unset($l->updated);
		}

		$tree['links'] = $links;

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
		error($e->getMessage());
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
		error($e->getMessage());
	}
}

function addLink() {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUser($app->request()->get('token'), $db);

		$json = json_decode($app->getInstance()->request()->getBody());

		$validParams = false;

		if (!is_null($app->request()->post('url'))) {
			$url = $app->request()->post('url');

			$folderId = ($app->request()->post('folder_id') ? $app->request()->post('folder_id') : null);

			$validParams = true;
		} else {
			$json = json_decode($app->getInstance()->request()->getBody());

			if (isset($json->url)) {
				$url = $json->url;

				$validParams = true;
			}

			if (isset($json->folder_id)) {
				$folderId = $json->folder_id;
			} else {
				$folderId = null;
			}
		}

		if ($validParams) {
			$parsedUrl = parse_url($url);

			if (isset($parsedUrl['host'])) {
				$domain = $parsedUrl['host'];

				// TODO check if the link is already in the db
				
				// Checking if the domain already exist in database and is enabled for the user
				$sql = 'SELECT allowed, d.id FROM domains d LEFT JOIN domains_users du ON d.id = domain_id AND du.status = 1 AND user_id = :userId WHERE url = :url AND d.status = 1;;';
				$stmt = $db->prepare($sql);

				$stmt->bindParam(':url', $domain);
				$stmt->bindParam(':userId', $userId);
				
				$stmt->execute();

				$domainResult = $stmt->fetch(PDO::FETCH_OBJ);
				
				if ($domainResult && $domainResult->allowed) {	   // domain already exist and is allowed
					// Checking if the link already exist in database
					$sql = 'SELECT id FROM links WHERE url = :url AND user_id = :userId AND status = 1';

					if (!is_null($folderId)) {
						$sql .= ' AND folder_id = :folderId';
					} else {
						$sql .= ' AND folder_id IS NULL';
					}

					$stmt = $db->prepare($sql);

					$stmt->bindParam(':url', $url);
					$stmt->bindParam(':userId', $userId);

					if (!is_null($folderId)) {
						$stmt->bindParam(':folderId', $folderId);
					}

					$stmt->execute();

					$link = $stmt->fetchColumn();

					$date = date('Y-m-d H:i:s');

					if ($link) {	   // link already exists
						$sql = 'UPDATE links SET count = count + 1, updated = :updated WHERE id = :id;';
						$stmt = $db->prepare($sql);

						$stmt->bindParam(':id', $link);
						$stmt->bindParam(':updated', $date);
						$stmt->bindParam(':id', $link);
						$stmt->execute();

						echo 1;
					} else {			// link doesn't exist
						$sql = 'INSERT INTO links (url, domain_id, created, updated, user_id, folder_id) VALUES (:url, :domainId, :created, :updated, :userId, :folderId);';
						$stmt = $db->prepare($sql);

						$stmt->bindParam(':url', $url);
						$stmt->bindParam(':domainId', $domainResult->id);
						$stmt->bindParam(':userId', $userId);
						$stmt->bindParam(':created', $date);
						$stmt->bindParam(':updated', $date);
						$stmt->bindParam(':folderId', $folderId);
						$stmt->execute();

						$link = array(
							'id'   => $db->lastInsertId(),
							'url'  => $url,
							'date' => strtotime($date)
						);

						echo json_encode(array('link' => $link));
					}
				} elseif (!$domainResult || is_null($domainResult->allowed)) {			   // domain doesn't exist for the user or not at all
					if (isset($domainResult->id)) {
						$domainId = $domainResult->id;
					} else {			// new domain
						$sql = 'INSERT INTO domains (url, created, updated) VALUES (:url, NOW(), NOW());';
						$stmt = $db->prepare($sql);

						$stmt->bindParam(':url', $domain);

						$stmt->execute();

						$domainId = $db->lastInsertId();
					}
					
					$date = date('Y-m-d H:i:s');
					
					// Create the link
					$sql = 'INSERT INTO links (url, domain_id, created, updated, user_id, folder_id) VALUES (:url, :domainId, :created, :updated, :userId, :folderId);';
					$stmt = $db->prepare($sql);

					$stmt->bindParam(':url', $url);
					$stmt->bindParam(':domainId', $domainId);
					$stmt->bindParam(':created', $date);
					$stmt->bindParam(':updated', $date);
					$stmt->bindParam(':userId', $userId);
					$stmt->bindParam(':folderId', $folderId);
					 
					$stmt->execute();

					$link = array(
						'id'   => $db->lastInsertId(),
						'url'  => $url,
						'date' => strtotime($date)
					);

					echo json_encode(array('link' => $link));
				}
			} else {
				throw new Exception('Wrong url');
			}
		} else {
			throw new Exception('Wrong parameters');
		}
	} catch(Exception $e) {
		error($e->getMessage());
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
		error($e->getMessage());
	}
}
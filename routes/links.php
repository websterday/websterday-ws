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

		$userId = getUserId($app->request()->get('token'), $db);

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

		if (!is_null($folderId)) {		// Get links in Home
			$sql = 'SELECT lu.id, title, url, created, updated FROM links l, links_users lu WHERE folder_id = :folderId AND l.id = link_id AND status = 1';
		} else {
			$sql = 'SELECT lu.id, title, url, created, updated FROM links l, links_users lu WHERE folder_id IS NULL AND l.id = link_id AND status = 1';
		}

		$stmt = $db->prepare($sql);

		if (!is_null($folderId)) {
			$stmt->bindParam(':folderId', $folderId);
		}

		$stmt->execute();

		$links = $stmt->fetchAll(PDO::FETCH_OBJ);

		foreach ($links as $l) {
			if (!is_null($l->updated)) {
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

		$userId = getUserId($app->request()->get('token'), $db);
		$url = $app->request()->get('url');

		// get the links with url corresponding to the search
		$sql = 'SELECT lu.folder_id AS id, f.name AS name FROM links_users lu LEFT JOIN links l ON l.id = link_id LEFT JOIN folders f ON lu.folder_id = f.id WHERE l.url = :url AND lu.user_id = :userId AND lu.status = 1;';

		$stmt = $db->prepare($sql);

		$url = urldecode($url);
		$stmt->bindParam(':url', $url);
		$stmt->bindParam(':userId', $userId);

		$stmt->execute();

		$folder = $stmt->fetch(PDO::FETCH_OBJ);

		echo json_encode($folder);

	} catch(Exception $e) {
		error($e->getMessage(), $e->getLine());
	}
}

function search($value) {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUserId($app->request()->get('token'), $db);

		// get the links with url corresponding to the search
		$sql = 'SELECT lu.id, url, f.id folderId, title, name FROM links l, links_users lu LEFT JOIN folders f ON folder_id = f.id WHERE (url LIKE :search OR title = :search) AND l.id = link_id AND lu.user_id = :userId';

		$stmt = $db->prepare($sql);

		$search = '%' . $value . '%';

		$stmt->bindParam(':search', $search);
		$stmt->bindParam(':userId', $userId);
		$stmt->execute();

		$links = $stmt->fetchAll(PDO::FETCH_OBJ);

		echo json_encode($links);
	} catch(Exception $e) {
		error($e->getMessage(), $e->getLine());
	}
}

function addLink() {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUserId($app->request()->get('token'), $db);

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

				// Check if the domain exists and is allowed by the user
				$sql = 'SELECT allowed, d.id FROM domains d LEFT JOIN domains_users du ON d.id = domain_id AND du.status = 1 AND user_id = :userId WHERE url = :url;';
				$stmt = $db->prepare($sql);

				$stmt->bindParam(':url', $domain);
				$stmt->bindParam(':userId', $userId);

				$stmt->execute();

				$domainResult = $stmt->fetch(PDO::FETCH_OBJ);

				if (!$domainResult || is_null($domainResult->allowed) || $domainResult->allowed) {
					if (isset($domainResult->id)) {
						$domainId = $domainResult->id;
					} else {			// Domain doesn't exists, we create it
						$sql = 'INSERT INTO domains (url) VALUES (:url);';
						$stmt = $db->prepare($sql);

						$stmt->bindParam(':url', $domain);

						$stmt->execute();

						$domainId = $db->lastInsertId();
					}
					
					// Check if the link exists (for the user)
					$sql = 'SELECT l.id linkId, title, lu.id linkUserId FROM links l LEFT JOIN links_users lu ON l.id = link_id AND user_id = :userId AND lu.status = 1 ';

					if (!is_null($folderId)) {
						$sql .= 'AND folder_id = :folderId ';
					} else {
						$sql .= 'AND folder_id IS NULL ';
					}

					$sql .= 'WHERE url = :url';

					$stmt = $db->prepare($sql);

					$stmt->bindParam(':url', $url);
					$stmt->bindParam(':userId', $userId);

					if (!is_null($folderId)) {
						$stmt->bindParam(':folderId', $folderId);
					}

					$stmt->execute();

					$linkResult = $stmt->fetch(PDO::FETCH_OBJ);

					if (!$linkResult) {		// The link doesn't exists, we create it
						$title = $description = '';

						getInfosUrl($url, $title, $description);

						$sql = 'INSERT INTO links (url, title, description, domain_id) VALUES (:url, :title, :description, :domainId);';
						$stmt = $db->prepare($sql);

						$stmt->bindParam(':url', $url);
						$stmt->bindParam(':title', $title);
						$stmt->bindParam(':description', $description);
						$stmt->bindParam(':domainId', $domainId);
						$stmt->execute();

						$linkId = $db->lastInsertId();
					} else {		
						$linkId = $linkResult->linkId;
						$title = $linkResult->title;
					}

					if (!isset($linkResult->linkUserId) || is_null($linkResult->linkUserId)) {
						// Add the link for the user
						$sql = 'INSERT INTO links_users (created, link_id, user_id, folder_id) VALUES (:created, :linkId, :userId, :folderId);';
						$stmt = $db->prepare($sql);

						$date = date('Y-m-d H:i:s');

						$stmt->bindParam(':created', $date);
						$stmt->bindParam(':linkId', $linkId);
						$stmt->bindParam(':userId', $userId);
						$stmt->bindParam(':folderId', $folderId);

						$stmt->execute();

						$link = array(
							'id'    => $db->lastInsertId(),
							'url'   => $url,
							'title' => $title,
							'date'  => strtotime($date)
						);

						echo json_encode(array('link' => $link));
					} else {
						throw new Exception('Link already in the databasse');
					}
				} else {
					throw new Exception('Domain not allowed by user');
				}
			} else {
				throw new Exception('Wrong url');
			}
		} else {
			throw new Exception('Wrong parameters');
		}
	} catch(Exception $e) {
		error($e->getMessage(), $e->getLine());
	}
}

function getInfosUrl($url, &$title, &$description) {
	require 'lib/simplehtmldom_1_5/simple_html_dom.php';
	$html = file_get_html($url);

	try {
		$title       = $html->find('title', 0)->plaintext;
		$description = $html->find('meta[name="description"]', 0)->content;
	} catch (Exception $e) {
		$app = \Slim\Slim::getInstance();

		$app->log->error('can\'t get infos : ' . $url);
	}
}

function moveLink() {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUserId($app->request()->get('token'), $db);

		if (!is_null($app->request()->post('url'))) {
			$url = $app->request()->post('url');
			$folderId = ($app->request()->post('folder_id') ? $app->request()->post('folder_id') : null);

			$sql = 'UPDATE links_users lu, links l SET folder_id = :folderId, updated = NOW() WHERE url = :url AND l.id = link_id AND user_id = :userId AND status = 1;';
			$stmt = $db->prepare($sql);

			$stmt->bindParam(':url', $url);
			$stmt->bindParam(':userId', $userId);
			$stmt->bindParam(':folderId', $folderId);

			echo $stmt->execute();
		} else {
			throw new Exception('Wrong parameters');
		}
	} catch(Exception $e) {
		error($e->getMessage(), $e->getLine());
	}
}

function deleteLinkRequest($id, $userId, $db) {
	$sql = 'UPDATE links_users SET status = 0, updated = NOW() WHERE id = :id AND user_id = :userId;';
	$stmt = $db->prepare($sql);

	$stmt->bindParam(':id', $id);
	$stmt->bindParam(':userId', $userId);

	return $stmt->execute();
}

function updateLink($id) {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUserId($app->request()->get('token'), $db);

		$json = json_decode($app->getInstance()->request()->getBody());

		if (isset($json->id) && isset($json->url)) {
			$sql = 'UPDATE links_users SET url = :url, updated = NOW() WHERE id = :id AND user_id = :userId;';
			$stmt = $db->prepare($sql);

			$stmt->bindParam(':url', $json->url);
			$stmt->bindParam(':id', $json->id);
			$stmt->bindParam(':userId', $userId);

			echo $stmt->execute();
		} else {
			throw new Exception('Wrong parameters');
		}

		// echo deleteLinkRequest($id, $userId, $db);
	} catch(Exception $e) {
		error($e->getMessage(), $e->getLine());
	}
}

function deleteLink($id) {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUserId($app->request()->get('token'), $db);

		echo deleteLinkRequest($id, $userId, $db);

	} catch(Exception $e) {
		error($e->getMessage(), $e->getLine());
	}
}

function deleteMultipleLinks() {
	$app = \Slim\Slim::getInstance();

	try {
		$db = getConnection();

		$userId = getUserId($app->request()->get('token'), $db);

		$json = json_decode($app->getInstance()->request()->getBody());

		$ok = false;

		foreach($json->list->folders as $k => $v) {
			$ok = deleteFolderRequest($k, $userId, $db);

			if (!$ok) {
				$app->log->error('error multiple delete : folder ' . $k);
			}
		}

		foreach($json->list->links as $k => $v) {
			$ok = deleteLinkRequest($k, $userId, $db);

			if (!$ok) {
				$app->log->error('error multiple delete : link ' . $k);
			}
		}

		echo $ok;
	} catch(Exception $e) {
		error($e->getMessage(), $e->getLine());
	}
}
Bookmarks WS
============

> Save your links for later

Bookmarks WS is a simple REST API to manage links organized into folders. It is part of a global project to help you to remember where you browse.

The other parts are available at :

Bookmarks Front - The front end part of the project (not yet available)

[Bookmarks Extension](https://github.com/thisIsNotCamelCase/bookmarks-ext) - The Chrome extension to automatically save the links and set the destination folder

## Database

The database system used is MySQL. The schema is available in the "database" folder.

## Installation

To use this API:

1. download the sources
2. install a MySQL database
3. edit "config/connection.php" to change the database connection
4. test the web service with methods below

## API methods

### Users

| URL                 | Method | Description          |
| ------------------- | ------ | -------------------- |
| /users/authenticate | GET    | Authenticate an user |

### Links

| URL          | Method | Description     |
| ------------ | ------ | --------------- |
| /links       | GET    | Links list      |
| /links       | POST   | Create a link   |
| /links/:id   | GET    | Link details    |
| /links/:id   | PUT    | Edit a link     |
| /links/:id   | DELETE | Delete a link   |

### Folders

| URL          | Method | Description     |
| ------------ | ------ | --------------- |
| /folders     | GET    | Folders list    |
| /folders     | POST   | Create a folder |
| /folders/:id | GET    | Folder details  |
| /folders/:id | PUT    | Edit a folder   |
| /folders/:id | DELETE | Delete a folder |


### Informations

For each web service, you'll need to use the token get with /users/authenticate, example: /links?token=XXX

## Support

For any bugs about the installation or the usage, please feel free to report [here](https://github.com/skurty/bookmarks-ws/issues).

You are welcome to fork and submit pull requests.

## License

The source code is available on [GitHub](https://github.com/skurty/bookmarks-ws) under MIT license.
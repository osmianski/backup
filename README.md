# Backup

My local backup scripts.

## How it works

This project does several things. 

First, it prepares some files locally:

* It turns project directories into `tar.gz`, updates `tar.gz` only if project is updated, deletes `tar.gz` if project is deleted.
* It backups databases into `gz`, updates `gz` only if database changes, deletes `gz` if database is deleted.

**Note**. If a project or a database is no longer used, delete its `gz` to the `archive` directory before deletion.

Second, it syncs and encrypts selected directories to the cloud.

It runs on reboot or wake up daily. The sync only happens from specified locations.

All operations are logged.

## Prerequisites

1. Install [PHP, Composer, Docker](https://laravel.com/docs/10.x).
2. Install [rclone](https://rclone.org/install/), and [configure it to access your Google Drive account](https://rclone.org/drive/#making-your-own-client-id).

## License

This project is open-sourced software licensed under the MIT license.

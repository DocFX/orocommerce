This file includes only the most important items that should be addressed before attempting to upgrade or during the upgrade of a vanilla Oro application.

Please refer to [CHANGELOG.md](CHANGELOG.md) for a list of significant changes in the code that may affect the upgrade of some customizations.

## FROM 4.1.0 to 4.2.0

The `var/attachment` and `var/import_export` directories are no longer used for storing files and have been removed from the default directory structure.

All files from these directories must be moved to the new locations:
- from `var/attachment/protected_mediacache` to `var/data/protected_mediacache`;
- from `var/attachment` to `var/data/attachments`;
- from `var/import_export` to `var/data/importexport`.

Files for the standard import should be placed into `var/data/import_files` instead of `var/import_export/files`.

Files for the product images import should be placed into `var/data/import_files` instead of `var/import_export/product_images`.

## FROM 1.5.0 to 1.6.0
* Changed minimum required php version to 7.1
* Relation between Category and Product has been changed in database. Join table has been removed. Please, make sure that you have fresh database backup before updating application.

## FROM 1.4.0 to 1.5.0

Full product reindexation has to be performed after upgrade!

## FROM 1.3.0 to 1.4.0
 
Format of sluggable urls cache was changed, added support of localized slugs. Cache regeneration is required after update. 

## FROM 1.0.0 to 1.1.0

* Minimum required `php` version has changed from **5.7** to **7.0**.
* [Fxpio/composer-asset-plugin](https://github.com/fxpio/composer-asset-plugin) dependency was updated to version **1.3**.
* Composer was updated to version **1.4**; use the following commands:

  ```
      composer self-update
      composer global require "fxp/composer-asset-plugin"
  ```

* To upgrade OroCommerce from **1.0** to **1.1** use the following command:

  ```bash
  php bin/console oro:platform:update --env=prod --force
  ```


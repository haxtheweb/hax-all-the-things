<?php

/**
 #ddev-generated: Automatically generated Backdrop settings file.
 ddev manages this file and may delete or overwrite the file unless this comment is removed.
 */

$database = 'mysql://db:db@db/db';
$database_prefix = '';

$settings['update_free_access'] = FALSE;
$settings['hash_salt'] = 'DXwZahjrcueuYQdjxvcZUFeMzVgjIgPZgYbuaAOLGsKZFjhKwowAnnPiAYhHqsGo';
$settings['backdrop_drupal_compatibility'] = TRUE;

ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 200000);
ini_set('session.cookie_lifetime', 2000000);

// This determines whether or not drush should include a custom settings file which allows
// it to work both within a docker container and natively on the host system.
$drush_settings = __DIR__ . '/ddev_drush_settings.php';
if (empty(getenv('DDEV_PHP_VERSION')) && file_exists($drush_settings)) {
  include $drush_settings;
}


<?php

/**
 #ddev-generated: Automatically generated Drupal settings file.
 ddev manages this file and may delete or overwrite the file unless this comment is removed.
 */

$databases['default']['default'] = array(
  'database' => "db",
  'username' => "db",
  'password' => "db",
  'host' => "db",
  'driver' => "mysql",
  'port' => 3306,
  'prefix' => "drupal7_",
);

ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 200000);
ini_set('session.cookie_lifetime', 2000000);

$drupal_hash_salt = 'LtGolrnDAHZaDFDdyieHHdGAgEvdjbNMVvaMVQvcNMvxVwLjMaLBPqcUUIPdcykt';

// This determines whether or not drush should include a custom settings file which allows
// it to work both within a docker container and natively on the host system.
$drush_settings = __DIR__ . '/ddev_drush_settings.php';
if (empty(getenv('DDEV_PHP_VERSION')) && file_exists($drush_settings)) {
  include $drush_settings;
}

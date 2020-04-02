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
  'prefix' => "drupal9_",
);

ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);
ini_set('session.gc_maxlifetime', 200000);
ini_set('session.cookie_lifetime', 2000000);

$settings['hash_salt'] = 'oocgZufKggjIFTIATsxtRNxNiZwiYvPHnndEmdItXGmQUuxkvLvNORddWzMIDiqM';
$settings['file_scan_ignore_directories'] = [
  'node_modules',
  'bower_components',
];

// This will prevent Drupal from setting read-only permissions on sites/default.
$settings['skip_permissions_hardening'] = TRUE;

// This will ensure the site can only be accessed through the intended host names.
// Additional host patterns can be added for custom configurations.
$settings['trusted_host_patterns'] = ['.*'];

// Don't use Symfony's APCLoader. ddev includes APCu; Composer's APCu loader has better performance.
$settings['class_loader_auto_detect'] = FALSE;

// This specifies the default configuration sync directory.
if (empty($config_directories[CONFIG_SYNC_DIRECTORY])) {
  $config_directories[CONFIG_SYNC_DIRECTORY] = 'sites/default/files/sync';
}

// This determines whether or not drush should include a custom settings file which allows
// it to work both within a docker container and natively on the host system.
$drush_settings = __DIR__ . '/ddev_drush_settings.php';
if (empty(getenv('DDEV_PHP_VERSION')) && file_exists($drush_settings)) {
  include $drush_settings;
}

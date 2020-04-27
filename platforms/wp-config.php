<?php

/**
 #ddev-generated: Automatically generated WordPress settings file.
 ddev manages this file and may delete or overwrite the file unless this comment is removed.
 */

/** Authentication Unique Keys and Salts. */
define('AUTH_KEY',         'xOgQHNFkQBZBAJNWlnHRcmSszgeiXdBtKDufpxZknAJNXGYpoLdXadJqkAgTKLLT');
define('SECURE_AUTH_KEY',  'ADSoCOgQVFJIscDpjjFLUHNFCivniyWDJkfnFsPQfgxMMZkNSqOCSbftIfHIBVqs');
define('LOGGED_IN_KEY',    'XgpzfZmmDUsOcDzrbseECCkRDBeQopMjDcbnHCAWQFoqScLnvUEhHvvIdnjUhgSj');
define('NONCE_KEY',        'YyFubYgQTJgefEqIaBxdmobVgBNKIuatYnqYkrvyYBtmGJBoFZbQydZdjEHGSOZl');
define('AUTH_SALT',        'BjZpXmyCERCuNOeCPeQdrhJompfdWchLYpJJYqZRxHNhjkuFMIOCiCsnUzquuzeE');
define('SECURE_AUTH_SALT', 'VxAUlmnojQipTrMjGzaVoBUffFHRzciDvLzoSeRVrAXwqgkSDbxkRSJYAQszPlFx');
define('LOGGED_IN_SALT',   'PpigrYRrlseUKuBZqiavUgtTkCjAaCBxdajDYYzPmefkqKYmglHZzgWPzAWiIbnr');
define('NONCE_SALT',       'WbDlQhyDAFAdPWQIzUqxnbtLVxALkEceAwxiMnRzyyEsYhiymZxNAQMewJbyIbfR');

/** Absolute path to the WordPress directory. */
define('ABSPATH', dirname(__FILE__) . '/');

// Include for settings managed by ddev.
$ddev_settings = dirname(__FILE__) . '/wp-config-ddev.php';
if (is_readable($ddev_settings) && !defined('DB_USER')) {
	require_once($ddev_settings);
}

/** Include wp-settings.php */
if (file_exists(ABSPATH . '/wp-settings.php')) {
	require_once ABSPATH . '/wp-settings.php';
}

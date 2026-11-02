<?php
define('DB_NAME','cas_access'); define('DB_USER','root'); define('DB_PASSWORD',''); define('DB_HOST','127.0.0.1:3306'); define('DB_CHARSET','utf8mb4'); $table_prefix='ca_';
define('WP_ENVIRONMENT_TYPE','local');define('WP_HOME','http://localhost:18089'); define('WP_SITEURL',WP_HOME); define('WP_HTTP_BLOCK_EXTERNAL',true); define('DISABLE_WP_CRON',true); define('WP_DEBUG',false);define('CAS_BUNNY_UPLOAD_CONFIG','/scratch/config/bunny.php');define('CAS_BUNNY_TOKEN_KEY','synthetic-playback-key'); define('CAS_PRIVATE_DIR','/scratch/private');
foreach(['AUTH_KEY','SECURE_AUTH_KEY','LOGGED_IN_KEY','NONCE_KEY','AUTH_SALT','SECURE_AUTH_SALT','LOGGED_IN_SALT','NONCE_SALT'] as $key)define($key,'synthetic-flow2-local-only-'.$key);
if(!defined('ABSPATH'))define('ABSPATH',__DIR__.'/'); require_once ABSPATH.'wp-settings.php';

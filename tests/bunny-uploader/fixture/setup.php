<?php
define('WP_INSTALLING',true);
require '/scratch/site/wp-load.php';
require_once ABSPATH.'wp-admin/includes/upgrade.php';
wp_install('Synthetic access checks','accessadmin','admin@example.test',true,'','Synthetic-Only-Access-2026');
require_once ABSPATH.'wp-admin/includes/plugin.php';
foreach (['tutor/tutor.php','tutor-pro/tutor-pro.php'] as $plugin) { $r=activate_plugin($plugin,'',false,true); if(is_wp_error($r)){ throw new Exception($r->get_error_message()); } }
activate_plugin('cas-bunny-uploader/cas-bunny-uploader.php','',false,true);switch_theme('hello-elementor-child');
echo json_encode(['version'=>get_bloginfo('version'),'plugins'=>get_option('active_plugins'),'theme'=>get_stylesheet()]);

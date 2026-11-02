<?php
require '/scratch/site/wp-load.php';wp_set_current_user(1);update_option('tutor_version',TUTOR_VERSION);(new \TUTOR\Upgrader())->init_upgrader();global $wpdb;echo json_encode(['version'=>get_option('tutor_version'),'cart_columns'=>$wpdb->get_col("SHOW COLUMNS FROM {$wpdb->prefix}tutor_cart_items")]);

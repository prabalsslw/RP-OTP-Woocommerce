<?php 
/*
 * Removes options from database when plugin is deleted.
 *  
 *
 */

#f uninstall not called from WordPress exit

if (!defined('WP_UNINSTALL_PLUGIN' ))
    exit();

global $wpdb, $wp_version;

$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}real_protection" );
$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}real_protection_woo" );

delete_option("rp_db_version");
delete_option('rp_otp_setting');

wp_cache_flush();

?>
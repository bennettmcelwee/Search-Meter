<?php
/*
Search Meter uninstaller
*/

if (!defined( 'WP_UNINSTALL_PLUGIN')) {
    exit();
}

delete_option('tguy_search_meter');
delete_site_option('tguy_search_meter');  

global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}searchmeter");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}searchmeter_recent");

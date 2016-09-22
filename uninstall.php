<?php
/*
Search Meter uninstaller

Copyright (C) 2005-16 Bennett McElwee (bennett at thunderguy dotcom)
This program is distributed under the terms in the included LICENSE file.
If you would like to use it under different terms, contact the author.
*/

if (!defined( 'WP_UNINSTALL_PLUGIN')) {
    exit();
}

delete_option('tguy_search_meter');
delete_site_option('tguy_search_meter');  

global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}searchmeter");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}searchmeter_recent");

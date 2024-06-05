<?php
/*
Plugin Name: Search Meter
Plugin URI: https://thunderguy.com/semicolon/wordpress/search-meter-wordpress-plugin/
Description: Keeps track of what your visitors are searching for. After you have activated this plugin, you can check the Search Meter section in the Dashboard to see what your visitors are searching for on your blog.
Version: 2.13.5
Author: Bennett McElwee
Author URI: https://thunderguy.com/semicolon/
Donate link: https://thunderguy.com/semicolon/donate/
Text Domain: search-meter
Domain Path: /languages

$Revision: 2843167 $


INSTRUCTIONS

1. Copy this file into the plugins directory in your WordPress installation
   (wp-content/plugins/search-meter/search-meter.php).
2. Log in to WordPress administration. Go to the Plugins section and activate
   this plugin.

* To see search statistics, log in to WordPress Admin, go to the Dashboard
  section and click Search Meter.
* To control search statistics, log in to WordPress Admin, go to the Settings
  section and click Search Meter.
* To display recent and popular searches, use the Recent Searches and
  Popular Searches widgets, or the sm_list_popular_searches() and
  sm_list_recent_searches() template tags.
* For full details, see https://thunderguy.com/semicolon/wordpress/search-meter-wordpress-plugin/

Thanks to everyone who has suggested or contributed improvements. It takes a village to build a plugin.


Copyright (C) 2005-23 Bennett McElwee (bennett at thunderguy dotcom)
This software is licensed under the GPL v3. See the included LICENSE file for
details. If you would like to use it under different terms, contact the author.
*/

add_action( 'plugins_loaded', 'tguy_sm_load_plugin_textdomain' );
function tguy_sm_load_plugin_textdomain() {
  load_plugin_textdomain('search-meter', FALSE, basename(dirname(__FILE__)) . '/languages/');
}

// This is here to avoid E_NOTICE when indexing nonexistent array keys. There's probably a better solution. Suggestions are welcome.
function tguy_sm_array_value(&$array, $key) {
	return (is_array($array) && array_key_exists($key, $array)) ? $array[$key] : null;
}

function tguy_is_admin_interface() {
	// ajax requests return true for is_admin(), but they're not part of the admin UI
	return is_admin() && ( ! defined('DOING_AJAX') || ! DOING_AJAX);
}


if (tguy_is_admin_interface()) {
	require_once dirname(__FILE__) . '/admin.php';
	register_activation_hook(__FILE__, 'tguy_sm_init');
}

// Template Tags


function sm_list_popular_searches($before = '', $after = '', $count = 5) {
// List the most popular searches in the last month in decreasing order of popularity.
	global $wpdb, $wp_rewrite;
	$count = intval($count);
	$escaped_filter_regex = sm_get_escaped_filter_regex();
	$filter_term = ($escaped_filter_regex == "" ? "" : "AND NOT `terms` REGEXP '{$escaped_filter_regex}'");
	// This is a simpler query than the report query, and may produce
	// slightly different results. This query returns searches if they
	// have ever had any hits, even if the last search yielded no hits.
	// This makes for a more efficient search -- important if this
	// function will be used in a sidebar.
	$results = $wpdb->get_results(
		"SELECT `terms`, SUM(`count`) AS countsum
		FROM `{$wpdb->prefix}searchmeter`
		WHERE DATE_SUB( UTC_DATE( ) , INTERVAL 30 DAY ) <= `date`
		AND 0 < `last_hits`
		{$filter_term}
		GROUP BY `terms`
		ORDER BY countsum DESC, `terms` ASC
		LIMIT $count");

	$searches = array();

	foreach ($results as $result) {
		array_push($searches, array(
			'term' => $result->terms,
			'href' => get_search_link($result->terms)
		));
	}

	$display = '';

	if (count($searches)) {
		$display = "$before\n<ul>\n";
		foreach ($searches as $search) {
			$display .= '<li><a href="' . $search['href'] . '">'. htmlspecialchars($search['term']) .'</a></li>'."\n";
		}
		$display .= "</ul>\n$after\n";
	}

	echo apply_filters('sm_list_popular_searches_display', $display, $searches);
}

function sm_list_recent_searches($before = '', $after = '', $count = 5) {
// List the most recent successful searches, ignoring duplicates
	global $wpdb;
	$count = intval($count);
	$escaped_filter_regex = sm_get_escaped_filter_regex();
	$filter_term = ($escaped_filter_regex == "" ? "" : "AND NOT `terms` REGEXP '{$escaped_filter_regex}'");
	$results = $wpdb->get_results(
		"SELECT `terms`, MAX(`datetime`) `maxdatetime`
		FROM `{$wpdb->prefix}searchmeter_recent`
		WHERE 0 < `hits`
		{$filter_term}
		GROUP BY `terms`
		ORDER BY `maxdatetime` DESC
		LIMIT $count");
	if (count($results)) {
		echo "$before\n<ul>\n";
		$home_url_slash = get_option('home') . '/';
		foreach ($results as $result) {
			echo '<li><a href="'. $home_url_slash . sm_get_relative_search_url($result->terms) . '">'. htmlspecialchars($result->terms) .'</a></li>'."\n";
		}
		echo "</ul>\n$after\n";
	}
}

function sm_get_relative_search_url($term) {
// Return the URL for a search term, relative to the home directory.
	global $wp_rewrite;
	$relative_url = null;
	if ($wp_rewrite->using_permalinks()) {
		$structure = $wp_rewrite->get_search_permastruct();
		if (strpos($structure, '%search%') !== false) {
			$relative_url = str_replace('%search%', rawurlencode($term), $structure);
		}
	}
	if ( ! $relative_url) {
		$relative_url =  '?s=' . urlencode($term);
	}
	return $relative_url;
}


function sm_get_escaped_filter_regex() {
// Return a regular expression, escaped to go into a DB query, that will match any terms to be filtered out
	global $sm_escaped_filter_regex, $wpdb;
	if ( ! isset($sm_escaped_filter_regex)) {
		$options = get_option('tguy_search_meter');
		$filter_words = tguy_sm_array_value($options, 'sm_filter_words');
		if ($filter_words == '') {
			$sm_escaped_filter_regex = '';
		} else {
			$filter_regex = str_replace(' ', '|', preg_quote($filter_words));
			$wpdb->escape_by_ref($filter_regex);
			$sm_escaped_filter_regex = $filter_regex;
		}
	}
	return $sm_escaped_filter_regex;
}
$sm_escaped_filter_regex = null;

// Hooks


add_filter('the_posts', 'tguy_sm_save_search', 20); // run after other plugins


// Functionality


// Widgets

add_action('widgets_init', 'tguy_sm_register_widgets');
function tguy_sm_register_widgets() {
	register_widget('SM_Popular_Searches_Widget');
	register_widget('SM_Recent_Searches_Widget');
}

class SM_Popular_Searches_Widget extends WP_Widget {
	function __construct() {
		$widget_ops = array('classname' => 'widget_search_meter', 'description' => __( "A list of the most popular successful searches in the last month", 'search-meter'));
		parent::__construct('popular_searches', __('Popular Searches', 'search-meter'), $widget_ops);
	}

	function widget($args, $instance) {
		extract($args);
		$title = apply_filters('widget_title', empty($instance['popular-searches-title']) ? __('Popular Searches', 'search-meter') : $instance['popular-searches-title']);
		$count = (int) (empty($instance['popular-searches-number']) ? 5 : $instance['popular-searches-number']);

		echo $before_widget;
		if ($title) {
			echo $before_title . $title . $after_title;
		}
		sm_list_popular_searches('', '', sm_constrain_widget_search_count($count));
		echo $after_widget;
	}

	function update($new_instance, $old_instance){
		$instance = $old_instance;
		$instance['popular-searches-title'] = strip_tags(stripslashes($new_instance['popular-searches-title']));
		$instance['popular-searches-number'] = (int) ($new_instance['popular-searches-number']);
		return $instance;
	}

	function form($instance){
		//Defaults
		$instance = wp_parse_args((array) $instance, array('popular-searches-title' => __('Popular Searches', 'search-meter'), 'popular-searches-number' => 5));

		$title = htmlspecialchars($instance['popular-searches-title']);
		$count = htmlspecialchars($instance['popular-searches-number']);

		# Output the options
		echo '<p><label for="' . $this->get_field_name('popular-searches-title') . '">' . __('Title:', 'search-meter') . ' <input class="widefat" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('popular-searches-title') . '" type="text" value="' . $title . '" /></label></p>';
		echo '<p><label for="' . $this->get_field_name('popular-searches-number') . '">' . __('Number of searches to show:', 'search-meter') . ' <input id="' . $this->get_field_id('popular-searches-number') . '" name="' . $this->get_field_name('popular-searches-number') . '" type="text" value="' . $count . '" size="3" /></label></p>';
		echo '<p><small>' . __('Powered by Search Meter', 'search-meter') . '</small></p>';
	}
}

class SM_Recent_Searches_Widget extends WP_Widget {
	function __construct() {
		$widget_ops = array('classname' => 'widget_search_meter', 'description' => __( "A list of the most recent successful searches on your blog", 'search-meter'));
		parent::__construct('recent_searches', __('Recent Searches', 'search-meter'), $widget_ops);
	}

	function widget($args, $instance) {
		extract($args);
		$title = apply_filters('widget_title', empty($instance['recent-searches-title']) ? __('Recent Searches', 'search-meter') : $instance['recent-searches-title']);
		$count = (int) (empty($instance['recent-searches-number']) ? 5 : $instance['recent-searches-number']);

		echo $before_widget;
		if ($title) {
			echo $before_title . $title . $after_title;
		}
		sm_list_recent_searches('', '', sm_constrain_widget_search_count($count));
		echo $after_widget;
	}

	function update($new_instance, $old_instance){
		$instance = $old_instance;
		$instance['recent-searches-title'] = strip_tags(stripslashes($new_instance['recent-searches-title']));
		$instance['recent-searches-number'] = (int) ($new_instance['recent-searches-number']);
		return $instance;
	}

	function form($instance){
		//Defaults
		$instance = wp_parse_args((array) $instance, array('recent-searches-title' => __('Recent Searches', 'search-meter'), 'recent-searches-number' => 5));

		$title = htmlspecialchars($instance['recent-searches-title']);
		$count = htmlspecialchars($instance['recent-searches-number']);

		# Output the options
		echo '<p><label for="' . $this->get_field_name('recent-searches-title') . '">' . __('Title:', 'search-meter') . ' <input class="widefat" id="' . $this->get_field_id('title') . '" name="' . $this->get_field_name('recent-searches-title') . '" type="text" value="' . $title . '" /></label></p>';
		echo '<p><label for="' . $this->get_field_name('recent-searches-number') . '">' . __('Number of searches to show:', 'search-meter') . ' <input id="' . $this->get_field_id('recent-searches-number') . '" name="' . $this->get_field_name('recent-searches-number') . '" type="text" value="' . $count . '" size="3" /></label></p>';
		echo '<p><small>' . __('Powered by Search Meter', 'search-meter') . '</small></p>';
	}
}

function sm_constrain_widget_search_count($number) {
	return max(1, min((int)$number, 100));
}

// Keep track of how many times this search has been saved.
// The save function may be called many times; normally we only save the first time.
$tguy_sm_save_count = 0;

function tguy_sm_save_search($posts) {
// Check if the request is a search, and if so then save details.
// This is a filter but does not change the posts.
	global $wpdb, $wp_query, $tguy_sm_save_count;

	// The filter may get called more than once for a given request. We ignore these duplicates.
	// Recording duplicate searches can be enabled by adding this line to functions.php:
	//   add_filter('search_meter_record_duplicates', function() { return true; });
	// Setting to true will record duplicates (the fact that it's a dupe will be recorded in the
	// details). This will mess up the stats, but could be useful for troubleshooting.
	$record_duplicates = apply_filters('search_meter_record_duplicates', false);

	if (is_search()
	&& !is_paged() // not the second or subsequent page of a previously-counted search
	&& !tguy_is_admin_interface() // not using the administration console
	&& (0 === $tguy_sm_save_count || $record_duplicates)
	&& (tguy_sm_array_value($_SERVER, 'HTTP_REFERER')) // proper referrer (otherwise could be search engine, cache...)
	) {
		$options = get_option('tguy_search_meter');

		// Break out if we're supposed to ignore admin searches
		if (tguy_sm_array_value($options, 'sm_ignore_admin_search') && current_user_can("manage_options")) {
			return $posts; // EARLY EXIT
		}

		// Get all details of this search
		// search string is the raw query
		$search_string = $wp_query->query_vars['s'];
		// search terms is the words in the query
		$search_terms = $search_string;
		$search_terms = preg_replace('/[," ]+/', ' ', $search_terms);
		$search_terms = trim($search_terms);
		$hit_count = $wp_query->found_posts; // Thanks to Will for this line
		// Other useful details of the search
		$details = '';
		if (tguy_sm_array_value($options, 'sm_details_verbose')) {
			if ($record_duplicates) {
				$details .= __('Search Meter save count', 'search-meter') . ": $tguy_sm_save_count\n";
			}
			foreach (array('REQUEST_URI','REQUEST_METHOD','QUERY_STRING','REMOTE_ADDR','HTTP_USER_AGENT','HTTP_REFERER')
			         as $header) {
				$details .= $header . ': ' . tguy_sm_array_value($_SERVER, $header) . "\n";
			}
		}

		// Save the individual search to the DB
		$success = $wpdb->query($wpdb->prepare("
			INSERT INTO `{$wpdb->prefix}searchmeter_recent` (`terms`,`datetime`,`hits`,`details`)
			VALUES (%s, UTC_TIMESTAMP(), %d, %s)",
			$search_string,
			$hit_count,
			$details
		));

		if ($success) {

			$rowcount = $wpdb->get_var(
				"SELECT count(`datetime`) as rowcount
				FROM `{$wpdb->prefix}searchmeter_recent`");

			// History size can be overridden by a user by adding a line like this to functions.php:
			//   add_filter('search_meter_history_size', function() { return 50000; });
			$history_size = apply_filters('search_meter_history_size', 500);

			// Ensure history table never grows larger than (history size) + 100; truncate it
			// to (history size) when it gets too big. (This we way will only truncate the table
			// every 100 searches, rather than every time.)
			if ($history_size + 100 < $rowcount)
			{
				// find time of ($history_size)th entry; delete everything before that
				$dateZero = $wpdb->get_var($wpdb->prepare(
					"SELECT `datetime`
					FROM `{$wpdb->prefix}searchmeter_recent`
					ORDER BY `datetime` DESC LIMIT %d, 1", $history_size));

				$query = "DELETE FROM `{$wpdb->prefix}searchmeter_recent` WHERE `datetime` < '$dateZero'";
				$success = $wpdb->query($query);
			}
		}
		// Save search summary into the DB.
		$wpdb->query($wpdb->prepare("
			INSERT INTO `{$wpdb->prefix}searchmeter` (`terms`,`date`,`count`,`last_hits`)
			VALUES (%s, UTC_DATE(), 1, %d)
			ON DUPLICATE KEY UPDATE `count` = `count` + 1, `last_hits` = VALUES(`last_hits`)",
			$search_terms,
			$hit_count
		));
		++$tguy_sm_save_count;
	}
	return $posts;
}

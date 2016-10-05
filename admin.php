<?php
/*
Copyright (C) 2005-15 Bennett McElwee (bennett at thunderguy dotcom)

This program is free software; you can redistribute it and/or
modify it under the terms of version 2 of the GNU General Public
License as published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details, available at
http://www.gnu.org/copyleft/gpl.html
*/

//////// Parameters


define('TGUY_SM_DEFAULT_VIEW_STATS_CAPABILITY', 'publish_posts');
// Default capability users must have in order to see stats.

define('TGUY_SM_OPTIONS_CAPABILITY', 'manage_options');
// Capability users must have in order to set options.


//////// General admin


add_action('admin_head', 'tguy_sm_stats_css');

function tguy_sm_stats_css() {
?>
<style type="text/css">
* {
    -webkit-box-sizing: border-box;
    -moz-box-sizing: border-box;
    box-sizing: border-box;
}
.wp-filter {
    margin-bottom: 0;
}
.row {
    margin-right: -20px;
    margin-left: -20px;
}
.row:after {
    clear: both;
}
.row:after, .row:before {
    display: table;
    content: " ";
}
div.sm-stats-table {
	float: left;
	width: 33.33333333%;
	padding-right: 20px;
    padding-left: 20px;
	padding-bottom: 1.5em;
}

div.sm-stats-table table{
	width:100%;
}
div.sm-stats-table th, div.sm-stats-table td {
	padding-right: 0.5em;
}
div.sm-stats-table h3 {
	margin-top: 0;
	margin-bottom: 0.5em;
}
div.sm-stats-table .sm-text {
	text-align: left;
}
div.sm-stats-table .sm-number {
	text-align: right;
}
div.sm-stats-clear {
	clear: both;
}

/* Dashboard widget overrides */
#dashboard_search_meter h4 {
	line-height: 1.7em;
}
#dashboard_search_meter div.sm-stats-table {
	float: none;
	padding-bottom: 0;
	padding-right: 0;
}
#dashboard_search_meter div.sm-stats-table th {
	color: #8F8F8F;
}
#dashboard_search_meter ul.subsubsub {
	float: none;
}

</style>
<?php
}


//////// Initialisation


function tguy_sm_init() {
	tguy_sm_create_summary_table();
	tguy_sm_create_recent_table();
}

function tguy_sm_create_summary_table() {
// Create the table if not already there.
	global $wpdb;
	$table_name = $wpdb->prefix . "searchmeter";
	if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
		dbDelta("
			CREATE TABLE `{$table_name}` (
				`terms` VARCHAR(50) NOT NULL,
				`date` DATE NOT NULL,
				`count` INT(11) NOT NULL,
				`last_hits` INT(11) NOT NULL,
				PRIMARY KEY (`terms`,`date`)
			)
			CHARACTER SET utf8 COLLATE utf8_general_ci;
			");
	}
}

function tguy_sm_create_recent_table() {
// Create the table if not already there.
	global $wpdb;
	$table_name = $wpdb->prefix . "searchmeter_recent";
	if ($wpdb->get_var("show tables like '$table_name'") != $table_name) {
		require_once(ABSPATH . '/wp-admin/includes/upgrade.php');
		dbDelta("
			CREATE TABLE `{$table_name}` (
				`terms` VARCHAR(50) NOT NULL,
				`datetime` DATETIME NOT NULL,
				`hits` INT(11) NOT NULL,
				`details` TEXT NOT NULL,
				KEY `datetimeindex` (`datetime`)
			)
			CHARACTER SET utf8 COLLATE utf8_general_ci;
			");
	}
}


//////// Permissions


function smcln_sm_can_view_stats() {
	$options = get_option('tguy_search_meter');
	$view_stats_capability = tguy_sm_array_value($options, 'sm_view_stats_capability');
	if ($view_stats_capability == '') {
		$view_stats_capability = TGUY_SM_DEFAULT_VIEW_STATS_CAPABILITY;
	}
	return current_user_can($view_stats_capability);
}


//////// Dashboard widget

add_action('wp_dashboard_setup', 'smcln_sm_dashboard');

// Add the widget to the dashboard
function smcln_sm_dashboard() {
	if (smcln_sm_can_view_stats()) {
		wp_add_dashboard_widget( 'dashboard_search_meter', __( 'Search Meter', 'search-meter' ), 'smcln_sm_summary');
	}
}

// Render the summary widget
function smcln_sm_summary() {
?>
	<div class="">
		<h4><?php _e( 'Searches in the Last 7 Days', 'search-meter' ); ?></h4>
		<?php tguy_sm_summary_table(7); ?>
	</div>
	<ul class="subsubsub">
		<li><a href="index.php?page=<?php echo plugin_basename(__FILE__); ?>"><?php _e( 'Full Dashboard', 'search-meter' ); ?></a></li>
		<?php if (current_user_can(TGUY_SM_OPTIONS_CAPABILITY)) : ?>
		<li> | <a href="options-general.php?page=<?php echo plugin_basename(__FILE__); ?>"><?php _e( 'Settings', 'search-meter' ); ?></a></li>
		<?php endif; ?>
	</ul>
<?php
}


//////// Admin pages


add_action('admin_menu', 'tguy_sm_add_admin_pages');

function tguy_sm_add_admin_pages() {
	$options = get_option('tguy_search_meter');
	$view_stats_capability = tguy_sm_array_value($options, 'sm_view_stats_capability');
	if ($view_stats_capability == '') {
		$view_stats_capability = TGUY_SM_DEFAULT_VIEW_STATS_CAPABILITY;
	}
	add_submenu_page('index.php', __( 'Search Meter', 'search-meter' ), __( 'Search Meter', 'search-meter' ), $view_stats_capability, __FILE__, 'tguy_sm_stats_page');
	add_options_page(__( 'Search Meter', 'search-meter' ), __( 'Search Meter', 'search-meter' ), TGUY_SM_OPTIONS_CAPABILITY, __FILE__, 'tguy_sm_options_page');
}


//////// Statistics pages


function tguy_sm_stats_page() {
	if (array_key_exists('recent', $_GET)) {
		$recent_count = intval($_GET['recent']);
		$do_show_details = array_key_exists('details', $_GET) && $_GET['details'];
		tguy_sm_recent_page($recent_count, $do_show_details);
	} else {
		tguy_sm_summary_page();
	}
}

function tguy_sm_summary_page() {
	global $wpdb;

	$options = get_option('tguy_search_meter');
	$is_disable_donation = $options['sm_disable_donation'];

	// Delete old records
	$result = $wpdb->query(
	"DELETE FROM `{$wpdb->prefix}searchmeter`
	WHERE `date` < DATE_SUB( CURDATE() , INTERVAL 30 DAY)");
	echo "<!-- Search Meter: deleted $result old rows -->\n";
	?>
	<div class="wrap">
		<h1><?php _e( 'Search summary', 'search-meter' ); ?></h1>
		<div class="wp-filter">
			<ul class="filter-links" id="search_meter_menu2">
				<li><a href="index.php?page=<?php echo plugin_basename(__FILE__); ?>" class="current"><?php _e( 'Summary', 'search-meter' ); ?></a></li>
				<li><a href="index.php?page=<?php echo plugin_basename(__FILE__); ?>&amp;recent=100"><?php _e( 'Last 100 Searches', 'search-meter' ); ?></a></li>
				<li><a href="index.php?page=<?php echo plugin_basename(__FILE__); ?>&amp;recent=500"><?php _e( 'Last 500 Searches', 'search-meter' ); ?></a></li>
			</ul>
		</div>
		<p><?php _e( 'These tables show the most popular searches on your blog for the given time periods. <strong>Term</strong> is the text that was searched for; you can click it to see which posts contain that term. (This won\'t be counted as another search.) <strong>Searches</strong> is the number of times the term was searched for. <strong>Results</strong> is the number of posts that were returned from the <em>last</em> search for that term.', 'search-meter' ); ?></p>
		
		<div class="row">
			<div class="sm-stats-table">
				<h3><?php _e( 'Yesterday and today', 'search-meter' ); ?></h3>
				<?php tguy_sm_summary_table(1); ?>
			</div>
			<div class="sm-stats-table">
				<h3><?php _e( 'Last 7 days', 'search-meter' ); ?></h3>
				<?php tguy_sm_summary_table(7); ?>
			</div>
			<div class="sm-stats-table last">
				<h3><?php _e( 'Last 30 days', 'search-meter' ); ?></h3>
				<?php tguy_sm_summary_table(30); ?>
			</div>
		</div>

		<h2><?php _e( 'Unsuccessful search summary', 'search-meter' ); ?></h2>
		<p><?php _e( 'These tables show only the search terms for which the last search yielded no results. People are searching your blog for these terms; maybe you should give them what they want.', 'search-meter' ); ?></p>
		<div class="row">
			<div class="sm-stats-table">
				<h3><?php _e( 'Yesterday and today', 'search-meter' ); ?></h3>
				<?php tguy_sm_summary_table(1, false); ?>
			</div>
			<div class="sm-stats-table">
				<h3><?php _e( 'Last 7 days', 'search-meter' ); ?></h3>
				<?php tguy_sm_summary_table(7, false); 	?>
			</div>
			<div class="sm-stats-table">
				<h3><?php _e( 'Last 30 days', 'search-meter' ); ?></h3>
				<?php tguy_sm_summary_table(30, false); ?>
			</div>
		</div>
		
		<?php if (current_user_can(TGUY_SM_OPTIONS_CAPABILITY)) : ?>
		<div class="notice inline notice-info">
			<h2><?php _e( 'Notes', 'search-meter' ); ?></h2>
			<p><?php printf( __( 'To manage your search statistics, go to the <a href="options-general.php?page=%1$s">Search Meter Settings</a>.', 'search-meter' ), plugin_basename(__FILE__) ); ?></p>
			<p><?php printf( __( 'For information and updates, see the <a href="%1$s">Search Meter home page</a>. At that page, you can also offer suggestions, request new features or report problems.', 'search-meter' ), 'http://thunderguy.com/semicolon/wordpress/search-meter-wordpress-plugin/' ); ?></p>
			<?php if (!$options['sm_disable_donation']) { tguy_sm_show_donation_message(); } ?>
		</div>
		<?php endif; ?>
	</div>
	<?php
}

function tguy_sm_summary_table($days, $do_include_successes = true) {
	global $wpdb;
	// Explanation of the query:
	// We group by terms, because we want all rows for a term to be combined.
	// For the search count, we simply SUM the count of all searches for the term.
	// For the result count, we only want the number of results for the latest search. Each row
	// contains the result for the latest search on that row's date. So for each date,
	// CONCAT the date with the number of results, and take the MAX. This gives us the
	// latest date combined with its hit count. Then strip off the date with SUBSTRING.
	// This Rube Goldberg-esque procedure should work in older MySQL versions that
	// don't allow subqueries. It's inefficient, but that doesn't matter since it's
	// only used in admin pages and the tables involved won't be too big.
	$hits_selector = $do_include_successes ? '' : 'HAVING hits = 0';
	$results = $wpdb->get_results(
		"SELECT `terms`,
			SUM( `count` ) AS countsum,
			SUBSTRING( MAX( CONCAT( `date` , ' ', `last_hits` ) ) , 12 ) AS hits
		FROM `{$wpdb->prefix}searchmeter`
		WHERE DATE_SUB( CURDATE( ) , INTERVAL $days DAY ) <= `date`
		GROUP BY `terms`
		$hits_selector
		ORDER BY countsum DESC, `terms` ASC
		LIMIT 20");
	if (count($results)) {
		?>
		<table class="widefat">
		<thead>
		<tr class="alternate"><th class="sm-text"><?php _e( 'Term', 'search-meter' ); ?></th><th><?php _e( 'Searches', 'search-meter' ); ?></th>
		<?php
		if ($do_include_successes) {
			?><th><?php _e( 'Results', 'search-meter' ); ?></th><?php
		}
		?></tr>
		</thead>
		<tbody><?php
		$class= '';
		foreach ($results as $result) {
			?>
			<tr class="<?php echo $class ?>">
			<?php if (current_user_can(TGUY_SM_OPTIONS_CAPABILITY)) : ?>
			<td><a href="<?php echo get_bloginfo('wpurl').'/wp-admin/edit.php?s='.urlencode($result->terms).'&submit=Search' ?>"><?php echo htmlspecialchars($result->terms) ?></a></td>
			<?php else: ?>
			<td><?php echo htmlspecialchars($result->terms) ?></td>
			<?php endif; ?>
			<td class="sm-number"><?php echo $result->countsum ?></td>
			<?php
			if ($do_include_successes) {
				?>
				<td class="sm-number"><?php echo $result->hits ?></td></tr>
				<?php
			}
			$class = ($class == '' ? 'alternate' : '');
		}
		?>
		</tbody>
		</table>
		<?php
	} else {
		?><p><em><?php _e( 'No searches recorded for this period.', 'search-meter' ); ?></em></p><?php
	}
}

function tguy_sm_recent_page($max_lines, $do_show_details) {
	global $wpdb;

	$options = get_option('tguy_search_meter');
	$is_details_available = $options['sm_details_verbose'];
	$is_disable_donation = $options['sm_disable_donation'];
	$this_url_base = 'index.php?page=' . plugin_basename(__FILE__);
	$this_url_recent_arg = '&amp;recent=' . $max_lines;
	?>
	<div class="wrap">
		<h1><?php _e('Recent Searches', 'search-meter'); ?></h1>
		<div class="wp-filter">
			<ul class="filter-links" id="search_meter_menu2">
				<li><a href="<?php echo $this_url_base ?>"><?php _e('Summary', 'search-meter'); ?></a></li>
			<?php if (100 == $max_lines) : ?>
				<li><a href="<?php echo $this_url_base ?>&amp;recent=100" class="current"><?php _e('Last 100 Searches', 'search-meter'); ?></a></li>
			<?php else : ?>
				<li><a href="<?php echo $this_url_base ?>&amp;recent=100"><?php _e('Last 100 Searches', 'search-meter'); ?></a></li>
			<?php endif ?>
			<?php if (500 == $max_lines) : ?>
				<li><a href="<?php echo $this_url_base ?>&amp;recent=500" class="current"><?php _e( 'Last 500 Searches', 'search-meter' ); ?></a></li>
			<?php else : ?>
				<li><a href="<?php echo $this_url_base ?>&amp;recent=500"><?php _e( 'Last 500 Searches', 'search-meter' ); ?></a></li>
			<?php endif ?>
			</ul>
		</div>
		<p><?php printf( __( 'This table shows the last %d searches on this blog. <strong>Term</strong> is the text that was searched for; you can click it to see which posts contain that term. (This won\'t be counted as another search.) <strong>Results</strong> is the number of posts that were returned from the search.', 'search-meter' ), $max_lines ); ?></p>
		
		<div class="row">
			<div class="sm-stats-table">
			<?php
			$query = 
				"SELECT `datetime`, `terms`, `hits`, `details`
				FROM `{$wpdb->prefix}searchmeter_recent`
				ORDER BY `datetime` DESC, `terms` ASC
				LIMIT $max_lines";
			$results = $wpdb->get_results($query);
			if (count($results)) {
				?>
				<table class="widefat">
				<thead>
				<tr class="alternate"><th class="sm-text"><?php _e( 'Date &amp; time', 'search-meter' ); ?></th><th class="sm-text"><?php _e( 'Term', 'search-meter' ); ?></th><th class="sm-number"><?php _e( 'Results', 'search-meter' ); ?></th>
				<?php if ($do_show_details) { ?>
					<th class="sm-text"><?php _e( 'Details', 'search-meter' ); ?></th>
				<?php } else if ($is_details_available) { ?>
					<th class="sm-text"><a href="<?php echo $this_url_base . $this_url_recent_arg . '&amp;details=1' ?>"><?php _e( 'Show details', 'search-meter' ); ?></a></th>
				<?php } ?>
				</tr>
				</thead>
				<tbody>
				<?php
				$class= '';
				foreach ($results as $result) {
					?>
					<tr valign="top" class="<?php echo $class ?>">
					<td><?php echo $result->datetime ?></td>
					<?php if (current_user_can(TGUY_SM_OPTIONS_CAPABILITY)) : ?>
					<td><a href="<?php echo get_bloginfo('wpurl').'/wp-admin/edit.php?s='.urlencode($result->terms).'&submit=Search' ?>"><?php echo htmlspecialchars($result->terms) ?></a></td>
					<?php else: ?>
					<td><?php echo htmlspecialchars($result->terms) ?></td>
					<?php endif; ?>
					<td class="sm-number"><?php echo $result->hits ?></td>
					<?php if ($do_show_details) : ?>
						<td><?php echo str_replace("\n", "<br />", htmlspecialchars($result->details)) ?></td>
					<?php endif ?>
					</tr>
					<?php
					$class = ($class == '' ? 'alternate' : '');
				}
				?>
				</tbody>
				</table>
				<?php
			} else {
				?><p><?php _e( 'No searches recorded.', 'search-meter' ); ?></p><?php
			}
			?>
			</div>
		</div>
		
		<?php if (current_user_can(TGUY_SM_OPTIONS_CAPABILITY)) : ?>
		<div class="notice inline notice-info">
			<h2><?php _e( 'Notes', 'search-meter' ); ?></h2>
			<p><?php printf( __( 'To manage your search statistics, go to the <a href="options-general.php?page=%1$s">Search Meter Settings</a>.', 'search-meter' ), plugin_basename(__FILE__) ); ?></p>
			<p><?php printf( __( 'For information and updates, see the <a href="%1$s">Search Meter home page</a>. At that page, you can also offer suggestions, request new features or report problems.', 'search-meter' ), 'http://thunderguy.com/semicolon/wordpress/search-meter-wordpress-plugin/' ); ?></p>
			<?php if (!$options['sm_disable_donation']) { tguy_sm_show_donation_message(); } ?>
		</div>
		<?php endif; ?>
	</div>
	<?php
}


//////// Plugins page


// Add settings link on plugin page
add_filter('plugin_action_links_'.plugin_basename(dirname(__FILE__).'/search-meter.php'), 'tguy_sm_settings_link' );

function tguy_sm_settings_link($links) {
	if (current_user_can(TGUY_SM_OPTIONS_CAPABILITY)) {
		$settings_link = '<a href="options-general.php?page='.plugin_basename(__FILE__).'">'.__('Settings','search-meter').'</a>'; 
		array_unshift($links, $settings_link);
	}
	return $links; 
}


//////// Options page


function tguy_sm_options_page() {
	if (isset($_POST['submitted'])) {
		check_admin_referer('search-meter-update-options_all');
		$options = get_option('tguy_search_meter');
		$options['sm_view_stats_capability']  = ($_POST['sm_view_stats_capability']);
		$sm_filter_words = $_POST['sm_filter_words'];
		if (get_magic_quotes_gpc()) {
			$sm_filter_words = stripslashes($sm_filter_words);
		}
		$options['sm_filter_words']  = preg_replace('/\\s+/', ' ', trim($sm_filter_words));
		$options['sm_ignore_admin_search']  = (bool)($_POST['sm_ignore_admin_search']);
		$options['sm_details_verbose']  = (bool)($_POST['sm_details_verbose']);
		$options['sm_disable_donation'] = (bool)($_POST['sm_disable_donation']);
		update_option('tguy_search_meter', $options);
		echo '<div id="message" class="updated fade"><p><strong>'.__('Plugin settings saved','search-meter').'.</strong></p></div>';
	} else if (isset($_POST['tguy_sm_reset'])) {
		check_admin_referer('search-meter-reset-stats');
		tguy_sm_reset_stats();
		echo '<div id="message" class="updated fade"><p><strong>'.__('Statistics have been reset','search-meter').'.</strong></p></div>';
	}
	$options = get_option('tguy_search_meter');
	$view_stats_capability = tguy_sm_array_value($options, 'sm_view_stats_capability');
	if ($view_stats_capability == '') {
		$view_stats_capability = TGUY_SM_DEFAULT_VIEW_STATS_CAPABILITY;
	}
	?>
	<div class="wrap">

		<h2><?php _e( 'Search Meter Settings', 'search-meter' ); ?></h2>

		<form name="searchmeter" action="" method="post">
			<?php
			if (function_exists('wp_nonce_field')) {
				wp_nonce_field('search-meter-update-options_all');
			}
			?>

			<input type="hidden" name="submitted" value="1" />
			

			<table class="form-table">
				<tr>
					<th scope="row"><?php _e( 'Show statistics to', 'search-meter' ); ?></th>
					<td>
						<fieldset>
						<label title='Users with "read" capability'>
							<input type="radio" name="sm_view_stats_capability" value="read"
								<?php echo ($view_stats_capability=='read'?"checked=\"checked\"":"") ?> />
							<?php _e( 'All logged-in users', 'search-meter' ); ?></label><br>
						<label title='Users with "publish_posts" capability'>
							<input type="radio" name="sm_view_stats_capability" value="publish_posts" 
								<?php echo ($view_stats_capability=='publish_posts'?"checked=\"checked\"":"") ?> />
							<?php _e( 'Post authors and administrators', 'search-meter' ); ?></label><br>
						<label title='Users with "manage_options" capability'>
							<input type="radio" name="sm_view_stats_capability" value="manage_options" 
								<?php echo ($view_stats_capability=='manage_options'?"checked=\"checked\"":"") ?> />
							<?php _e( 'Administrators only', 'search-meter' ); ?></label>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e( 'Search filter', 'search-meter' ); ?></th>
					<td>
						<fieldset>
						<label for="sm_filter_words"><?php _e( 'When a search term contains any of these words, it will be filtered and will not show up in the Recent Searches or Popular Searches widgets. This will match inside words, so "press" will match "WordPress".', 'search-meter' ); ?></label>
						<textarea name="sm_filter_words" rows="3" cols="40" id="sm_filter_words" class="large-text code"><?php echo esc_html(tguy_sm_array_value($options, 'sm_filter_words')); ?></textarea>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th class="th-full" scope="row" colspan="2">
						<label for="sm_ignore_admin_search" title='Administrators are users with "manage_options" capability'>
							<input type="checkbox" id="sm_ignore_admin_search" name="sm_ignore_admin_search" <?php echo (tguy_sm_array_value($options, 'sm_ignore_admin_search') ? 'checked="checked"' : '') ?> />
							<?php _e( 'Ignore searches made by logged-in administrators', 'search-meter' ); ?>
						</label>
					</th>
				</tr>
				<tr>
					<th class="th-full" scope="row" colspan="2">
						<label for="sm_details_verbose">
							<input type="checkbox" id="sm_details_verbose" name="sm_details_verbose" <?php echo (tguy_sm_array_value($options, 'sm_details_verbose') ? 'checked="checked"' : '') ?> />
							<?php _e( 'Keep detailed information about recent searches (taken from HTTP headers)', 'search-meter' ); ?>
						</label>
					</th>
				</tr>
				<tr>
					<th class="th-full" scope="row" colspan="2">
						<label for="sm_disable_donation">
							<input type="checkbox" id="sm_disable_donation" name="sm_disable_donation" <?php echo (tguy_sm_array_value($options, 'sm_disable_donation') ? 'checked="checked"' : '') ?> />
							<?php _e( 'Hide the "Do you find this plugin useful" box', 'search-meter' ); ?>
						</label>
					</th>
				</tr>
			</table>

			<p class="submit">
				<input name="Submit" class="button-primary" value="<?php _e( 'Save Changes', 'search-meter' ); ?>" type="submit">
			</p>
		</form>
		
		<div class="notice inline notice-warning notice-alt">
			<h3><?php _e( 'Reset statistics', 'search-meter' ); ?></h3>
			<p><?php _e( 'Click this button to reset all search statistics. This will delete all information about previous searches.', 'search-meter' ); ?></p>
			<form name="tguy_sm_admin" action="" method="post">
				<?php
				if (function_exists('wp_nonce_field')) {
					wp_nonce_field('search-meter-reset-stats');
				}
				?>
				<p class="submit">
					<input name="tguy_sm_reset" class="button-secondary delete" value="<?php _e( 'Reset Statistics', 'search-meter' ); ?>" type="submit" onclick="return confirm('<?php _e( 'You are about to delete all saved search statistics.\n  \'Cancel\' to stop, \'OK\' to delete.', 'search-meter' ); ?>');" />
				</p>
			</form>
		</div>
		
		<div class="notice inline notice-info">
			<h3><?php _e( 'Notes', 'search-meter' ); ?></h3>
			<p><?php printf( __( 'To see your search statistics, go to the <a href="index.php?page=%1$s">Search Meter Dashboard</a>.', 'search-meter' ), plugin_basename(__FILE__) ); ?></p>
			<p><?php printf( __( 'For information and updates, see the <a href="%1$s">Search Meter home page</a>. At that page, you can also offer suggestions, request new features or report problems.', 'search-meter' ), 'http://thunderguy.com/semicolon/wordpress/search-meter-wordpress-plugin/' ); ?></p>
			<?php if ( ! tguy_sm_array_value($options, 'sm_disable_donation')) { tguy_sm_show_donation_message(); } ?>
		</div>
	</div>
	<?php
}

function tguy_sm_reset_stats() {
	global $wpdb;
	// Delete all records
	$wpdb->query("DELETE FROM `{$wpdb->prefix}searchmeter`");
	$wpdb->query("DELETE FROM `{$wpdb->prefix}searchmeter_recent`");
}

function tguy_sm_show_donation_message() {
?>
<div style="margin: 0; padding: 10px 20px 10px 0; float: left;">
<?php tguy_sm_show_donation_button() ?>
</div>
<p><strong><?php _e( 'Do you find this plugin useful?', 'search-meter' ); ?></strong><br />
<?php printf( __( 'I write WordPress plugins because I enjoy doing it, but it does take up a lot of my time. If you think this plugin is useful, please consider donating some appropriate amount by clicking the <strong>Donate</strong> button. You can also send <strong>Bitcoins</strong> to address <tt>%1$s</tt>. Thanks!', 'search-meter' ), '1542gqyprvQd7gwvtZZ4x25cPeGWVKg45x' ); ?></p>
<?php
}

function tguy_sm_show_donation_button() {
// I wish PayPal offered a simple little REST-style URL instead of this monstrosity
?><form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="margin:0; padding:0;"
><input name="cmd" value="_s-xclick" type="hidden" style="margin:0; padding:0;"
/><input src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" name="submit" alt="<?php _e( 'Make payments with PayPal - it\'s fast, free and secure!', 'search-meter' ); ?>" border="0" type="image" style="margin:0; padding:0;"
/><input name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
" type="hidden" style="margin:0; padding:0;"
/></form><?php
}

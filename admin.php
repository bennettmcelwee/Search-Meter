<?php
/*
Copyright (C) 2005-16 Bennett McElwee (bennett at thunderguy dotcom)
This software is licensed under the GPL v3. See the included LICENSE file for
details. If you would like to use it under different terms, contact the author.
*/

//////// Parameters


define('TGUY_SM_DEFAULT_VIEW_STATS_CAPABILITY', 'publish_posts');
// Default capability users must have in order to see stats.

define('TGUY_SM_OPTIONS_CAPABILITY', 'manage_options');
// Capability users must have in order to set options.


//////// General admin


add_action('admin_head', 'tguy_sm_stats_css');

// Check for download requests before we output anything else
add_action('init', 'tguy_sm_download', 10);

function tguy_sm_stats_css() {
?>
<style type="text/css">
#search_meter_menu {
	line-height: 1.4em;
	margin: 5px 0 0 0;
	padding: 0;
	border-bottom: 1px solid #aaaaaa;
}
#search_meter_menu li {
	border: 1px solid #cccccc;
	border-bottom: none;
	line-height: 1.4em;
	display: inline-block;
	margin: 0 10px 0 0;
	padding: 0;
	list-style-type: none;
	list-style-image: none;
	list-style-position: outside;
}
#search_meter_menu li.sm-current {
	border-color: #aaaaaa;
}
#search_meter_menu li.sm-current span {
	background-color: #ffffff;
	font-weight: bold;
	padding: 0 5px 1px 5px;
}
#search_meter_menu li a,
#search_meter_menu li a:visited {
	padding: 0 5px;
	text-decoration: none;
}
#search_meter_menu li a:hover {
	background-color: #eaf2fa;
}
div.sm-stats-table {
	float: left;
	padding-right: 3em;
	padding-bottom: 1.5em;
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
		wp_add_dashboard_widget( 'dashboard_search_meter', __('Search Meter', 'search-meter'), 'smcln_sm_summary');
	}
}

// Render the summary widget
function smcln_sm_summary() {
?>
	<div class="sm-stats-table">
		<h4><?php _e('Searches in the Last 7 Days', 'search-meter') ?></h4>
		<?php tguy_sm_summary_table(7); ?>
	</div>
	<ul class="subsubsub">
		<li><a href="index.php?page=<?php echo plugin_basename(__FILE__); ?>"><?php _e('Full Dashboard', 'search-meter') ?></a> |</li>
		<?php if (current_user_can(TGUY_SM_OPTIONS_CAPABILITY)) : ?>
		<li><a href="options-general.php?page=<?php echo plugin_basename(__FILE__); ?>"><?php _e('Settings', 'search-meter') ?></a> |</li>
		<?php endif; ?>
		<li><a href="http://thunderguy.com/semicolon/donate/"><?php _e('Donate', 'search-meter') ?></a></li>
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
	add_submenu_page('index.php', __('Search Meter', 'search-meter'), __('Search Meter', 'search-meter'), $view_stats_capability, __FILE__, 'tguy_sm_stats_page');
	add_options_page(__('Search Meter', 'search-meter'), __('Search Meter', 'search-meter'), TGUY_SM_OPTIONS_CAPABILITY, __FILE__, 'tguy_sm_options_page');
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

		<ul id="search_meter_menu">
		<li class="sm-current"><span><?php _e('Summary', 'search-meter') ?></span></li>
		<li><a href="index.php?page=<?php echo plugin_basename(__FILE__); ?>&amp;recent=100"><?php printf(__('Last %s Searches', 'search-meter'), 100) ?></a></li>
		<li><a href="index.php?page=<?php echo plugin_basename(__FILE__); ?>&amp;recent=500"><?php printf(__('Last %s Searches', 'search-meter'), 500) ?></a></li>
		</ul>

		<h2><?php _e('Search summary', 'search-meter') ?></h2>

		<p><?php
			_e('These tables show the most popular searches on your blog for the given time periods.', 'search-meter');
			echo ' ';
			printf(__('%s is the text that was searched for; you can click it to see which posts contain that term. (This won\'t be counted as another search.)', 'search-meter'), '<strong>' . __('Term', 'search-meter') . '</strong>');
			echo ' ';
			printf(__('%s is the number of times the term was searched for.', 'search-meter'), '<strong>' . __('Searches', 'search-meter') . '</strong>');
			echo ' ';
			printf(__('%s is the number of posts that were returned from the <em>last</em> search for that term.', 'search-meter'), '<strong>' . __('Results', 'search-meter') . '</strong>');
			?>
		</p>

		<div class="sm-stats-table">
		<h3><?php _e('Yesterday and today', 'search-meter') ?></h3>
		<?php tguy_sm_summary_table(1); 	?>
		</div>
		<div class="sm-stats-table">
		<h3><?php _e('Last 7 days', 'search-meter') ?></h3>
		<?php tguy_sm_summary_table(7); ?>
		</div>
		<div class="sm-stats-table">
		<h3><?php _e('Last 30 days', 'search-meter') ?></h3>
		<?php tguy_sm_summary_table(30); ?>
		</div>
		<div class="sm-stats-clear"></div>

		<h2><?php _e('Unsuccessful search summary', 'search-meter') ?></h2>

		<p><?php _e('These tables show only the search terms for which the last search yielded no results. People are searching your blog for these terms; maybe you should give them what they want.', 'search-meter') ?></p>

		<div class="sm-stats-table">
		<h3><?php _e('Yesterday and today', 'search-meter') ?></h3>
		<?php tguy_sm_summary_table(1, false); ?>
		</div>
		<div class="sm-stats-table">
		<h3><?php _e('Last 7 days', 'search-meter') ?></h3>
		<?php tguy_sm_summary_table(7, false); 	?>
		</div>
		<div class="sm-stats-table">
		<h3><?php _e('Last 30 days', 'search-meter') ?></h3>
		<?php tguy_sm_summary_table(30, false); ?>
		</div>
		<div class="sm-stats-clear"></div>

		<h3><?php _e('Download summary', 'search-meter') ?></h3>

		<p><?php _e('Download your 30-day summary as a CSV file, which can be opened by any spreadsheet program or text editor.', 'search-meter') ?></p>

		<form name="tguy_sm_admin" action="" method="post">
			<?php
			if (function_exists('wp_nonce_field')) {
				wp_nonce_field('search-meter-download');
			}
			?>
			<p class="submit">
				<input name="tguy_sm_download_summary" class="button-secondary" value="<?php esc_attr_e('Download Summary', 'search-meter') ?>" type="submit" />
			</p>
		</form>

		<h2><?php _e('Notes', 'search-meter') ?></h2>

		<?php if (current_user_can(TGUY_SM_OPTIONS_CAPABILITY)) : ?>
			<p><?php printf(__('To manage your search statistics, go to %s.', 'search-meter'), '<a href="options-general.php?page=' . plugin_basename(__FILE__) . '">' . __('Search Meter Settings', 'search-meter') . '</a>') ?></p>
		<?php endif; ?>

		<p><?php
			printf(__('For information and updates, see the %s.', 'search-meter'), '<a href="http://thunderguy.com/semicolon/wordpress/search-meter-wordpress-plugin/">' . __('Search Meter home page', 'search-meter') . '</a>');
			echo ' ';
			_e('There you can offer suggestions, request new features or report problems.', 'search-meter');
		?></p>

		<?php if (!$options['sm_disable_donation']) { tguy_sm_show_donation_message(); } ?>

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
		<table cellpadding="3" cellspacing="2">
		<tbody>
		<tr class="alternate"><th class="sm-text"><?php _e('Term', 'search-meter') ?></th><th><?php _e('Searches', 'search-meter') ?></th>
		<?php
		if ($do_include_successes) {
			?><th><?php _e('Results', 'search-meter') ?></th><?php
		}
		?></tr><?php
		$class= '';
		foreach ($results as $result) {
			?>
			<tr class="<?php echo $class ?>">
			<td><a href="<?php echo get_bloginfo('wpurl').'/wp-admin/edit.php?s='.urlencode($result->terms).'&submit=Search' ?>"><?php echo htmlspecialchars($result->terms) ?></a></td>
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
		?><p><em><?php _e('No searches recorded for this period.', 'search-meter') ?></em></p><?php
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

		<ul id="search_meter_menu">
		<li><a href="<?php echo $this_url_base ?>"><?php _e('Summary', 'search-meter') ?></a></li>
		<?php if (100 == $max_lines) : ?>
			<li class="sm-current"><span><?php printf(__('Last %s Searches', 'search-meter'), 100) ?></span></li>
		<?php else : ?>
			<li><a href="<?php echo $this_url_base ?>&amp;recent=100"><?php printf(__('Last %s Searches', 'search-meter'), 100) ?></a></li>
		<?php endif ?>
		<?php if (500 == $max_lines) : ?>
			<li class="sm-current"><span><?php printf(__('Last %s Searches', 'search-meter'), 500) ?></span></li>
		<?php else : ?>
			<li><a href="<?php echo $this_url_base ?>&amp;recent=500"><?php printf(__('Last %s Searches', 'search-meter'), 500) ?></a></li>
		<?php endif ?>
		</ul>

		<h2><?php _e('Recent searches', 'search-meter') ?></h2>

		<p><?php
			printf(__('This table shows the last %s searches on this blog.', 'search-meter'), $max_lines);
			echo ' ';
			printf(__('%s is the text that was searched for; you can click it to see which posts contain that term. (This won\'t be counted as another search.)', 'search-meter'), '<strong>' . __('Term', 'search-meter') . '</strong>');
			echo ' ';
			printf(__('%s is the number of posts that were returned from the search.', 'search-meter'), '<strong>' . __('Results', 'search-meter') . '</strong>');
			?>
		</p>

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
			<table cellpadding="3" cellspacing="2">
			<tbody>
			<tr class="alternate"><th class="sm-text"><?php _e('Date &amp; time', 'search-meter') ?></th><th class="sm-text"><?php _e('Term', 'search-meter') ?></th><th class="sm-number"><?php _e('Results', 'search-meter') ?></th>
			<?php if ($do_show_details) { ?>
				<th class="sm-text"><?php _e('Details', 'search-meter') ?></th>
			<?php } else if ($is_details_available) { ?>
				<th class="sm-text"><a href="<?php echo $this_url_base . $this_url_recent_arg . '&amp;details=1' ?>"><?php _e('Show details', 'search-meter') ?></a></th>
			<?php } ?>
			</tr>
			<?php
			$class= '';
			foreach ($results as $result) {
				?>
				<tr valign="top" class="<?php echo $class ?>">
				<td><?php echo tguy_sm_format_utc_as_local('Y-m-d H:i:s', $result->datetime) ?></td>
				<td><a href="<?php echo get_bloginfo('wpurl').'/wp-admin/edit.php?s='.urlencode($result->terms).'&submit=Search' ?>"><?php echo htmlspecialchars($result->terms) ?></a></td>
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
			?><p><?php _e('No searches recorded.', 'search-meter') ?></p><?php
		}
		?>
		</div>
		<div class="sm-stats-clear"></div>

		<h3><?php _e('Download recent searches', 'search-meter') ?></h3>

		<p><?php _e('Download your recent searches as a CSV file, which can be opened by any spreadsheet program or text editor.', 'search-meter') ?></p>

		<form name="tguy_sm_admin" action="" method="post">
			<?php
			if (function_exists('wp_nonce_field')) {
				wp_nonce_field('search-meter-download');
			}
			?>
			<p class="submit">
				<input name="tguy_sm_download_individual" class="button-secondary" value="<?php esc_attr_e('Download Recent Searches', 'search-meter') ?>" type="submit" />
			</p>
		</form>

		<h2><?php _e('Notes', 'search-meter') ?></h2>

		<?php if (current_user_can(TGUY_SM_OPTIONS_CAPABILITY)) : ?>
			<p><?php printf(__('To manage your search statistics, go to %s.', 'search-meter'), '<a href="options-general.php?page=' . plugin_basename(__FILE__) . '">' . __('Search Meter Settings', 'search-meter') . '</a>'); ?></p>
		<?php endif; ?>

		<p><?php
			printf(__('For information and updates, see the %s.', 'search-meter'), '<a href="http://thunderguy.com/semicolon/wordpress/search-meter-wordpress-plugin/">' . __('Search Meter home page', 'search-meter') . '</a>');
			echo ' ';
			_e('There you can offer suggestions, request new features or report problems.', 'search-meter');
		?></p>

		<?php if (!$options['sm_disable_donation']) { tguy_sm_show_donation_message(); } ?>

	</div>
	<?php
}


//////// Plugins page


// Add settings link on plugin page
add_filter('plugin_action_links_'.plugin_basename(dirname(__FILE__).'/search-meter.php'), 'tguy_sm_settings_link' );

function tguy_sm_settings_link($links) {
	if (current_user_can(TGUY_SM_OPTIONS_CAPABILITY)) {
		$settings_link = '<a href="options-general.php?page='.plugin_basename(__FILE__).'">' . __('Settings', 'search-meter') . '</a>';
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
		$options['sm_ignore_admin_search']  = (bool)tguy_sm_array_value($_POST, 'sm_ignore_admin_search');
		$options['sm_details_verbose']  = (bool)tguy_sm_array_value($_POST, 'sm_details_verbose');
		$options['sm_disable_donation'] = (bool)tguy_sm_array_value($_POST, 'sm_disable_donation');
		update_option('tguy_search_meter', $options);
		echo '<div id="message" class="updated fade"><p><strong>' . __('Plugin settings saved.', 'search-meter') . '</strong></p></div>';
	} else if (isset($_POST['tguy_sm_reset'])) {
		check_admin_referer('search-meter-reset-stats');
		tguy_sm_reset_stats();
		echo '<div id="message" class="updated fade"><p><strong>' . __('Statistics have been reset.', 'search-meter') . '</strong></p></div>';
	}
	$options = get_option('tguy_search_meter');
	$view_stats_capability = tguy_sm_array_value($options, 'sm_view_stats_capability');
	if ($view_stats_capability == '') {
		$view_stats_capability = TGUY_SM_DEFAULT_VIEW_STATS_CAPABILITY;
	}
	?>
	<div class="wrap">

		<h2><?php _e('Search Meter Settings', 'search-meter') ?></h2>

		<form name="searchmeter" action="" method="post">
			<?php
			if (function_exists('wp_nonce_field')) {
				wp_nonce_field('search-meter-update-options_all');
			}
			?>

			<input type="hidden" name="submitted" value="1" />

			<table class="form-table">
				<tr>
					<th scope="row"><?php _e('Show statistics to', 'search-meter') ?></th>
					<td>
						<fieldset>
						<label title='<?php esc_attr_e('Users with "read" capability', 'search-meter') ?>'>
							<input type="radio" name="sm_view_stats_capability" value="read"
								<?php echo ($view_stats_capability=='read'?"checked=\"checked\"":"") ?> />
							<?php _e('All logged-in users', 'search-meter') ?></label><br>
						<label title='<?php esc_attr_e('Users with "publish_posts" capability', 'search-meter') ?>'>
							<input type="radio" name="sm_view_stats_capability" value="publish_posts"
								<?php echo ($view_stats_capability=='publish_posts'?"checked=\"checked\"":"") ?> />
							<?php _e('Post authors and administrators', 'search-meter') ?></label><br>
						<label title='<?php esc_attr_e('Users with "manage_options" capability', 'search-meter') ?>'>
							<input type="radio" name="sm_view_stats_capability" value="manage_options"
								<?php echo ($view_stats_capability=='manage_options'?"checked=\"checked\"":"") ?> />
							<?php _e('Administrators only', 'search-meter') ?></label>
						</fieldset>
					</td>
				</tr>
				<tr valign="top">
					<th scope="row"><?php _e('Search filter', 'search-meter') ?></th>
					<td>
						<fieldset>
						<label for="sm_filter_words"><?php _e('When a search term contains any of these words, it will be filtered
						and will not show up in the Recent Searches or Popular Searches widgets. This will match inside words,
						so &#8220;press&#8221; will match &#8220;WordPress&#8221;.', 'search-meter') ?></label>
						<textarea name="sm_filter_words" rows="3" cols="40" id="sm_filter_words" class="large-text code"><?php echo esc_html(tguy_sm_array_value($options, 'sm_filter_words')); ?></textarea>
						</fieldset>
					</td>
				</tr>
				<tr>
					<th class="th-full" scope="row" colspan="2">
						<label for="sm_ignore_admin_search" title='Administrators are users with "manage_options" capability'>
							<input type="checkbox" id="sm_ignore_admin_search" name="sm_ignore_admin_search" <?php echo (tguy_sm_array_value($options, 'sm_ignore_admin_search') ? 'checked="checked"' : '') ?> />
							<?php _e('Ignore searches made by logged-in administrators', 'search-meter') ?>
						</label>
					</th>
				</tr>
				<tr>
					<th class="th-full" scope="row" colspan="2">
						<label for="sm_details_verbose">
							<input type="checkbox" id="sm_details_verbose" name="sm_details_verbose" <?php echo (tguy_sm_array_value($options, 'sm_details_verbose') ? 'checked="checked"' : '') ?> />
							<?php _e('Keep detailed information about recent searches (taken from HTTP headers)', 'search-meter') ?>
						</label>
					</th>
				</tr>
				<tr>
					<th class="th-full" scope="row" colspan="2">
						<label for="sm_disable_donation">
							<input type="checkbox" id="sm_disable_donation" name="sm_disable_donation" <?php echo (tguy_sm_array_value($options, 'sm_disable_donation') ? 'checked="checked"' : '') ?> />
							<?php printf(__('Hide the &#8220;%s&#8221; section.', 'search-meter'), __('Do you find this plugin useful?', 'search-meter')); ?>
						</label>
					</th>
				</tr>
			</table>

			<p class="submit">
				<input name="Submit" class="button-primary" value="<?php esc_attr_e('Save Changes') ?>" type="submit">
			</p>
		</form>

		<h3><?php _e('Reset statistics', 'search-meter') ?></h3>

		<p><?php _e('Click this button to reset all search statistics. This will delete all information about previous searches.', 'search-meter') ?></p>

		<form name="tguy_sm_admin" action="" method="post">
			<?php
			if (function_exists('wp_nonce_field')) {
				wp_nonce_field('search-meter-reset-stats');
			}
			?>
			<p class="submit">
				<input name="tguy_sm_reset" class="button-secondary delete" value="<?php esc_attr_e('Reset Statistics', 'search-meter') ?>" type="submit" onclick="return confirm('You are about to delete all saved search statistics.\n  \'Cancel\' to stop, \'OK\' to delete.');" />
			</p>
		</form>

		<h3><?php _e('Notes', 'search-meter') ?></h3>

		<p><?php printf(__('To see your search statistics, go to the %s.', 'search-meter'), '<a href="index.php?page=' . plugin_basename(__FILE__) . '">' . __('Search Meter Dashboard', 'search-meter') . '</a>') ?></p>

		<p><?php
			printf(__('For information and updates, see the %s.', 'search-meter'), '<a href="http://thunderguy.com/semicolon/wordpress/search-meter-wordpress-plugin/">' . __('Search Meter home page', 'search-meter') . '</a>');
			echo ' ';
			_e('There you can offer suggestions, request new features or report problems.', 'search-meter');
		?></p>

		<?php if ( ! tguy_sm_array_value($options, 'sm_disable_donation')) { tguy_sm_show_donation_message(); } ?>

	</div>
	<?php
}

function tguy_sm_reset_stats() {
	global $wpdb;
	// Delete all records
	$wpdb->query("DELETE FROM `{$wpdb->prefix}searchmeter`");
	$wpdb->query("DELETE FROM `{$wpdb->prefix}searchmeter_recent`");
}

// Service a download request if there is one
function tguy_sm_download() {
	if (isset($_POST['tguy_sm_download_summary'])) {
		check_admin_referer('search-meter-download');
		tguy_sm_download_summary();
	} else if (isset($_POST['tguy_sm_download_individual'])) {
		check_admin_referer('search-meter-download');
		tguy_sm_download_individual();
	}
}
function tguy_sm_download_summary() {
	global $wpdb;
	$results = $wpdb->get_results(
		"SELECT `terms`, `count`, `date`, `last_hits`
		FROM `{$wpdb->prefix}searchmeter`
		ORDER BY `date` ASC, `terms` ASC");
	$results_array = array(array(__('Date', 'search-meter'), __('Search terms', 'search-meter'), __('Searches', 'search-meter'), __('Results', 'search-meter')));
	foreach ($results as $result) {
		$results_array[] = array(tguy_sm_format_utc_as_local('Y-m-d', $result->date), $result->terms, $result->count, $result->last_hits);
	}
	/* translators: base filename for downloaded summary - lowercase letters, digits, dashes only  */
	tguy_sm_download_to_csv($results_array, __('search-summary', 'search-meter'));
}

function tguy_sm_download_individual() {
	global $wpdb;
	$results = $wpdb->get_results(
		"SELECT `terms`, `datetime`, `hits`, `details`
		FROM `{$wpdb->prefix}searchmeter_recent`
		ORDER BY `datetime` ASC");
	$results_array = array(array(__('Date', 'search-meter'), __('Search terms', 'search-meter'), __('Results', 'search-meter'), __('Details', 'search-meter')));
	foreach ($results as $result) {
		$results_array[] = array(tguy_sm_format_utc_as_local('Y-m-d H:i:s', $result->datetime), $result->terms, $result->hits, $result->details);
	}
	/* translators: base filename for downloaded searches - lowercase letters, digits, dashes only  */
	tguy_sm_download_to_csv($results_array, __('recent-searches', 'search-meter'));
}

// Similar to PHP date(), but the timestamp is a string in UTC, and we return a string in Wordpress time zone
function tguy_sm_format_utc_as_local($format, $timestamp = 'now') {
	$tz = get_option('timezone_string');
	$datetime = date_create($timestamp, new DateTimeZone('UTC'));
	if ($tz) {
		$datetime->setTimezone(new DateTimeZone($tz));
		return $datetime->format($format);
	} else {
		return gmdate($format, $datetime->getTimestamp() + get_option('gmt_offset') * HOUR_IN_SECONDS);
	}
}

function tguy_sm_download_to_csv($array, $filenamebase) {
	header('Last-Modified: ' . current_time('D, d M Y H:i:s', true) . ' GMT');
	header('Content-Type: application/csv');
	header('Content-Disposition: attachment; filename="'.$filenamebase.'-'.current_time('Ymd-His').'.csv";');

    // see http://www.php.net/manual/en/wrappers.php.php#refsect2-wrappers.php-unknown-unknown-unknown-descriptioq
    $f = fopen('php://output', 'w');
    foreach ($array as $line) {
        fputcsv($f, $line);
    }
    exit;
}

function tguy_sm_show_donation_message() {
?>
<p><div style="margin: 0; padding: 0 2ex 0.25ex 0; float: left;">
<?php tguy_sm_show_donation_button() ?>
</div>
<strong><?php _e('Do you find this plugin useful?', 'search-meter') ?></strong><br />

<?php printf(__(<<<EOS
I enjoy maintaining Search Meter, but it does take time and effort.
If you think this plugin is useful, please consider donating some appropriate amount by clicking the <strong>Donate</strong> button.
You can also send <strong>Bitcoins</strong> to address %s.
Thanks!
EOS
, 'search-meter' ), '<tt>1542gqyprvQd7gwvtZZ4x25cPeGWVKg45x</tt>') ?>
</p>
<?php
}

function tguy_sm_show_donation_button() {
// I wish PayPal offered a simple little REST-style URL instead of this monstrosity
?><form action="https://www.paypal.com/cgi-bin/webscr" method="post" style="margin:0; padding:0;"
><input name="cmd" value="_s-xclick" type="hidden" style="margin:0; padding:0;"
/><input src="https://www.paypal.com/en_US/i/btn/x-click-but04.gif" name="submit" alt="<?php esc_attr_e('Make payments with PayPal - it\'s fast, free and secure!', 'search-meter') ?>" border="0" type="image" style="margin:0; padding:0;"
/><input name="encrypted" value="-----BEGIN PKCS7-----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-----END PKCS7-----
" type="hidden" style="margin:0; padding:0;"
/></form><?php
}

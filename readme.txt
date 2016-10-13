=== Search Meter ===
Contributors: bennettmcelwee
Donate link: http://thunderguy.com/semicolon/donate/
Tags: search, meter, search-meter, statistics, widget, admin, keywords, terms, search terms
Requires at least: 3.2
Tested up to: 4.6.1
Stable tag: 2.13.1

Search Meter tracks what your readers are searching for on your blog. View full details of recent searches or stats for the last day, week or month.

== Description ==

If you have a Search box on your blog, Search Meter automatically records what people are searching for -- and whether they are finding what they are looking for. Search Meter's admin interface shows you what people have been searching for in the last couple of days, and in the last week or month. It also shows you which searches have been unsuccessful. If people search your blog and get no results, they'll probably go elsewhere. With Search Meter, you'll be able to find out what people are searching for, and give them what they want by creating new posts on those topics.

You can also show your readers what the most popular searches are. The Popular Searches widget displays a configurable list of recent popular successful search terms on your blog, with each term hyperlinked to the actual search results. There's also a Recent Searches widget, which simply displays the most recent searches. If you are happy to edit your theme, both of these functions are also available as template tags.

Search Meter installs easily and requires no configuration. Just install it, activate it, and it starts tracking your visitors' searches.

= View Statistics =

To see your search statistics, Log in to WordPress Admin. On your dashboard you will see a Search Meter widget listing search statistics from the last seven days. For more details, go to the Dashboard menu on the left and click Search Meter. You'll see the most popular searches in the last day, week and month. Click "Last 100 Searches" or "Last 500 Searches" to see lists of all recent searches. You can download the statistics as a file that you can open in Excel or a similar program.

= Manage Statistics =

There are a few options available if you go to the Settings section and click Search Meter. Use the radio buttons to determine who will be allowed to see the full search statistics. You can also type in a list of filter words; any search terms containing these words will not show up in the Recent Searches and Popular Searches widgets.

*Advanced users*: You can check the "Ignore" box to tell Search Meter to ignore searches made by logged-in administrators, so you can test things without cluttering your search statistics. You can also check the "Keep detailed information" checkbox to make Search Meter save technical information about every search (the information is taken from the HTTP headers).

Use the Reset Statistics button to clear all past search statistics; Search Meter will immediately start gathering fresh statistics.

== Installation ==

You can find, download and install Search Meter directly from the **Plugins** section in WordPress.

If you want to install manually, download and unzip the search-meter.zip file and upload to the `/wp-content/plugins/search-meter` directory. Then activate the plugin through the **Plugins** section in WordPress.

= Widgets: Popular and Recent Searches =

The Popular Searches widget displays a list of the most popular successful search terms on your blog during the last 30 days. The Recent Searches widget displays a simple list of the most recent successful search terms. In both cases, the search terms in the lists are hyperlinked to the actual search results; readers can click the search term to show the results for that search. You can configure the title of each widget, and the maximum number of searches that each widget will display.

To add these widgets to your sidebar, log in to WordPress Admin, go to the Appearance section and click Widgets. You can drag the appropriate widget to the sidebar of your choice, and set the title and the number of searches to display.

The widgets only display successful searches, so they will only display words that actually appear in your blog. If you still want to prevent some of these words appearing in the widgets, you can add search filter words in the Search Meter settings page.

= Template Tags =

If you are using an older version of WordPress or an old theme, you may not be able to use the widgets. In any case, you can always use the Search Meter template tags to display the same information. You'll need to edit your theme to use them.

The `sm_list_popular_searches()` template tag displays a list of the 5 most popular successful search terms on your blog during the last 30 days. Each term is a hyperlink; readers can click the search term to show the results for that search. Here are some examples of using this template tag.

`sm_list_popular_searches()`
Show a simple list of the 5 most popular recent successful search terms, hyperlinked to the actual search results.

`sm_list_popular_searches('<h2>Popular Searches</h2>')`
Show the list as above, with the heading "Popular Searches". If there have been no successful searches, then this tag displays no heading and no list.

`sm_list_popular_searches('<li><h2>Popular Searches</h2>', '</li>')`
Show the headed list as above; this form of the tag should be used in the default WordPress theme. Put it in the `sidebar.php` file.

`sm_list_popular_searches('<li><h2>Popular Searches</h2>', '</li>', 10)`
This is the same as the above, but it shows the 10 most popular searches.

`sm_list_recent_searches()`
Show a simple list of the 5 most recent successful search terms, hyperlinked to the actual search results. You can also use the same options as for the `sm_list_popular_searches` tag.

== Frequently Asked Questions ==

= Where can I find out more information? =

The [Search Meter home page](http://thunderguy.com/semicolon/wordpress/search-meter-wordpress-plugin/) has more information and a form to submit comments and questions.

== Screenshots ==

1. The Search Meter administration interface, showing some of the reports available.

== Changelog ==
= 2.13.2 =
* Restore compatibility with some older versions of PHP (probably back to 5.0).

= 2.13.1 =
* Some fixes for text and internationalization.

= 2.13 =
* Search Summary and Recent Searches can be downloaded as CSV files.
* Search Meter is now set up for translation to other languages. For details, go to [translate.wordpress.org](https://translate.wordpress.org/projects/wp-plugins/search-meter). (Thanks to Christiaen François)
* All stats are now displayed in the WordPress time zone, and stored in the database as UTC. Previously, they were stored and displayed in the server time zone, which was confusing. The change means that old search statistics may be out by up to 13 hours.
* Updated licensing, now using GPL3.

= 2.12 =
* When uninstalled, delete all options and data. (Thanks to Scott Allen)
* Track searches made via ajax requests. (Thanks to tliebig)

= 2.11 =
* Settings for search history size and recording duplicates can now be altered in filters. See the code for details. (Thanks to Dan Harrison)
* Fixed a problem with saving hit counts. (Thanks to vrocks)

= 2.10 =
* Add an option to ignore searches made by logged-in administrators, so administrators can test searches without cluttering up the search stats.
* Requires WP 3.2.
* Upgrade deprecated code. Minor restyling.

= 2.9.1 =
* Ensure Search Meter can save searches even if other plugins trigger a query before the main WordPress loop.

= 2.9 =
* Add a Search Meter dashboard widget.
* Add Search Meter settings link on the Plugins page for convenient configuration.
* Many small improvements.

= 2.8 =
* Fix option for permission level, which was not being saved correctly.
* Allow Search Meter to work with Multisite WordPress.
* Add convenient links between Settings and Dashboard pages.
* Clean up dashboard tabs and table layout.
* Add Bitcoin donation address in case you're feeling generous.

= 2.7.3 =
* Remove another warning message.

= 2.7.2 =
* Requires WP 2.8.
* Fix problem displaying multiple-word searches in WP 3.0.
* Remove notice messages when debugging.

= 2.7 =
* Don't show duplicated recent searches.
* Add filter list so that search terms with certain words will not show up in recent and popular search lists.
* Search links work whether or not fancy permalinks are enabled.
* Administrator can decide who is allowed to see full statistics.
* Requires WordPress 2.3 or later.

= 2.6 =
* Use UTF8 when creating tables.
* Fix PHP 5.3 incompatibility.
* Widgets now conform to WordPress 2.8 standards.

= 2.5 =
* Improve formatting on the Options page.
* Fix database error caused by duplicate searches.
* Users of Search Meter version 1 will need to deactivate and reactivate the plugin to use version 2.5.

= 2.4 =
* Fix the links to the Statistics and Options pages, which broke in WordPress 2.7.

= 2.3 =
* Improve widget display and add controls to specify the number of searches to show.
* Add option to hide donation buttons.

= 2.2 =
* Add widgets for Recent Searches and Popular Searches.
* Fix table creation problem on WordPress 2.2.1.
* Add donation buttons (thanks for your consideration).

= 2.1 =
* Improve search count accuracy.

= 2.0 =
* Add Recent Searches page and template tag.
* Make search counts more accurate: correctly count multi-page searches and searches with no referer [sic].
* Popular Searches tag allows number of results to be specified.

= 1.1 =
* Various improvements.

= 1.0 =
* Initial public release.

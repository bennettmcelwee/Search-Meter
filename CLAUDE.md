# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Search Meter is a WordPress plugin that tracks what visitors are searching for on a site. It displays search statistics in the dashboard and provides template tags/blocks to show recent and popular searches on the frontend.

## Commands

### Build the JavaScript blocks
```bash
cd blocks && npm run build
```

### Start the development server for blocks
```bash
cd blocks && npm start
```

### Run WordPress code quality checks
Uses `@wordpress/scripts` package installed in the `blocks/` directory.

## Code Structure

### Core Files

- **search-meter.php** - Main plugin file containing:
  - Template tags: `sm_list_popular_searches()`, `sm_list_recent_searches()`, `sm_render_results()`
  - `tguy_sm_save_search()` - Filters searches and saves them to the database
  - Widget registration: `SM_Popular_Searches_Widget`, `SM_Recent_Searches_Widget`
  - Block registration: `tguy_sm_register_blocks()`

- **admin.php** - Admin interface functionality:
  - Dashboard widget (`smcln_sm_dashboard()`, `smcln_sm_summary()`)
  - Statistics pages (`tguy_sm_stats_page()`, `tguy_sm_summary_page()`, `tguy_sm_recent_page()`)
  - Settings page (`tguy_sm_options_page()`)
  - Download functionality (`tguy_sm_download_summary()`, `tguy_sm_download_individual()`)
  - Database schema creation: `tguy_sm_create_summary_table()`, `tguy_sm_create_recent_table()`

- **uninstall.php** - Cleanup on plugin deactivation

### Blocks Directory

Located in `blocks/` with WordPress Block API (Block.json v3):
- `src/popular-searches/` - Popular searches block (Gutenberg)
- `src/recent-searches/` - Recent searches block (Gutenberg)
- Each block has: `block.json`, `edit.js`, `index.js`, `render.php`, `style.scss`
- Built output in `blocks/build/` directory

### Database Schema

Two main tables (prefixed with WordPress table prefix, typically `wp_`):

1. **`{prefix}searchmeter`** - Summary table
   - `terms` - Search terms
   - `date` - Date of search
   - `count` - Total search count
   - `last_hits` - Result count from last search
   - Primary key: (`terms`, `date`)

2. **`{prefix}searchmeter_recent`** - Recent searches table
   - `terms` - Search terms
   - `datetime` - Timestamp of search
   - `hits` - Result count
   - `details` - HTTP request details (optional, verbose mode)

## Plugin Hooks and Actions

### WordPress Actions
- `wp_dashboard_setup` - Adds dashboard widget
- `admin_menu` - Adds admin pages
- `admin_head` - Adds admin CSS
- `widgets_init` - Registers widgets
- `plugins_loaded` - Loads textdomain
- `init` - Registers blocks

### WordPress Filters
- `the_posts` - `tguy_sm_save_search()` (priority 20) - Captures search queries before rendering

## Options Storage

All options stored in `tguy_search_meter` option key:
- `sm_view_stats_capability` - Who can view stats
- `sm_filter_words` - Terms to filter out from display
- `sm_ignore_admin_search` - Ignore logged-in admin searches
- `sm_details_verbose` - Store detailed request info
- `sm_disable_donation` - Hide donation message

## Text Domain

`search-meter` - All translatable strings use `_e()` and `__()`
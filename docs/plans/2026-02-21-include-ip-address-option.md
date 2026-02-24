# Include IP Address Option Implementation Plan

> **For Claude:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task.

**Goal:** Add a new option "Include IP address" to control whether REMOTE_ADDR is saved to the search details, visible only when "Keep detailed information" is enabled.

**Architecture:**
- Add new option key `sm_include_ip` (boolean, default `false`) to WordPress options
- Update admin settings page to render IP checkbox with disabled state based on verbose setting
- Modify `tguy_sm_save_search()` to conditionally include REMOTE_ADDR header when both `sm_details_verbose` and `sm_include_ip` are true

**Tech Stack:**
- PHP WordPress plugin
- WordPress options API
- WordPress checkbox with disabled attribute

---

## Task 1: Add option save handler

**Files:**
- Modify: `admin.php:509-514`

**Step 1: Add sm_include_ip to options save**

Edit admin.php lines 509-514 to add the new option:

```php
$options['sm_view_stats_capability']  = (@$_POST['sm_view_stats_capability'] ?? '');
$sm_filter_words = $_POST['sm_filter_words'];
$options['sm_filter_words']  = preg_replace('/\\s+/', ' ', trim($sm_filter_words));
$options['sm_ignore_admin_search']  = (bool) @$_POST['sm_ignore_admin_search'];
$options['sm_details_verbose']  = (bool) @$_POST['sm_details_verbose'];
$options['sm_include_ip']  = (bool) @$_POST['sm_include_ip'];  // NEW LINE
$options['sm_disable_donation'] = (bool) @$_POST['sm_disable_donation'];
```

**Step 2: Run tests to verify**

There are no automated tests for this feature. Verify manually:
1. Go to Settings → Search Meter
2. Uncheck "Keep detailed information"
3. Verify IP checkbox is visible but disabled
4. Check IP checkbox and save
5. Verify checkbox remains disabled when verbose is unchecked

**Step 3: Commit**

```bash
git add admin.php
git commit -m "feat: add sm_include_ip option to save handler"
```

---

## Task 2: Add IP checkbox to settings page

**Files:**
- Modify: `admin.php:577-592`

**Step 1: Add checkbox HTML in details fieldset**

Replace the existing details checkbox block (lines 577-583) with:

```php
<tr>
	<th class="th-full" scope="row" colspan="2">
		<label for="sm_details_verbose">
			<input type="checkbox" id="sm_details_verbose" name="sm_details_verbose" <?php echo (@$options['sm_details_verbose'] ? 'checked="checked"' : '') ?> />
			<?php _e('Keep detailed information about recent searches (taken from HTTP headers)', 'search-meter') ?>
		</label>
	</th>
</tr>
<tr>
	<th class="th-full" scope="row" colspan="2">
		<label for="sm_include_ip">
			<input type="checkbox" id="sm_include_ip" name="sm_include_ip"
				<?php echo (@$options['sm_include_ip'] ? 'checked="checked"' : '') ?>
				<?php echo (@$options['sm_details_verbose'] ? '' : 'disabled="disabled"') ?> />
			<?php _e('Include IP address in details', 'search-meter') ?>
		</label>
	</th>
</tr>
```

**Step 2: Verify rendering**

Check that:
1. When `sm_details_verbose` is unchecked: IP checkbox is visible but disabled
2. When `sm_details_verbose` is checked: IP checkbox is enabled
3. Checkbox shows current state from saved options

**Step 3: Commit**

```bash
git add admin.php
git commit -m "feat: add Include IP address checkbox to settings"
```

---

## Task 3: Modify save_search to conditionally include REMOTE_ADDR

**Files:**
- Modify: `search-meter.php:287-290`

**Step 1: Update header loop in tguy_sm_save_search**

Edit lines 287-290 to check `sm_include_ip`:

```php
foreach (['REQUEST_URI','REQUEST_METHOD','QUERY_STRING','HTTP_USER_AGENT','HTTP_REFERER']
         as $header) {
	if ($header === 'REMOTE_ADDR' && !$options['sm_include_ip']) {
		continue;  // Skip REMOTE_ADDR if option is disabled
	}
	$details .= $header . ': ' . @$_SERVER[$header] . "\n";
}
```

**Step 2: Verify behavior**

Test scenarios:
1. **sm_details_verbose = false, sm_include_ip = false**: No headers saved (REMOTE_ADDR not included anyway)
2. **sm_details_verbose = true, sm_include_ip = false**: Headers saved but WITHOUT REMOTE_ADDR
3. **sm_details_verbose = true, sm_include_ip = true**: Headers saved WITH REMOTE_ADDR (current behavior)
4. **Default new install**: No IP in details

**Step 3: Commit**

```bash
git add search-meter.php
git commit -m "feat: conditionally include REMOTE_ADDR based on sm_include_ip option"
```

---

## Summary

Three tasks total:
1. Save handler for `sm_include_ip` option
2. Checkbox UI with disabled state logic
3. Conditional header inclusion in `tguy_sm_save_search()`

The feature is fully backward compatible (new option defaults to false).
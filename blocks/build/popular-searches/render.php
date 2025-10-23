<?php
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */
?>

<?php
	$title = @$attributes['title'] ?? 'Popular Searches';
	$count = (int) (@$attributes['count'] ?? 5);
?>

<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php if ($title) { ?>
		<h2><?php echo esc_html($title) ?></h2>
	<?php } ?>

	<?php sm_list_popular_searches('', '', sm_constrain_widget_search_count($count)); ?>

</div>

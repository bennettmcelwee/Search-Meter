/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';

import { PanelBody, __experimentalText as Text, TextControl } from '@wordpress/components';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */
export default function Edit( { attributes, setAttributes } ) {
    const { title = 'Popular Searches', count = 5 } = attributes;
	return (
        <>
            <InspectorControls>
                <PanelBody title={ __( 'Settings', 'search-meter' ) }>
                    <TextControl
                        label={ __('Title', 'search-meter') }
                        value={ title }
                        onChange={ (value) => setAttributes( { title: value } ) }
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />
                    <TextControl
                        label={ __('Count', 'search-meter') }
                        value={ count }
						type="number"
                        onChange={ (value) => setAttributes( { count: clamp(1, 100, value) } ) }
                        __nextHasNoMarginBottom
                        __next40pxDefaultSize
                    />
                    <Text variant="muted">
                        To customize the appearance of the title, delete it here and instead add a Heading block above this block.
                    </Text>
                </PanelBody>
            </InspectorControls>
			<div { ...useBlockProps() }>
				{title && <h2 class="wp-block-heading">{title}</h2>}
				<ul>
					{Array.from({length: count}, (_, i) => <li key={i}>search{i}</li>)}
				</ul>
			</div>
        </>
	);
}

const clamp = (min, max, value ) => Math.max(min, Math.min(max, value));

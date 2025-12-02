<?php
// This file is generated. Do not modify it manually.
return array(
	'popular-searches' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'search-meter/popular-searches',
		'version' => '0.1.0',
		'title' => 'Popular Searches',
		'category' => 'widgets',
		'icon' => 'editor-ul',
		'description' => 'A list of the most popular successful searches on the site. Powered by Search Meter',
		'example' => array(
			
		),
		'attributes' => array(
			'title' => array(
				'type' => 'string'
			),
			'count' => array(
				'type' => 'number'
			)
		),
		'supports' => array(
			'color' => array(
				'background' => false,
				'text' => true
			),
			'html' => false,
			'typography' => array(
				'fontSize' => true
			)
		),
		'textdomain' => 'search-meter',
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	),
	'recent-searches' => array(
		'$schema' => 'https://schemas.wp.org/trunk/block.json',
		'apiVersion' => 3,
		'name' => 'search-meter/recent-searches',
		'version' => '0.1.0',
		'title' => 'Recent Searches',
		'category' => 'widgets',
		'icon' => 'editor-ul',
		'description' => 'A list of the most recent successful searches on the site. Powered by Search Meter',
		'example' => array(
			
		),
		'attributes' => array(
			'title' => array(
				'type' => 'string'
			),
			'count' => array(
				'type' => 'number'
			)
		),
		'supports' => array(
			'color' => array(
				'background' => false,
				'text' => true
			),
			'html' => false,
			'typography' => array(
				'fontSize' => true
			)
		),
		'textdomain' => 'search-meter',
		'editorScript' => 'file:./index.js',
		'style' => 'file:./style-index.css',
		'render' => 'file:./render.php'
	)
);

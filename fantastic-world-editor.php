<?php

/*
Plugin Name: Fantastic World Editor
*/

class FantasticWorldEditor {
	private static $instance = false;

	public static function getInstance() {
		if ( ! self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function __construct() {
		add_action( 'init', array( $this, 'customPostLocationRegister' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'publicEnqueue' ) );

		add_filter( 'style_loader_tag', array( $this, 'addStyleAttributes' ), 10, 2 );
		add_filter( 'script_loader_tag', array( $this, 'addScriptAttributes' ), 10, 3 );
	}

	public function customPostLocationRegister() {
		register_post_type( 'fwe_location',
			array(
				'labels'      => array(
					'name'          => 'Locations',
					'singular_name' => 'Location',
				),
				'public'      => true,
				'has_archive' => true,
//				'menu_position' => 5,
				'taxonomies'  => array( 'post_tag' ),
				'supports'    => array(
					'title',
					'editor',
					'revision',
					'excerpt'
				),
			)
		);
	}

	public function publicEnqueue() {
		wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.8.0/dist/leaflet.css', array(), null);
		wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.8.0/dist/leaflet.js', array(), null );
	}

	public function addStyleAttributes( $html, $handle ) {
		if ( 'leaflet' === $handle ) {
			return str_replace( 'media="all"', 'integrity="sha512-hoalWLoI8r4UszCkZ5kL8vayOGVae1oxXe/2A4AO6J9+580uKHDO3JdHb7NzwwzK5xr/Fs0W40kiNHxM9vyTtQ==" crossorigin=""', $html );
		}

		return $html;
	}

	public function addScriptAttributes( $tag, $handle, $src ) {
		if ( 'leaflet' === $handle ) {
			return '<script id="' . $handle . '-js" src="' . $src . '" integrity="sha512-BB3hKbKWOc9Ez/TAwyWxNXeoV9c1v6FIeYiBieIWkpLjauysF18NzgR1MBNBXf8/KABdlkX68nAhlwcDFLGPCQ==" crossorigin=""></script>' . "\n";
		}

		return $tag;
	}
}

$FWE_Instance = FantasticWorldEditor::getInstance();

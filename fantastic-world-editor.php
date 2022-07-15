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
}

$FWE_Instance = FantasticWorldEditor::getInstance();

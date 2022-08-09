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
		add_action( 'admin_enqueue_scripts', array( $this, 'adminEnqueue' ) );

		add_action( 'admin_init', array( $this, 'initFWEOptions' ) );
		add_action( 'admin_menu', array( $this, 'addAdminMenu' ) );

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
		wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.8.0/dist/leaflet.css', array(), null );
		wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.8.0/dist/leaflet.js', array(), null );
		wp_enqueue_script( 'fantastic-world-editor', plugins_url( 'public/js/fantastic-world-editor.js', __FILE__ ), array(), null );
	}

	public function adminEnqueue() {
		$this->publicEnqueue();
	}

	public function addAdminMenu() {
		add_menu_page( 'World Map', 'World Map', 'manage_options', 'fwe-world-map', array(
			$this,
			'worldMapMenuHTML'
		) );

		add_options_page(
			'Fantastic World Editor',
			'Fantastic World Editor',
			'manage_options',
			'fwe-options',
			array( $this, 'fweOptionsHTML' )
		);
	}

	function initFWEOptions() {
		register_setting( 'fwe-options', 'fwe-map-url' );

		add_settings_section(
			'fwe-options',
			null,
			null,
			'fwe-options'
		);

		add_settings_field(
			'fwe-map-url',
			'Map URL',
			array( $this, 'textWithMapFieldHTML' ),
			'fwe-options',
			'fwe-options'
		);
	}

	function textWithMapFieldHTML() {
		$options = get_option( 'fwe-map-url' );
		?>
        <input type="text" id="fwe-map-url" name="fwe-map-url"
               value="<?php echo isset( $options ) ? esc_attr( $options ) : ''; ?>" class="regular-text"/>
        <input type="button" id="fwe-test-map-url" class="button" value="Test" onclick="testMapUrl()"/>
        <p>
        <div id="test-map-container" style="max-width: 500px; height: 200px; background-color: lightgray"></div>
        </p>
		<?php
	}

	public function worldMapMenuHTML() {
		?>
        <h1>World Map Title</h1>
        <div id="worldMap" style="height: 700px;"></div>
        <script>FWE.createMap("worldMap");</script>
		<?php
	}

	public function fweOptionsHTML() {
		require_once "admin/partials/fwe-options.php";
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

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

		add_action( 'add_meta_boxes', array( $this, 'addMetaBoxesAction' ) );
		add_action( 'save_post', array( $this, 'savePostAction' ) );

		add_action( 'wp_ajax_getAllLocationGeoJson', array( $this, 'getAllLocationGeoJson' ) );

		add_filter( 'style_loader_tag', array( $this, 'addStyleAttributes' ), 10, 2 );
		add_filter( 'script_loader_tag', array( $this, 'addScriptAttributes' ), 10, 3 );
	}

	public function customPostLocationRegister() {
		register_post_type( 'fwe-location',
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

	public function customPostMarkerRegister() {
		register_post_type( 'fwe-marker',
			array(
				'labels'      => array(
					'name'          => 'Markers',
					'singular_name' => 'Marker',
				),
				'public'      => false,
				'has_archive' => true,
//				'menu_position' => 5,
				'supports'    => array(
					'title',
					'thumbnail'
				),
			)
		);
	}

	function addMetaBoxesAction() {
		add_meta_box(
			'fwe-geo-json',
			'Geo JSON',
			array( $this, 'customPostLocationMetaBoxHTML' ),
			'fwe-location'
		);

	}

	function savePostAction( $post_id ) {
		if ( array_key_exists( 'fwe-geo-json', $_POST ) ) {
			update_post_meta(
				$post_id,
				'fwe-geo-json',
				$_POST['fwe-geo-json']
			);
		}
	}

	function customPostLocationMetaBoxHTML( $post ) {
		$geoJson = get_post_meta( $post->ID, 'fwe-geo-json', true );
		?><label class="screen-reader-text" for="fwe-geo-json">Geo JSON</label>
        <textarea name="fwe-geo-json" id="fwe-geo-json" style="width: 100%;"><?php esc_attr_e( $geoJson ) ?></textarea>
		<?php
	}

	public function publicEnqueue() {
		wp_enqueue_style( 'leaflet', 'https://unpkg.com/leaflet@1.8.0/dist/leaflet.css', array(), null );
		wp_enqueue_script( 'leaflet', 'https://unpkg.com/leaflet@1.8.0/dist/leaflet.js', array(), null );
		wp_enqueue_script( 'fantastic-world-editor', plugins_url( 'public/js/fantastic-world-editor.js', __FILE__ ), array(), null );
		wp_add_inline_script( 'fantastic-world-editor',
			'const FWE_DATA = ' . json_encode( array(
				'mapUrl' => get_option( 'fwe-map-url' )
			) ), 'before' );
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
        <div id="test-map-container" style="max-width: 500px; height: 200px; background-color: lightgray"></div></p>
		<?php
	}

	public function worldMapMenuHTML() {
		?>
        <div class="wrap">
            <h1>World Map Title</h1>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content" style="position: relative;">
                        <div id="worldMap" style="height: 500px;"></div>
                    </div>
                    <div class="postbox-container" style="float: right; margin-right: -300px; width: 280px;">
                        <div id="inspector" class="postbox">
                            <div class="postbox-header"><h2 id="inspector-header">Inspector</h2></div>
                            <div class="inside">
                                <p>Test inspector text</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            var map = FWE.createMap("worldMap");
            jQuery(document).ready(function ($) {
                let data = {
                    action: "getAllLocationGeoJson"
                };
                // #FIXME: Maybe find another way to send geoJSON features as strings so we don't have to do a double JSON.parse()
                $.post(ajaxurl, data, function (response) {
                    let geoJsonFeatures = JSON.parse(response)
                    geoJsonFeatures.forEach(function (geoJsonFeatureString, i) {
                        geoJsonFeatures[i] = JSON.parse(geoJsonFeatureString);
                    });

                    function onEachFeature(feature, layer) {
                        let name = feature.geometry.coordinates;
                        layer.on("click", (e) => {
                            jQuery("#inspector-header").text(name);
                        });
                    }

                    L.geoJSON(geoJsonFeatures, {onEachFeature: onEachFeature}).addTo(map);
                });
            });
        </script>
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

	public function getAllLocationGeoJson() {
		$locations = get_posts( array(
				'post_type'   => 'fwe-location',
				'numberposts' => - 1,
			)
		);

		// #FIXME: this foreach loop probably won't scale with an increasing number of posts. Need to find a way to cache the data
		$geoJson = array();
		foreach ( $locations as $location ) {
			$geoJson[] = get_post_meta( $location->ID, 'fwe-geo-json', true );
		}

		wp_send_json( json_encode( $geoJson ) );
	}
}

$FWE_Instance = FantasticWorldEditor::getInstance();

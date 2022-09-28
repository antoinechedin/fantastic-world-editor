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
		add_action( 'init', array( $this, 'registerCustomPosts' ) );

		add_action( 'wp_enqueue_scripts', array( $this, 'publicEnqueue' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'adminEnqueue' ) );

		add_action( 'admin_init', array( $this, 'initFWEOptions' ) );
		add_action( 'admin_menu', array( $this, 'addAdminMenu' ) );

		add_action( 'add_meta_boxes', array( $this, 'addMetaBoxesAction' ) );
		add_action( 'save_post', array( $this, 'savePostAction' ) );

		add_action( 'wp_ajax_getAllLocationGeoJson', array( $this, 'getAllLocationGeoJson' ) );

		add_filter( 'style_loader_tag', array( $this, 'addStyleAttributes' ), 10, 2 );
		add_filter( 'script_loader_tag', array( $this, 'addScriptAttributes' ), 10, 3 );

		add_filter( 'manage_fwe-marker_posts_columns', array( $this, 'markerCustomColumns' ) );
		add_action( 'manage_posts_custom_column', array( $this, 'renderMarkerCustomColumns' ), 10, 2 );
	}

	public function registerCustomPosts() {
		$this->customPostLocationRegister();
		$this->customPostMarkerRegister();
	}

	private function customPostLocationRegister() {
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

	private function customPostMarkerRegister() {
		register_post_type( 'fwe-marker',
			array(
				'labels'             => array(
					'name'          => 'Markers',
					'singular_name' => 'Marker',
				),
				'public'             => true,
				'has_archive'        => false,
				'publicly_queryable' => false,
//				'menu_position' => 5,
				'supports'           => array(
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

		add_meta_box(
			'fwe-marker-settings',
			'Settings',
			array( $this, 'markerSettingsMetaBoxHTML' ),
			'fwe-marker'
		);
	}

	public function savePostAction( $post_id ) {
		$metaKeys = array(
			'fwe-geo-json',
			'fwe-marker-icon-size-x',
			'fwe-marker-icon-size-y',
			'fwe-marker-icon-anchor-x',
			'fwe-marker-icon-anchor-y',
			'fwe-marker-popup-anchor-x',
			'fwe-marker-popup-anchor-y',
		);

		foreach ( $metaKeys as $metaKey ) {
			if ( array_key_exists( $metaKey, $_POST ) ) {
				update_post_meta(
					$post_id,
					$metaKey,
					$_POST[ $metaKey ]
				);
			}
		}
	}

	function customPostLocationMetaBoxHTML( $post ) {
		$geoJson = get_post_meta( $post->ID, 'fwe-geo-json', true );
		?><label class="screen-reader-text" for="fwe-geo-json">Geo JSON</label>
        <textarea name="fwe-geo-json" id="fwe-geo-json"
                  style="width: 100%;"><?php echo esc_attr( $geoJson ) ?></textarea>
		<?php
	}

	public function markerSettingsMetaBoxHTML( $post ) {
		$iconSizeX = get_post_meta( $post->ID, 'fwe-marker-icon-size-x', true );
		if ( empty( $iconSizeX ) ) {
			$iconSizeX = '64';
		}
		$iconSizeY = get_post_meta( $post->ID, 'fwe-marker-icon-size-y', true );
		if ( empty( $iconSizeY ) ) {
			$iconSizeY = '64';
		}
		$iconAnchorX = get_post_meta( $post->ID, 'fwe-marker-icon-anchor-x', true );
		if ( empty( $iconAnchorX ) ) {
			$iconAnchorX = '0.5';
		}
		$iconAnchorY = get_post_meta( $post->ID, 'fwe-marker-icon-anchor-y', true );
		if ( empty( $iconAnchorY ) ) {
			$iconAnchorY = '0.5';
		}
		$popupAnchorX = get_post_meta( $post->ID, 'fwe-marker-popup-anchor-x', true );
		if ( empty( $popupAnchorX ) ) {
			$popupAnchorX = '0.5';
		}
		$popupAnchorY = get_post_meta( $post->ID, 'fwe-marker-popup-anchor-y', true );
		if ( empty( $popupAnchorY ) ) {
			$popupAnchorY = '0.5';
		}

		?>
        <p><label for="fwe-marker-icon-size-x">Icon size X</label>
            <input name="fwe-marker-icon-size-x" id="fwe-marker-icon-size-x" class="slider" type="range" min="16"
                   max="128" step="1"
                   value="<?php echo esc_attr( $iconSizeX ) ?>">
            <input id="fwe-marker-icon-size-x-num" type="number" min="16" max="128" step="1"
                   value="<?php echo esc_attr( $iconSizeX ) ?>">
        </p>
        <p><label for="fwe-marker-icon-size-y">Icon size Y</label>
            <input name="fwe-marker-icon-size-y" id="fwe-marker-icon-size-y" class="slider" type="range" min="16"
                   max="128" step="1"
                   value="<?php echo esc_attr( $iconSizeY ) ?>">
            <input id="fwe-marker-icon-size-y-num" type="number" min="16" max="128" step="1"
                   value="<?php echo esc_attr( $iconSizeY ) ?>">
        </p>
        <label for="fwe-marker-square-ratio">Square icon</label> <input id="fwe-marker-square-ratio" type="checkbox"
			<?php checked( $iconSizeX, $iconSizeY, true ) ?> >
        <p><label for="fwe-marker-icon-anchor-x">Icon anchor X</label>
            <input name="fwe-marker-icon-anchor-x" id="fwe-marker-icon-anchor-x" class="slider" type="range" min="0"
                   max="1" step="0.01"
                   value="<?php echo esc_attr( $iconAnchorX ) ?>">
            <input id="fwe-marker-icon-anchor-x-num" type="number" min="0" max="1" step="0.01"
                   value="<?php echo esc_attr( $iconAnchorX ) ?>">
        </p>
        <p><label for="fwe-marker-icon-anchor-y">Icon anchor Y</label>
            <input name="fwe-marker-icon-anchor-y" id="fwe-marker-icon-anchor-y" class="slider" type="range" min="0"
                   max="1" step="0.01"
                   value="<?php echo esc_attr( $iconAnchorY ) ?>">
            <input id="fwe-marker-icon-anchor-y-num" type="number" min="0" max="1" step="0.01"
                   value="<?php echo esc_attr( $iconAnchorY ) ?>">
        </p>

        <p><label for="fwe-marker-popup-anchor-x">Popup anchor X</label>
            <input name="fwe-marker-popup-anchor-x" id="fwe-marker-popup-anchor-x" class="slider" type="range" min="0"
                   max="1" step="0.01" value="<?php echo esc_attr( $popupAnchorX ) ?>">
            <input id="fwe-marker-popup-anchor-x-num" type="number" min="0" max="1" step="0.01"
                   value="<?php echo esc_attr( $popupAnchorX ) ?>">
        </p>
        <p><label for="fwe-marker-popup-anchor-y">Popup anchor Y</label>
            <input name="fwe-marker-popup-anchor-y" id="fwe-marker-popup-anchor-y" class="slider" type="range" min="0"
                   max="1" step="0.01" value="<?php echo esc_attr( $popupAnchorY ) ?>">
            <input id="fwe-marker-popup-anchor-y-num" type="number" min="0" max="1" step="0.01"
                   value="<?php echo esc_attr( $popupAnchorY ) ?>">
        </p>
        <div id="marker-map" style="height: 200px;"></div>

        <script>
            let sliderInput = document.getElementById("fwe-marker-icon-size-x");
            let numInput = document.getElementById("fwe-marker-icon-size-x-num");

            var iconData = {
                iconUrl: "<?php echo has_post_thumbnail( $post->ID ) ? esc_url( get_the_post_thumbnail_url( $post->ID ) ) : 'null' ?>",
                iconSize: [<?php echo $iconSizeX . ', ' . $iconSizeY ?>],
                iconAnchor: [32, 32],
                popupAnchor: [32, 32],
            };

            function updateMarkerSizeX(value, noUpdate = false) {
                iconData.iconSize[0] = parseInt(value);
                iconData.iconAnchor[0] = Math.round(iconData.iconSize[0] * document.getElementById("fwe-marker-icon-anchor-x").value);
                iconData.popupAnchor[0] = Math.round(iconData.iconSize[0] * document.getElementById("fwe-marker-popup-anchor-x").value);
                document.getElementById("fwe-marker-icon-size-x").value = value;
                document.getElementById("fwe-marker-icon-size-x-num").value = value;

                if (noUpdate) return;

                if (document.getElementById("fwe-marker-square-ratio").checked) {
                    updateMarkerSizeY(value, true);
                }

                updateMarker();
            }

            function updateMarkerSizeY(value, noUpdate = false) {
                iconData.iconSize[1] = parseInt(value);
                iconData.iconAnchor[1] = Math.round(iconData.iconSize[1] * document.getElementById("fwe-marker-icon-anchor-y").value);
                iconData.popupAnchor[1] = Math.round(iconData.iconSize[1] * document.getElementById("fwe-marker-popup-anchor-y").value);
                document.getElementById("fwe-marker-icon-size-y").value = value;
                document.getElementById("fwe-marker-icon-size-y-num").value = value;

                if (noUpdate) return;

                if (document.getElementById("fwe-marker-square-ratio").checked) {
                    updateMarkerSizeX(value, true);
                }

                updateMarker();
            }

            function updateMarkerAnchorX(value) {
                iconData.iconAnchor[0] = Math.round(iconData.iconSize[0] * parseFloat(value));
                document.getElementById("fwe-marker-icon-anchor-x").value = value;
                document.getElementById("fwe-marker-icon-anchor-x-num").value = value;
                updateMarker();
            }

            function updateMarkerAnchorY(value) {
                iconData.iconAnchor[1] = Math.round(iconData.iconSize[1] * parseFloat(value));
                document.getElementById("fwe-marker-icon-anchor-y").value = value;
                document.getElementById("fwe-marker-icon-anchor-y-num").value = value;
                updateMarker();
            }

            function updatePopupAnchorX(value) {
                iconData.popupAnchor[0] = Math.round(iconData.iconSize[0] * parseFloat(value));
                document.getElementById("fwe-marker-popup-anchor-x").value = value;
                document.getElementById("fwe-marker-popup-anchor-x-num").value = value;
                updateMarker();
            }

            function updatePopupAnchorY(value) {
                iconData.popupAnchor[1] = Math.round(iconData.iconSize[1] * parseFloat(value));
                document.getElementById("fwe-marker-popup-anchor-y").value = value;
                document.getElementById("fwe-marker-popup-anchor-y-num").value = value;
                updateMarker();
            }

            let markerMap = FWE.createMap("marker-map");

            let idCallbacks = [
                {id: "fwe-marker-icon-size-x", callback: updateMarkerSizeX},
                {id: "fwe-marker-icon-size-y", callback: updateMarkerSizeY},
                {id: "fwe-marker-icon-anchor-x", callback: updateMarkerAnchorX},
                {id: "fwe-marker-icon-anchor-y", callback: updateMarkerAnchorY},
                {id: "fwe-marker-popup-anchor-x", callback: updatePopupAnchorX},
                {id: "fwe-marker-popup-anchor-y", callback: updatePopupAnchorY},
            ];

            idCallbacks.forEach(function (idCallback) {
                let sliderInput = document.getElementById(idCallback.id);
                let numInput = document.getElementById(idCallback.id + "-num");
                sliderInput.oninput = function () {
                    idCallback.callback(this.value);
                }
                numInput.onchange = function () {
                    this.value = Math.max(Math.min(this.value, this.max), this.min);
                    idCallback.callback(this.value);
                }
            });

            var currentIcon = L.icon(iconData);
            var marker = L.marker([0.0, 0.0], {icon: currentIcon}).addTo(markerMap);

            function updateMarker() {
                currentIcon = L.icon(iconData);
                marker.setIcon(currentIcon);
            }

        </script>
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
		wp_enqueue_style( 'fantastic-world-editor-admin', plugins_url( 'admin/css/fantastic-world-editor.css', __FILE__ ), array(), null );
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

	public function markerCustomColumns( $columns ) {
		$columns = array_slice( $columns, 0, 1, true )
		           + array( 'marker-icon' => 'Icon' )
		           + array_slice( $columns, 1, null, true );

		return $columns;
	}

	public function renderMarkerCustomColumns( $columnName, $postID ) {
		if ( 'marker-icon' === $columnName ) {
			if ( has_post_thumbnail( $postID ) ) {
				$url = esc_url( get_the_post_thumbnail_url( $postID ) );
				?><img src="<?php echo $url ?>" /><?php
			} else {
				?><img src=""/><?php
			}
		}
	}
}

$FWE_Instance = FantasticWorldEditor::getInstance();

<?php

/*
Plugin Name: Fantastic World Editor
*/

class FantasticWorldEditor
{
    private static $instance = false;

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new self;
        }

        return self::$instance;
    }

    public function __construct()
    {
        add_action('init', array($this, 'registerCustomPosts'));

        add_action('wp_enqueue_scripts', array($this, 'publicEnqueue'));
        add_action('admin_enqueue_scripts', array($this, 'adminEnqueue'));

        add_action('admin_init', array($this, 'initFWEOptions'));
        add_action('admin_menu', array($this, 'addAdminMenu'));

        add_action('add_meta_boxes', array($this, 'addMetaBoxesAction'));
        add_action('save_post', array($this, 'savePostAction'));

        add_action('wp_ajax_getAllLocationGeoJson', array($this, 'getAllLocationGeoJson'));

        add_filter('style_loader_tag', array($this, 'addStyleAttributes'), 10, 2);
        add_filter('script_loader_tag', array($this, 'addScriptAttributes'), 10, 3);

        add_filter('manage_fwe-marker_posts_columns', array($this, 'markerCustomColumns'));
        add_action('manage_posts_custom_column', array($this, 'renderMarkerCustomColumns'), 10, 2);
    }

    public function registerCustomPosts()
    {
        $this->customPostLocationRegister();
        $this->customPostMarkerRegister();
    }

    private function customPostLocationRegister()
    {
        register_post_type('fwe-location',
            array(
                'labels' => array(
                    'name' => 'Locations',
                    'singular_name' => 'Location',
                ),
                'public' => true,
                'has_archive' => true,
//				'menu_position' => 5,
                'taxonomies' => array('post_tag'),
                'supports' => array(
                    'title',
                    'editor',
                    'revision',
                    'excerpt'
                ),
                'rewrite' => array(
                    'slug' => 'location'
                )
            )
        );
    }

    private function customPostMarkerRegister()
    {
        register_post_type('fwe-marker',
            array(
                'labels' => array(
                    'name' => 'Markers',
                    'singular_name' => 'Marker',
                ),
                'public' => true,
                'has_archive' => false,
                'publicly_queryable' => false,
//				'menu_position' => 5,
                'supports' => array(
                    'title',
                    'thumbnail'
                ),
            )
        );
    }

    function addMetaBoxesAction()
    {
        add_meta_box(
            'fwe-geo-json',
            'Geo JSON',
            array($this, 'customPostLocationMetaBoxHTML'),
            'fwe-location'
        );

        add_meta_box(
            'fwe-location-map-settings',
            'Map settings',
            array($this, 'locationMapSettingsMetaBoxHTML'),
            'fwe-location'
        );

        add_meta_box(
            'fwe-marker-settings',
            'Settings',
            array($this, 'markerSettingsMetaBoxHTML'),
            'fwe-marker'
        );
    }

    public function savePostAction($post_id)
    {
        $metaKeys = array(
            'fwe-geo-json',
            'fwe-marker-icon-size-x',
            'fwe-marker-icon-size-y',
            'fwe-marker-icon-anchor-x',
            'fwe-marker-icon-anchor-y',
            'fwe-marker-popup-anchor-x',
            'fwe-marker-popup-anchor-y',
        );

        foreach ($metaKeys as $metaKey) {
            if (array_key_exists($metaKey, $_POST)) {
                update_post_meta(
                    $post_id,
                    $metaKey,
                    $_POST[$metaKey]
                );
            }
        }
    }

    public function locationMapSettingsMetaBoxHTML($post)
    {
        ?>
        <div id="map" style="height:400px;"></div>
        <script>
            var currentMarker;
            var locationFeature = {
                "type": "Feature",
                "geometry": {"type": "Point", "coordinates": [0.0, 0.0]},
                "properties": {}
            };
            var geoJsonInputField;

            function onMarkerDragEnd(e) {
                locationFeature.geometry.coordinates = L.GeoJSON.latLngToCoords(e.target.getLatLng());
                updateGeoJsonInputField();
            }

            function updateGeoJsonInputField() {
                geoJsonInputField.value = JSON.stringify(locationFeature);
            }

            jQuery(document).ready(function () {
                geoJsonInputField = jQuery("textarea#fwe-geo-json")[0];

                let locationGeoJson = <?php echo json_encode(get_post_meta($post->ID, 'fwe-geo-json', true)) ?>;
                try {
                    locationFeature = JSON.parse(locationGeoJson);
                } catch (e) {
                    console.error("geo JSON string retrieved isn't a valid JSON " + locationGeoJson);
                }

                let map = FWE.createMap("map");
                currentMarker = L.geoJSON(locationFeature, {
                    pointToLayer: function (feature, latLng) {
                        return L.marker(latLng, {
                            title: locationFeature.geometry.coordinates.toString(),
                            draggable: true,
                            icon: FWE.icons[locationFeature.properties.iconID]
                        }).on("dragend", onMarkerDragEnd);
                    }
                });

                currentMarker
                    .addTo(map);

                var customControl = L.Control.extend({
                    options: {
                        position: 'bottomleft'
                    },

                    onAdd: function (map) {
                        var container = L.DomUtil.create('select', 'leaflet-bar leaflet-control leaflet-control-custom');

                        /*                        container.style.backgroundColor = 'white';
												container.style.backgroundImage = "url(http://t1.gstatic.com/images?q=tbn:ANd9GcR6FCUMW5bPn8C4PbKak2BJQQsmC-K9-mbYBeFZm1ZM2w2GRy40Ew)";
												container.style.backgroundSize = "30px 30px";
												container.style.width = '30px';
												container.style.height = '30px';*/

                        container.onclick = function () {
                            console.log('buttonClicked');
                        }

                        return container;
                    }
                });

                map.addControl(new customControl());
            });
        </script>
        <?php
    }

    function customPostLocationMetaBoxHTML($post)
    {
        $geoJson = get_post_meta($post->ID, 'fwe-geo-json', true);
        ?><label class="screen-reader-text" for="fwe-geo-json">Geo JSON</label>
        <textarea name="fwe-geo-json" id="fwe-geo-json"
                  class="large-text code"><?php echo esc_attr($geoJson) ?></textarea>
        <?php
    }

    public function markerSettingsMetaBoxHTML($post)
    {
        // Icon size data initialisation
        /*$iconSizeX = get_post_meta($post->ID, 'fwe-marker-icon-size-x', true);
        if (empty($iconSizeX)) {
            $iconSizeX = '64';
        }
        $iconSizeY = get_post_meta($post->ID, 'fwe-marker-icon-size-y', true);
        if (empty($iconSizeY)) {
            $iconSizeY = '64';
        }
        $iconAnchorX = get_post_meta($post->ID, 'fwe-marker-icon-anchor-x', true);
        if (empty($iconAnchorX)) {
            $iconAnchorX = '0.5';
        }
        $iconAnchorY = get_post_meta($post->ID, 'fwe-marker-icon-anchor-y', true);
        if (empty($iconAnchorY)) {
            $iconAnchorY = '0.5';
        }*/
        /*$popupAnchorX = get_post_meta( $post->ID, 'fwe-marker-popup-anchor-x', true );
        if ( empty( $popupAnchorX ) ) {
            $popupAnchorX = '0.5';
        }
        $popupAnchorY = get_post_meta( $post->ID, 'fwe-marker-popup-anchor-y', true );
        if ( empty( $popupAnchorY ) ) {
            $popupAnchorY = '0.5';
        }*/

        ?>
        <!-- Icon size form-->
        <!--<p><label for="fwe-marker-icon-size-x">Icon size X</label>
            <input id="fwe-marker-icon-size-x" class="slider" type="range" min="16" max="128" step="1">
            <input id="fwe-marker-icon-size-x-num" type="number" min="16" max="128" step="1">
        </p>
        <p><label for="fwe-marker-icon-size-y">Icon size Y</label>
            <input id="fwe-marker-icon-size-y" class="slider" type="range" min="16" max="128" step="1">
            <input id="fwe-marker-icon-size-y-num" type="number" min="16" max="128" step="1">
        </p>
        <label for="fwe-marker-square-ratio">Square icon</label> <input id="fwe-marker-square-ratio" type="checkbox">
        <p><label for="fwe-marker-icon-anchor-x">Icon anchor X</label>
            <input id="fwe-marker-icon-anchor-x" class="slider" type="range" min="0" max="1" step="0.01">
            <input id="fwe-marker-icon-anchor-x-num" type="number" min="0" max="1" step="0.01">
        </p>
        <p><label for="fwe-marker-icon-anchor-y">Icon anchor Y</label>
            <input id="fwe-marker-icon-anchor-y" class="slider" type="range" min="0" max="1" step="0.01">
            <input id="fwe-marker-icon-anchor-y-num" type="number" min="0" max="1" step="0.01">
        </p>-->
        <p>
            <textarea id="content" class="large-text code" name="content"
                      type="text"><?php echo esc_html($post->post_content) ?></textarea>
        </p>

        <?php
        // Image selection form
        /*$image_id = '497';
        if (intval($image_id) > 0) {
            // Change with the image size you want to use
            $image = wp_get_attachment_image($image_id, 'medium', false, array('id' => 'myprefix-preview-image'));
        } else {
            // Some default image
            $image = '<img id="myprefix-preview-image" src="https://some.default.image.jpg" />';
        }

        echo $image; */
        ?><!--
        <input type="" name="myprefix_image_id" id="myprefix_image_id"
               value="<?php /*echo esc_attr($image_id); */ ?>" class="regular-text"/>
        <input type='button' class="button-primary" value="<?php /*esc_attr_e('Select a image', 'mytextdomain'); */ ?>"
               id="myprefix_media_manager"/>-->


        <!--<p><label for="fwe-marker-popup-anchor-x">Popup anchor X</label>
            <input name="fwe-marker-popup-anchor-x" id="fwe-marker-popup-anchor-x" class="slider" type="range" min="0"
                   max="1" step="0.01" value="<?php /*echo esc_attr( $popupAnchorX ) */ ?>">
            <input id="fwe-marker-popup-anchor-x-num" type="number" min="0" max="1" step="0.01"
                   value="<?php /*echo esc_attr( $popupAnchorX ) */ ?>">
        </p>
        <p><label for="fwe-marker-popup-anchor-y">Popup anchor Y</label>
            <input name="fwe-marker-popup-anchor-y" id="fwe-marker-popup-anchor-y" class="slider" type="range" min="0"
                   max="1" step="0.01" value="<?php /*echo esc_attr( $popupAnchorY ) */ ?>">
            <input id="fwe-marker-popup-anchor-y-num" type="number" min="0" max="1" step="0.01"
                   value="<?php /*echo esc_attr( $popupAnchorY ) */ ?>">
        </p>-->
        <div id="marker-map" style="height: 300px;"></div>

        <script>
            // Initialize media manager to select icon image
            /*jQuery("input#myprefix_media_manager").click(function (e) {
                e.preventDefault();
                var image_frame;
                if (image_frame) {
                    image_frame.open();
                }
                // Define image_frame as wp.media object
                image_frame = wp.media({
                    title: "Select Media",
                    multiple: false,
                    library: {
                        type: "image",
                    }
                });

                image_frame.on("select", function () {
                    // On close, get selections and save to the hidden input
                    // plus other AJAX stuff to refresh the image preview
                    var selection = image_frame.state().get("selection");
                    var gallery_ids = new Array();
                    var my_index = 0;
                    jQuery("input#myprefix_image_id").val(selection.first().id);
                    Refresh_Image(selection.first());
                });

                image_frame.on("open", function () {
                    // On open, get the id from the hidden input
                    // and select the appropiate images in the media manager
                    var selection = image_frame.state().get('selection');
                    var ids = jQuery("input#myprefix_image_id").val().split(',');
                    ids.forEach(function (id) {
                        var attachment = wp.media.attachment(id);
                        attachment.fetch();
                        selection.add(attachment ? [attachment] : []);
                    });

                });

                image_frame.open();
            });*/

            // Ajax request to refresh the image preview
            /*function Refresh_Image(attachement) {
                jQuery('#myprefix-preview-image').attr("src", attachement.attributes.sizes.full.url);
            }*/

            let markerMap = FWE.createMap("marker-map");

            var iconData = {};
            var currentIcon;
            var marker = null;

            function onContentChange(e) {
                let iconOptionsSting = e.target.value;
                try {
                    iconData = JSON.parse(iconOptionsSting);
                    /*if (iconData.className === undefined || iconData.className === null) {
                        iconData.className = "marker-debug";
                    } else {
                        iconData.className += " marker-debug";
                    }*/
                } catch (e) {
                }

                updateMarker();
            }

            jQuery("textarea#content").on("input", onContentChange).trigger("input");


            //var iconData = {
            //    iconUrl: "<?php //echo has_post_thumbnail( $post->ID ) ? esc_url( get_the_post_thumbnail_url( $post->ID ) ) : 'null' ?>//",
            //    iconSize: [<?php //echo $iconSizeX . ', ' . $iconSizeY ?>//],
            //    iconAnchor: [<?php //echo $iconAnchorX . ', ' . $iconAnchorY?>//],
            //    popupAnchor: [0, 0],
            //};

            // Form update callbacks
            /*function updateMarkerSizeX(value, noUpdate = false) {
                let oldValue = iconData.iconSize[0];
                iconData.iconSize[0] = parseInt(value);
                document.getElementById("fwe-marker-icon-size-x").value = value;
                document.getElementById("fwe-marker-icon-size-x-num").value = value;

                iconData.iconAnchor[0] = Math.round(iconData.iconAnchor[0] * iconData.iconSize[0] / oldValue);
                let slider = document.getElementById("fwe-marker-icon-anchor-x");
                slider.max = iconData.iconSize[0];
                slider.value = iconData.iconAnchor[0];
                let numInput = document.getElementById("fwe-marker-icon-anchor-x-num");
                numInput.max = iconData.iconSize[0];
                numInput.value = iconData.iconAnchor[0];
                // iconData.popupAnchor[0] = Math.round(iconData.iconSize[0] * document.getElementById("fwe-marker-popup-anchor-x").value);

                if (noUpdate) return;

                if (document.getElementById("fwe-marker-square-ratio").checked) {
                    updateMarkerSizeY(value, true);
                }

                updateMarker();
            }

            function updateMarkerSizeY(value, noUpdate = false) {
                let oldValue = iconData.iconSize[1];
                iconData.iconSize[1] = parseInt(value);
                document.getElementById("fwe-marker-icon-size-y").value = value;
                document.getElementById("fwe-marker-icon-size-y-num").value = value;

                iconData.iconAnchor[1] = Math.round(iconData.iconAnchor[1] * iconData.iconSize[1] / oldValue);
                let slider = document.getElementById("fwe-marker-icon-anchor-y");
                slider.max = iconData.iconSize[1];
                slider.value = iconData.iconAnchor[1];
                let numInput = document.getElementById("fwe-marker-icon-anchor-y-num");
                numInput.max = iconData.iconSize[1];
                numInput.value = iconData.iconAnchor[1];
                // iconData.popupAnchor[1] = Math.round(iconData.iconSize[1] * document.getElementById("fwe-marker-popup-anchor-y").value);

                if (noUpdate) return;

                if (document.getElementById("fwe-marker-square-ratio").checked) {
                    updateMarkerSizeX(value, true);
                }

                updateMarker();
            }

            function updateMarkerAnchorX(value) {
                iconData.iconAnchor[0] = parseFloat(value);
                document.getElementById("fwe-marker-icon-anchor-x").value = value;
                document.getElementById("fwe-marker-icon-anchor-x-num").value = value;
                updateMarker();
            }

            function updateMarkerAnchorY(value) {
                iconData.iconAnchor[1] = parseFloat(value);
                document.getElementById("fwe-marker-icon-anchor-y").value = value;
                document.getElementById("fwe-marker-icon-anchor-y-num").value = value;
                updateMarker();
            }*/

            /*function updatePopupAnchorX(value) {
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
            }*/



            // Apply form input callbacks
            /*let idCallbacks = [
                {id: "fwe-marker-icon-size-x", callback: updateMarkerSizeX},
                {id: "fwe-marker-icon-size-y", callback: updateMarkerSizeY},
                {id: "fwe-marker-icon-anchor-x", callback: updateMarkerAnchorX},
                {id: "fwe-marker-icon-anchor-y", callback: updateMarkerAnchorY},
                /!*{id: "fwe-marker-popup-anchor-x", callback: updatePopupAnchorX},
                {id: "fwe-marker-popup-anchor-y", callback: updatePopupAnchorY},*!/
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
            });*/


            function updateMarker() {
                if (iconData.iconUrl == null) {
                    currentIcon = L.divIcon(iconData);
                } else {
                    currentIcon = L.icon(iconData);
                }
                if (marker === null) {
                    marker = L.marker([0.0, 0.0], {icon: currentIcon, draggable: true});
                    marker.addTo(markerMap);
                } else {
                    marker.setIcon(currentIcon);
                }
            }


        </script>
        <?php
    }

    public function publicEnqueue()
    {
        wp_enqueue_style('leaflet', 'https://unpkg.com/leaflet@1.8.0/dist/leaflet.css', array(), null);
        wp_enqueue_script('leaflet', 'https://unpkg.com/leaflet@1.8.0/dist/leaflet.js', array(), null);
        wp_enqueue_script('fantastic-world-editor', plugins_url('public/js/fantastic-world-editor.js', __FILE__), array('jquery'), null);


        $markers = get_posts(array(
            'post_type' => 'fwe-marker',
            'numberposts' => -1,
        ));
        $iconsOptions = array();
        foreach ($markers as $marker) {
            $iconOption = json_decode($marker->post_content);
            if (!is_null($iconOption)) {
                $iconsOptions[$marker->ID] = $iconOption;
            }
        }

        wp_add_inline_script('fantastic-world-editor',
            'const FWE_DATA = ' . json_encode(array(
                'mapUrl' => get_option('fwe-map-url'),
                'iconsOptions' => $iconsOptions
            )), 'before');
    }

    public function adminEnqueue($page)
    {
        $this->publicEnqueue();
        wp_enqueue_style('fantastic-world-editor-admin', plugins_url('admin/css/fantastic-world-editor.css', __FILE__), array(), null);
        if ($page == 'post.php') { // #FIXME: Check if there's a more precise way to do so (I'm using this to use the image selector in the marker post creation page
            wp_enqueue_media();
        }
    }

    public function addAdminMenu()
    {
        add_menu_page('World Map', 'World Map', 'manage_options', 'fwe-world-map', array(
            $this,
            'worldMapMenuHTML'
        ));

        add_options_page(
            'Fantastic World Editor',
            'Fantastic World Editor',
            'manage_options',
            'fwe-options',
            array($this, 'fweOptionsHTML')
        );
    }

    function initFWEOptions()
    {
        register_setting('fwe-options', 'fwe-map-url');

        add_settings_section(
            'fwe-options',
            null,
            null,
            'fwe-options'
        );

        add_settings_field(
            'fwe-map-url',
            'Map URL',
            array($this, 'textWithMapFieldHTML'),
            'fwe-options',
            'fwe-options'
        );
    }

    function textWithMapFieldHTML()
    {
        $options = get_option('fwe-map-url');
        ?>
        <input type="text" id="fwe-map-url" name="fwe-map-url"
               value="<?php echo isset($options) ? esc_attr($options) : ''; ?>" class="regular-text"/>
        <input type="button" id="fwe-test-map-url" class="button" value="Test" onclick="testMapUrl()"/>
        <p>
        <div id="test-map-container" style="max-width: 500px; height: 200px; background-color: lightgray"></div></p>
        <?php
    }

    public function worldMapMenuHTML()
    {
        ?>
        <div class="wrap">
            <h1>World Map Title</h1>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content" style="position: relative;">
                        <div id="worldMap" style="height: 800px;"></div>
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
                jQuery.post(ajaxurl, data, function (response) {
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

                    function pointToLayer(feature, latlng) {
                        if (feature.properties.iconID === undefined) {
                            return L.marker(latlng);
                        } else {
                            return L.marker(latlng, {icon: FWE.icons[feature.properties.iconID]});
                        }
                    }

                    L.geoJSON(geoJsonFeatures, {onEachFeature: onEachFeature, pointToLayer: pointToLayer}).addTo(map);
                });
            });
        </script>
        <?php
    }

    public function fweOptionsHTML()
    {
        require_once "admin/partials/fwe-options.php";
    }

    public function addStyleAttributes($html, $handle)
    {
        if ('leaflet' === $handle) {
            return str_replace('media="all"', 'integrity="sha512-hoalWLoI8r4UszCkZ5kL8vayOGVae1oxXe/2A4AO6J9+580uKHDO3JdHb7NzwwzK5xr/Fs0W40kiNHxM9vyTtQ==" crossorigin=""', $html);
        }

        return $html;
    }

    public function addScriptAttributes($tag, $handle, $src)
    {
        if ('leaflet' === $handle) {
            return '<script id="' . $handle . '-js" src="' . $src . '" integrity="sha512-BB3hKbKWOc9Ez/TAwyWxNXeoV9c1v6FIeYiBieIWkpLjauysF18NzgR1MBNBXf8/KABdlkX68nAhlwcDFLGPCQ==" crossorigin=""></script>' . "\n";
        }

        return $tag;
    }

    public function getAllLocationGeoJson()
    {
        $locations = get_posts(array(
                'post_type' => 'fwe-location',
                'numberposts' => -1,
            )
        );

        // #FIXME: this foreach loop probably won't scale with an increasing number of posts. Need to find a way to cache the data
        $geoJson = array();
        foreach ($locations as $location) {
            $geoJson[] = get_post_meta($location->ID, 'fwe-geo-json', true);
        }

        wp_send_json(json_encode($geoJson));
    }

    public function markerCustomColumns($columns)
    {
        $columns = array_slice($columns, 0, 1, true)
            + array('marker-icon' => 'Icon')
            + array_slice($columns, 1, null, true);

        return $columns;
    }

    public function renderMarkerCustomColumns($columnName, $postID)
    {
        if ('marker-icon' === $columnName) {
            if (has_post_thumbnail($postID)) {
                $url = esc_url(get_the_post_thumbnail_url($postID));
                ?><img src="<?php echo $url ?>" /><?php
            } else {
                ?><img src=""/><?php
            }
        }
    }
}

$FWE_Instance = FantasticWorldEditor::getInstance();

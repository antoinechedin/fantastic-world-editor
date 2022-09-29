<?php
if ( ! current_user_can( 'manage_options' ) ) {
	return;
}

// show error/update messages
settings_errors( "wporg_messages" );
?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
    <form action="options.php" method="post">
		<?php
		settings_fields( 'fwe-options' );
		do_settings_sections( 'fwe-options' );
		submit_button( 'Save Changes' );
		?>
    </form>
</div>
<script>
    let mapUrlField = document.getElementById("fwe-map-url");
    let testMap = FWE.createTestMap("test-map-container", mapUrlField.value);

    function testMapUrl() {
        if (testMap !== null) {
            testMap.off();
            testMap.remove();
        }

        testMap = FWE.createTestMap("test-map-container", mapUrlField.value);
    }
</script>


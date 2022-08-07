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


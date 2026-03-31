<?php
/**
 * Settings page template.
 *
 * @package PH\DocumentManager
 */

defined( 'ABSPATH' ) || exit;
?>
<div class="wrap">
	<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>

	<?php settings_errors( 'ph_document_manager' ); ?>

	<form method="post" action="options.php">
		<?php
		settings_fields( 'ph_document_manager' );
		do_settings_sections( 'ph_document_manager' );
		submit_button();
		?>
	</form>
</div>

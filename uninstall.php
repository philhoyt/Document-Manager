<?php
/**
 * Plugin uninstall handler.
 *
 * Runs when the plugin is deleted from the Plugins screen.
 * Removes capabilities and options. Does NOT delete ph_document posts or media.
 *
 * @package PH\DocumentManager
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

require_once __DIR__ . '/vendor/autoload.php';

$capabilities = new PH\DocumentManager\Capabilities();
$capabilities->remove_caps();

delete_option( PH\DocumentManager\Settings::OPTION_SLUG );
delete_option( PH\DocumentManager\Settings::OPTION_CAPABILITY );
delete_option( PH\DocumentManager\Settings::OPTION_FALLBACK );

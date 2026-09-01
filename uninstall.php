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
// Registered by versions before the option was removed as unused; delete any
// value those installs left behind.
delete_option( 'ph_document_manager_capability' );
delete_option( PH\DocumentManager\Settings::OPTION_FALLBACK );

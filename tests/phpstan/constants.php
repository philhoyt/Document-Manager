<?php
/**
 * Constant definitions for static analysis.
 *
 * These are defined at runtime in ph-document-manager.php; declaring them here
 * as a PHPStan bootstrap file lets analysis resolve them without executing the
 * plugin. Not loaded by WordPress.
 *
 * @package PH\DocumentManager
 */

define( 'PH_DOCUMENT_MANAGER_VERSION', '0.0.0' );
define( 'PH_DOCUMENT_MANAGER_DIR', dirname( __DIR__, 2 ) . '/' );
define( 'PH_DOCUMENT_MANAGER_URL', 'https://example.test/' );

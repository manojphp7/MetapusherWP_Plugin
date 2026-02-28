<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class MP_Helper
 *
 * Provides shared utility methods used across the MetaPusher plugin.
 * Handles sanitization, formatting, and common data operations.
 *
 * @package    MetaPusher
 * @subpackage MetaPusher/core
 */
class MP_Helper {

    /**
     * Sanitize and validate a user-provided API key.
     *
     * @param string $key Raw key input.
     * @return string Sanitized key.
     */
    public static function sanitize_api_key( $key ) {
        return sanitize_text_field( trim( $key ) );
    }

    /**
     * Determine whether the current user has admin capabilities.
     *
     * @return bool True if user is admin, false otherwise.
     */
    public static function is_admin_user() {
        return current_user_can( 'manage_options' );
    }

    /**
     * Format bytes to human-readable size string.
     *
     * @param int $bytes     Raw byte count.
     * @param int $precision Decimal places.
     * @return string Formatted string like "2.5 MB".
     */
    public static function format_bytes( $bytes, $precision = 2 ) {
        $units = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
        $bytes = max( $bytes, 0 );
        $pow   = floor( ( $bytes ? log( $bytes ) : 0 ) / log( 1024 ) );
        $pow   = min( $pow, count( $units ) - 1 );
        return round( $bytes / ( 1024 ** $pow ), $precision ) . ' ' . $units[ $pow ];
    }

    /**
     * Get the plugin's base URL.
     *
     * @return string URL ending with slash.
     */
    public static function plugin_url() {
        return trailingslashit( plugins_url( '', dirname( __FILE__ ) ) );
    }

    /**
     * Get the plugin's base filesystem path.
     *
     * @return string Absolute path ending with slash.
     */
    public static function plugin_path() {
        return trailingslashit( dirname( dirname( __FILE__ ) ) );
    }

    /**
     * Log a debug message to the WordPress debug log.
     * Only logs when WP_DEBUG and WP_DEBUG_LOG are true.
     *
     * @param mixed $data Data to log.
     */
    public static function log( $data ) {
        if ( defined( 'WP_DEBUG' ) && WP_DEBUG && defined( 'WP_DEBUG_LOG' ) && WP_DEBUG_LOG ) {
            error_log( '[MetaPusher] ' . ( is_array( $data ) || is_object( $data ) ? print_r( $data, true ) : $data ) );
        }
    }
}

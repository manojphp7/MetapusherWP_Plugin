<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class MP_Validator
 *
 * Provides input validation utilities for MetaPusher.
 * Ensures that user-supplied data meets expected formats
 * before it is processed or stored.
 *
 * @package    MetaPusher
 * @subpackage MetaPusher/core
 */
class MP_Validator {

    /**
     * Validate that a string is non-empty after trimming.
     *
     * @param string $value The value to check.
     * @return bool
     */
    public static function is_non_empty( $value ) {
        return ( is_string( $value ) && strlen( trim( $value ) ) > 0 );
    }

    /**
     * Validate a URL.
     *
     * @param string $url The URL to validate.
     * @return bool
     */
    public static function is_valid_url( $url ) {
        return filter_var( $url, FILTER_VALIDATE_URL ) !== false;
    }

    /**
     * Validate an email address.
     *
     * @param string $email The email to validate.
     * @return bool
     */
    public static function is_valid_email( $email ) {
        return is_email( $email ) !== false;
    }

    /**
     * Check if a value is a positive integer.
     *
     * @param mixed $value The value to check.
     * @return bool
     */
    public static function is_positive_int( $value ) {
        return ( is_numeric( $value ) && intval( $value ) > 0 );
    }

    /**
     * Validate that a key contains only alphanumeric characters and dashes.
     *
     * @param string $key The key to validate.
     * @return bool
     */
    public static function is_valid_key( $key ) {
        return (bool) preg_match( '/^[a-zA-Z0-9\-_]+$/', $key );
    }
}

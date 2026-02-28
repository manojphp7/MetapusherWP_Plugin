<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class MP_Http
 *
 * Thin wrapper around WordPress HTTP API (wp_remote_get / wp_remote_post).
 * Centralizes timeout, error handling, and response parsing for all
 * outbound requests made by the MetaPusher plugin.
 *
 * @package    MetaPusher
 * @subpackage MetaPusher/libs/vendor
 */
class MP_Http {

    /** @var int Default request timeout in seconds. */
    const TIMEOUT = 10;

    /**
     * Perform a GET request.
     *
     * @param string $url  Target URL.
     * @param array  $args Optional wp_remote_get args to merge.
     * @return array|WP_Error Parsed response body string, or WP_Error on failure.
     */
    public static function get( $url, $args = [] ) {
        $defaults = [ 'timeout' => self::TIMEOUT ];
        $response = wp_remote_get( $url, array_merge( $defaults, $args ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return wp_remote_retrieve_body( $response );
    }

    /**
     * Perform a POST request.
     *
     * @param string $url  Target URL.
     * @param array  $body Key-value body parameters.
     * @param array  $args Optional wp_remote_post args to merge.
     * @return array|WP_Error Response body string, or WP_Error on failure.
     */
    public static function post( $url, $body = [], $args = [] ) {
        $defaults = [
            'timeout' => self::TIMEOUT,
            'body'    => $body,
        ];
        $response = wp_remote_post( $url, array_merge( $defaults, $args ) );

        if ( is_wp_error( $response ) ) {
            return $response;
        }

        return wp_remote_retrieve_body( $response );
    }

    /**
     * Decode a JSON response body.
     *
     * @param string $body Raw response body.
     * @return object|null Decoded object, or null on failure.
     */
    public static function parse_json( $body ) {
        if ( empty( $body ) ) return null;
        $decoded = json_decode( $body );
        return ( json_last_error() === JSON_ERROR_NONE ) ? $decoded : null;
    }
}

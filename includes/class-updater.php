<?php
/**
 * MP_Updater — checks info.json once per day and hooks into
 * WordPress's built-in plugin update system.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class MP_Updater {

    private $plugin_file;
    private $plugin_slug;
    private $current_version;
    private $update_url;

    public function __construct( $plugin_file, $current_version, $update_url ) {
        $this->plugin_file     = $plugin_file;
        $this->current_version = $current_version;
        $this->update_url      = $update_url;
        $this->plugin_slug     = plugin_basename( $plugin_file ); // e.g. meta-pusher/meta-pusher.php

        // Hook into WP update system
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );
        add_filter( 'plugins_api',                           [ $this, 'plugin_info' ], 20, 3 );
        add_action( 'upgrader_process_complete',             [ $this, 'purge_cache' ], 10, 2 );
    }

    /**
     * Fetch remote info.json (cached 12 hours)
     */
    private function get_remote_info() {
        $cache_key = 'mp_updater_' . md5( $this->update_url );
        $data      = get_transient( $cache_key );

        if ( false === $data ) {
            $response = wp_remote_get( $this->update_url, [
                'timeout'    => 10,
                'user-agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
            ]);

            if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
                return false;
            }

            $body = wp_remote_retrieve_body( $response );
            $data = json_decode( $body );

            if ( empty( $data ) || ! isset( $data->version ) ) {
                return false;
            }

            set_transient( $cache_key, $data, 12 * HOUR_IN_SECONDS );
        }

        return $data;
    }

    /**
     * Inject update into WP transient if newer version found
     */
    public function check_for_update( $transient ) {

        if ( empty( $transient->checked ) ) return $transient;

        $info = $this->get_remote_info();
        if ( ! $info ) return $transient;

        if ( version_compare( $this->current_version, $info->version, '<' ) ) {

            $obj                = new stdClass();
            $obj->slug          = dirname( $this->plugin_slug );   // folder name
            $obj->plugin        = $this->plugin_slug;              // folder/file.php
            $obj->new_version   = $info->version;
            $obj->tested        = $info->tested      ?? '';
            $obj->requires      = $info->requires    ?? '';
            $obj->url           = $info->homepage    ?? '';
            $obj->package       = $info->download_url;             // .zip URL
            $obj->icons         = [];
            $obj->banners       = [];
            $obj->banners_rtl   = [];
            $obj->upgrade_notice = '';

            $transient->response[ $this->plugin_slug ] = $obj;
        }

        return $transient;
    }

    /**
     * Show plugin details popup (View version x.x.x details)
     */
    public function plugin_info( $result, $action, $args ) {

        if ( $action !== 'plugin_information' ) return $result;
        if ( ! isset( $args->slug ) ) return $result;
        if ( $args->slug !== dirname( $this->plugin_slug ) ) return $result;

        $info = $this->get_remote_info();
        if ( ! $info ) return $result;

        $obj                = new stdClass();
        $obj->name          = $info->name;
        $obj->slug          = $args->slug;
        $obj->version       = $info->version;
        $obj->tested        = $info->tested      ?? '';
        $obj->requires      = $info->requires    ?? '';
        $obj->author        = '<a href="' . esc_url( $info->homepage ?? '' ) . '">Meta Pusher</a>';
        $obj->homepage      = $info->homepage    ?? '';
        $obj->download_link = $info->download_url;
        $obj->short_description = $info->description ?? '';
        $obj->sections      = [
            'description' => $info->description ?? '',
            'changelog'   => $info->changelog   ?? '',
        ];

        return $obj;
    }

    /**
     * Clear cached info after update completes
     */
    public function purge_cache( $upgrader, $options ) {
        if (
            $options['action'] === 'update' &&
            $options['type']   === 'plugin'  &&
            isset( $options['plugins'] )
        ) {
            foreach ( $options['plugins'] as $plugin ) {
                if ( $plugin === $this->plugin_slug ) {
                    delete_transient( 'mp_updater_' . md5( $this->update_url ) );
                }
            }
        }
    }
}
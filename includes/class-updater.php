<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class MP_Updater
 *
 * Handles automatic plugin update notifications inside WordPress Admin.
 * Checks for new versions once per calendar day (date-based cache key).
 * When a newer version is found on the remote server, WordPress admin
 * shows the standard update notice under Plugins → Installed Plugins.
 *
 * ── HOW TO USE ────────────────────────────────────────────────────────────
 * 1. Host a `info.json` file on your server (see format below).
 * 2. Set META_PUSHER_UPDATE_URL constant to point to that file.
 * 3. When releasing a new version:
 *      a. Update `version` in info.json
 *      b. Upload new plugin zip and update `download_url` in info.json
 *      c. Users will see the notice next time the date changes.
 *
 * ── info.json FORMAT ──────────────────────────────────────────────────────
 * {
 *   "name"         : "Meta Pusher",
 *   "slug"         : "MetaPusher",
 *   "version"      : "1.2.0",
 *   "requires"     : "5.0",
 *   "tested"       : "6.5",
 *   "homepage"     : "https://metapusher.com",
 *   "download_url" : "https://yourserver.com/downloads/MetaPusher-1.2.0.zip",
 *   "description"  : "Free Meta Pusher Notification System.",
 *   "changelog"    : "<h4>1.2.0</h4><ul><li>Bug fixes</li></ul>"
 * }
 * ─────────────────────────────────────────────────────────────────────────
 *
 * @package    MetaPusher
 * @subpackage MetaPusher/includes
 */
class MP_Updater {

    /** @var string Plugin slug (folder name). */
    private $plugin_slug;

    /** @var string Plugin file path relative to plugins dir e.g. MetaPusher/meta-pusher.php */
    private $plugin_file;

    /** @var string Currently installed version. */
    private $current_version;

    /** @var string URL to the remote info.json file. */
    private $update_url;

    /**
     * MP_Updater constructor.
     *
     * @param string $plugin_file     Plugin's main file path (use __FILE__ from main plugin file).
     * @param string $current_version Currently installed version string.
     * @param string $update_url      URL of the remote info.json file.
     */
    public function __construct( $plugin_file, $current_version, $update_url ) {
        $this->plugin_slug    = basename( dirname( $plugin_file ) );
        $this->plugin_file    = plugin_basename( $plugin_file );
        $this->current_version = $current_version;
        $this->update_url     = $update_url;

        // Inject into WordPress update transient
        add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'check_for_update' ] );

        // Populate plugin details popup
        add_filter( 'plugins_api', [ $this, 'plugin_info' ], 10, 3 );

        // Clean up old date-keyed transients on deactivation
        register_deactivation_hook( $plugin_file, [ $this, 'clear_update_cache' ] );
    }

    /**
     * Inject update data into WordPress plugin update transient.
     * Called by WordPress automatically when it checks for plugin updates.
     *
     * @param object $transient The update_plugins transient object.
     * @return object Modified transient.
     */
    public function check_for_update( $transient ) {
        if ( empty( $transient->checked ) ) {
            return $transient;
        }

        $remote = $this->get_remote_info();

        if ( $remote && version_compare( $this->current_version, $remote->version, '<' ) ) {
            $transient->response[ $this->plugin_file ] = (object) [
                'slug'        => $this->plugin_slug,
                'plugin'      => $this->plugin_file,
                'new_version' => $remote->version,
                'url'         => $remote->homepage,
                'package'     => $remote->download_url,
            ];
        }

        return $transient;
    }

    /**
     * Populate the plugin details popup ("View version x.x.x details").
     *
     * @param false|object|array $res    Default false.
     * @param string             $action API action string.
     * @param object             $args   API arguments.
     * @return false|object Plugin info object or original $res.
     */
    public function plugin_info( $res, $action, $args ) {
        if ( $action !== 'plugin_information' ) {
            return $res;
        }

        if ( $args->slug !== $this->plugin_slug ) {
            return $res;
        }

        $remote = $this->get_remote_info();

        if ( ! $remote ) {
            return $res;
        }

        return (object) [
            'name'          => $remote->name,
            'slug'          => $this->plugin_slug,
            'version'       => $remote->version,
            'requires'      => $remote->requires,
            'tested'        => $remote->tested,
            'author'        => '<a href="' . esc_url( $remote->homepage ) . '">Meta Pusher</a>',
            'homepage'      => $remote->homepage,
            'download_link' => $remote->download_url,
            'sections'      => [
                'description' => isset( $remote->description ) ? $remote->description : '',
                'changelog'   => isset( $remote->changelog )   ? $remote->changelog   : '',
            ],
        ];
    }

    /**
     * Fetch and cache remote plugin info.
     *
     * Cache key includes today's date — so the remote server is hit only
     * ONCE per calendar day per site, regardless of how many admin pages
     * are loaded or how many update checks WordPress triggers.
     *
     * At midnight the date changes → new cache key → fresh request goes out.
     * The previous day's transient is deleted to keep the DB clean.
     *
     * @return object|false Decoded JSON object, or false on failure.
     */
    private function get_remote_info() {
        $today     = date( 'Y-m-d' );
        $cache_key = 'mp_update_' . $today;

        // Serve from cache if today's check already happened
        $cached = get_transient( $cache_key );
        if ( false !== $cached ) {
            return $cached;
        }

        // Delete yesterday's transient to keep DB tidy
        $yesterday_key = 'mp_update_' . date( 'Y-m-d', strtotime( '-1 day' ) );
        delete_transient( $yesterday_key );

        // Hit the remote server
        $response = wp_remote_get( $this->update_url, [ 'timeout' => 10 ] );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body );

        if ( ! $data || json_last_error() !== JSON_ERROR_NONE ) {
            return false;
        }

        // Cache until midnight (exact seconds remaining today)
        $seconds_until_midnight = strtotime( 'tomorrow midnight' ) - time();
        set_transient( $cache_key, $data, $seconds_until_midnight );

        return $data;
    }

    /**
     * Remove today's update cache transient.
     * Called on plugin deactivation.
     */
    public function clear_update_cache() {
        delete_transient( 'mp_update_' . date( 'Y-m-d' ) );
    }
}

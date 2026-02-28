<?php

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Class MP_i18n
 *
 * Manages internationalization (i18n) and localization for the plugin.
 * Loads the plugin text domain to enable translations.
 *
 * @package    MetaPusher
 * @subpackage MetaPusher/includes
 */
class MP_i18n {

    /**
     * @var string $domain The plugin text domain.
     */
    private $domain;

    /**
     * Load the plugin text domain for translations.
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            $this->domain,
            false,
            dirname( plugin_basename( __FILE__ ) ) . '/languages/'
        );
    }

    /**
     * Set the text domain.
     *
     * @param string $domain Text domain identifier.
     */
    public function set_domain( $domain ) {
        $this->domain = $domain;
    }
}

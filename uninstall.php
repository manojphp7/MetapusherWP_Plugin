<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;

/**
 * -------------------------------------------------
 * 1. sw.js delete karo root se
 * -------------------------------------------------
 */
if (file_exists(ABSPATH . 'sw.js')) {
    unlink(ABSPATH . 'sw.js');
}

/**
 * -------------------------------------------------
 * 2. Plugin ki self directory delete karo
 * -------------------------------------------------
 */
$plugin_dir = WP_PLUGIN_DIR . '/MetaPusher';

if (is_dir($plugin_dir)) {
    // Recursive directory delete function
    function meta_pusher_delete_dir($dir) {
        if (!is_dir($dir)) return;
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? meta_pusher_delete_dir($path) : unlink($path);
        }
        rmdir($dir);
    }
    meta_pusher_delete_dir($plugin_dir);
}

/**
 * -------------------------------------------------
 * 3. Database se sab options delete karo
 * -------------------------------------------------
 */
delete_option('meta_pusher_user_key');
delete_option('meta_pusher_auto_notify');

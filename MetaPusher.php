<?php
/*
Plugin Name: Meta Pusher
Description: Free Meta Pusher Notification System
Version: 1.0.0
Author: Meta Pusher
*/

if (!defined('ABSPATH')) exit;

/**
 * -------------------------------------------------
 * PLUGIN CONSTANTS
 * -------------------------------------------------
 */
define( 'META_PUSHER_VERSION',    '1.3.0' );
define( 'META_PUSHER_UPDATE_URL', 'https://metapusher.com/cdn/plugin/info.json' );

/**
 * -------------------------------------------------
 * LOAD UPDATER — version check (once per day)
 * -------------------------------------------------
 */
require_once plugin_dir_path( __FILE__ ) . 'includes/class-updater.php';
new MP_Updater( __FILE__, META_PUSHER_VERSION, META_PUSHER_UPDATE_URL );

/**
 * -------------------------------------------------
 * ENQUEUE SCRIPTS
 * -------------------------------------------------
 */
function meta_pusher_enqueue_scripts() {

    $user_key = get_option('meta_pusher_user_key', '');
    if (empty($user_key)) return;

    wp_enqueue_script(
        'meta-pusher-cdn',
        'https://metapusher.com/cdn/pushnotification-min.js',
        [],
        '1.0',
        true
    );

    $config = [
        'siteUrl'  => site_url('/'),
        'siteName' => 'Meta Pusher',
        'swUrl'    => site_url('/sw.js'),
        'userKey'  => $user_key,
    ];

    wp_add_inline_script(
        'meta-pusher-cdn',
        'window.MetaPusher = ' . wp_json_encode($config) . ';',
        'before'
    );
}
add_action('wp_enqueue_scripts', 'meta_pusher_enqueue_scripts');


/**
 * -------------------------------------------------
 * AUTO NOTIFY ON POST PUBLISH / UPDATE
 * -------------------------------------------------
 */
add_action('transition_post_status', function ($new_status, $old_status, $post) {

    if ($post->post_type !== 'post') return;
    if ($new_status !== 'publish') return;
	if ($old_status === 'publish') return;
    if (get_option('meta_pusher_auto_notify', '0') !== '1') return;

    $user_key = get_option('meta_pusher_user_key', '');
    if (empty($user_key)) return;

    wp_remote_post('https://metapusher.com/api/auto-send-notification', [
        'body' => [
            'user_key' => $user_key,
            'title'    => get_the_title($post),
            'body'     => get_the_excerpt($post),
            'url'      => get_permalink($post),
            'icon'     => get_site_icon_url(192),
			'image' => has_post_thumbnail($post) ? get_the_post_thumbnail_url($post, 'full') : '',
        ],
        'timeout' => 10,
    ]);

}, 10, 3);


/**
 * -------------------------------------------------
 * COPY SERVICE WORKER ON PLUGIN ACTIVATE
 * -------------------------------------------------
 */
register_activation_hook(__FILE__, function () {

    $sw_source = plugin_dir_path(__FILE__) . 'sw.js';
    $sw_dest   = ABSPATH . 'sw.js';

    if (!file_exists($sw_dest) && file_exists($sw_source)) {
        copy($sw_source, $sw_dest);
    }
});


/**
 * -------------------------------------------------
 * ADMIN MENU — Custom Logo
 * -------------------------------------------------
 */
add_action('admin_menu', function () {


    add_menu_page(
        'Meta Pusher',
        'Meta Pusher',
        'manage_options',
        'meta-pusher-settings',
        'meta_pusher_render_settings',
        plugins_url('logo.png', __FILE__),
        65
    );

    // Custom class 'mp-menu-icon' add karo sirf is menu item pe
    global $menu;
    foreach ($menu as $key => $item) {
        if (isset($item[2]) && $item[2] === 'meta-pusher-settings') {
            $menu[$key][4] = isset($menu[$key][4]) ? $menu[$key][4] . ' mp-menu-icon' : 'mp-menu-icon';
            break;
        }
    }
});



/**
 * -------------------------------------------------
 * MENU ICON CSS — runs on all admin pages
 * -------------------------------------------------
 */
add_action('admin_head', function () {
    echo '<style>
        /* Meta Pusher menu icon */
        #adminmenu .mp-menu-icon .wp-menu-image img {
            width: 25px !important;
            filter: brightness(0) invert(1);
            padding-top: 4px;
        }
    </style>';
});

/**
 * -------------------------------------------------
 * ADMIN STYLES
 * -------------------------------------------------
 */
add_action('admin_head', function () {

    $screen = get_current_screen();
    if (!$screen || strpos($screen->id, 'meta-pusher') === false) return;
    ?>
    <style>
        /* ---- Layout ---- */
        .mp-wrap {
            max-width: 1100px;
            margin-top: 20px;
        }
        .mp-col-layout {
            display: flex;
            gap: 22px;
            align-items: flex-start;
        }
        .mp-col-main {
            flex: 1;
            min-width: 0;
        }
        .mp-col-side {
            width: 300px;
            flex-shrink: 0;
        }

        /* ---- Card ---- */
        .mp-section {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            padding: 24px 26px;
            margin-bottom: 22px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .mp-section h2 {
            font-size: 15px;
            font-weight: 700;
            color: #1d2327;
            margin: 0 0 16px 0;
            padding-bottom: 12px;
            border-bottom: 2px solid #f0f0f0;
        }

        /* ---- Steps ---- */
        .mp-steps {
            list-style: none;
            margin: 0; padding: 0;
        }
        .mp-steps li {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            margin-bottom: 14px;
            padding-bottom: 14px;
            border-bottom: 1px solid #f5f5f5;
        }
        .mp-steps li:last-child {
            margin-bottom: 0; padding-bottom: 0; border-bottom: none;
        }
        .mp-step-num {
            background: #f26f0f;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            min-width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            margin-top: 1px;
        }
        .mp-step-content strong {
            display: block;
            font-size: 13px;
            color: #1d2327;
            margin-bottom: 3px;
        }
        .mp-step-content p {
            margin: 0;
            font-size: 13px;
            color: #646970;
            line-height: 1.6;
        }
        .mp-step-content a {
            color: #f26f0f;
            text-decoration: none;
            font-weight: 600;
        }

        /* ---- Fields ---- */
        .mp-field-row { margin-bottom: 18px; }
        .mp-field-row label.mp-label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: #1d2327;
            margin-bottom: 6px;
        }
        .mp-field-row input[type="text"] {
            width: 100%;
            padding: 9px 12px;
            font-size: 14px;
            border: 1px solid #c3c4c7;
            border-radius: 6px;
            box-sizing: border-box;
            transition: border-color 0.2s;
        }
        .mp-field-row input[type="text"]:focus {
            border-color: #f26f0f;
            box-shadow: 0 0 0 1px #f26f0f;
            outline: none;
        }
        .mp-field-desc {
            font-size: 12px;
            color: #8c8f94;
            margin: 5px 0 0;
        }
        .mp-field-desc a { color: #f26f0f; }

        /* ---- Checkbox ---- */
        .mp-checkbox-row {
            display: flex;
            align-items: center;
            gap: 10px;
            background: #fff8f2;
            border: 1px solid #fde0c0;
            border-radius: 8px;
            padding: 13px 14px;
        }
        .mp-checkbox-row input[type="checkbox"] {
            width: 16px; height: 16px;
            margin: 0; cursor: pointer;
            accent-color: #f26f0f;
            flex-shrink: 0;
        }
        .mp-checkbox-row label {
            margin: 0;
            font-size: 14px;
            color: #1d2327;
            cursor: pointer;
        }

        /* ---- Save button ---- */
        .mp-save-wrap { margin-top: 16px; }
        .mp-save-wrap .button-primary {
            background: #f26f0f !important;
            border-color: #d45e00 !important;
            box-shadow: none !important;
            padding: 8px 22px;
            font-size: 14px;
            height: auto;
            border-radius: 6px;
        }
        .mp-save-wrap .button-primary:hover {
            background: #d45e00 !important;
        }

        /* ---- Notice ---- */
        .mp-notice {
            background: #edfaef;
            border-left: 4px solid #00a32a;
            border-radius: 0 6px 6px 0;
            padding: 11px 16px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #1d2327;
        }

        /* ---- Support Card ---- */
        .mp-support-card {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
        }
        .mp-support-header {
            background: linear-gradient(135deg, #f26f0f, #ff9a3c);
            padding: 20px 22px;
            text-align: center;
        }
        .mp-support-header img {
            width: 52px;
            height: 52px;
            object-fit: contain;
            margin-bottom: 10px;
            filter: brightness(0) invert(1);
        }
        .mp-support-header h3 {
            color: #fff;
            margin: 0 0 4px;
            font-size: 16px;
            font-weight: 700;
        }
        .mp-support-header p {
            color: rgba(255,255,255,0.88);
            margin: 0;
            font-size: 12px;
            line-height: 1.5;
        }
        .mp-support-body {
            padding: 18px 20px;
        }
        .mp-support-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 11px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .mp-support-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }
        .mp-support-icon {
            width: 34px;
            height: 34px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 17px;
            flex-shrink: 0;
        }
        .mp-icon-wa  { background: #e7fce8; }
        .mp-icon-ph  { background: #fff0e0; }
        .mp-icon-hr  { background: #f0f0ff; }
        .mp-support-info span {
            display: block;
            font-size: 11px;
            color: #8c8f94;
            margin-bottom: 1px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .mp-support-info a {
            font-size: 13px;
            font-weight: 600;
            color: #1d2327;
            text-decoration: none;
        }
        .mp-support-info a:hover { color: #f26f0f; }
        .mp-support-info strong {
            font-size: 13px;
            color: #1d2327;
        }
        .any-help-link {
           float:right;
		   margin-bottom:16px;
        }
		@media screen and (max-width: 1024px) {
			.mp-col-layout { flex-direction: column; }
			.mp-col-side   { width: 100%; }
			.mp-col-main   { width: 100%; }
		}


		@media screen and (max-width: 782px) {
			.mp-wrap               { margin-top: 10px; }
			.mp-section            { padding: 16px 14px; border-radius: 8px; margin-bottom: 16px; }
			.mp-section h2         { font-size: 14px; margin-bottom: 12px; padding-bottom: 10px; }
			.mp-steps li           { gap: 10px; }
			.mp-step-content strong{ font-size: 12px; }
			.mp-step-content p     { font-size: 12px; }
			.mp-field-row input[type="text"] { font-size: 16px; } /* iOS zoom prevent */
			.mp-checkbox-row       { padding: 11px 12px; gap: 8px; }
			.mp-checkbox-row label { font-size: 13px; }
			.mp-support-header     { padding: 16px 14px; }
			.mp-support-header img { width: 40px; height: 40px; }
			.mp-support-header h3  { font-size: 15px; }
			.mp-support-body       { padding: 14px; }
			.mp-col-layout         { gap: 16px; }
		}

		@media screen and (max-width: 480px) {
			.mp-wrap h1                      { font-size: 18px !important; }
			.mp-wrap > p                     { font-size: 12px !important; }
			.mp-save-wrap .button-primary    { width: 100%; text-align: center; padding: 10px 22px; }
		}
		
    </style>
    <?php
});


/**
 * -------------------------------------------------
 * SETTINGS PAGE RENDER
 * -------------------------------------------------
 */
function meta_pusher_render_settings() {

    // Save
    if (
        isset($_POST['meta_pusher_nonce']) &&
        wp_verify_nonce($_POST['meta_pusher_nonce'], 'meta_pusher_save_key')
    ) {
        if (isset($_POST['user_key'])) {
            update_option('meta_pusher_user_key', sanitize_text_field($_POST['user_key']));
        }
        update_option('meta_pusher_auto_notify', isset($_POST['auto_notify']) ? '1' : '0');
        echo '<div class="mp-notice">&#10003; &nbsp;Settings saved successfully!</div>';
    }

    $user_key    = get_option('meta_pusher_user_key', '');
    $auto_notify = get_option('meta_pusher_auto_notify', '0');
    $logo_url    = plugins_url('logo.png', __FILE__);
    $wa_link     = 'https://wa.me/919306139922';
    ?>

    <div class="wrap mp-wrap">

        <h1 style="font-size:21px; margin-bottom:4px;">
            MetaPusher 📣
        </h1>
        <p style="color:#646970; margin:0 0 20px; font-size:13px;">
            Configure your web push notification settings below.
        </p>

        <form method="post">
            <?php wp_nonce_field('meta_pusher_save_key', 'meta_pusher_nonce'); ?>

            <div class="mp-col-layout">

                <!-- ======================== -->
                <!-- LEFT COLUMN              -->
                <!-- ======================== -->
                <div class="mp-col-main">
					<!-- SECTION 2: SETTINGS -->
                    <div class="mp-section">
                        <h2>⚙️ &nbsp;Configuration </h2>

                        <div class="mp-field-row">
                            <label class="mp-label" for="mp_user_key">User Key</label>
                            <input
                                type="text"
                                id="mp_user_key"
                                name="user_key"
                                value="<?php echo esc_attr($user_key); ?>"
                                placeholder="Paste your Meta Pusher User Key here"
                            >
                            <p class="mp-field-desc">
                                Get your User Key from the <a href="https://metapusher.com" target="_blank">Meta Pusher dashboard</a> → Domains → Integrate.
                            </p>
                        </div>

                        <div class="mp-field-row">
                            <label class="mp-label">Notifications</label>
                            <div class="mp-checkbox-row">
                                <input
                                    type="checkbox"
                                    id="mp_auto_notify"
                                    name="auto_notify"
                                    value="1"
                                    <?php checked($auto_notify, '1'); ?>
                                >
                                <label for="mp_auto_notify">
                                    Automatically notify when an article is published or updated.
                                </label>
                            </div>
                        </div>

                        <div class="mp-save-wrap">
                            <?php submit_button('Save Settings', 'primary', 'submit', false); ?>
                        </div>
                    </div>
                    <!-- SECTION 2: HOW TO SETUP -->
                    <div class="mp-section">
                        <h2>📖 &nbsp;How to Set Up Meta Pusher Notifications</h2>
                        <ol class="mp-steps">
                            <li>
                                <div class="mp-step-num">1</div>
                                <div class="mp-step-content">
                                    <strong>Create an account at Meta Pusher Platform 🌐</strong>
                                    <p>If you are new, <a href="https://metapusher.com" target="_blank">register for an account</a>. If you already have an account, simply log in with your credentials.</p>
                                </div>
                            </li>
                            <li>
                                <div class="mp-step-num">2</div>
                                <div class="mp-step-content">
                                    <strong>Add your website to the dashboard by entering your site domain.</strong>
                                    <p>In the dashboard, go to the <em>"Sites"</em> section and add your website. This will generate a unique <strong>User Key</strong> for your site.</p>
                                </div>
                            </li>
                            <li>
                                <div class="mp-step-num">3</div>
                                <div class="mp-step-content">
                                    <strong>Copy the User Key from your dashboard.</strong>
                                    <p>Find your User Key in the <em>"Settings"</em> section of your site in the Meta Pusher dashboard. Copy it for use in your WordPress site.</p>
                                </div>
                            </li>
                            <li>
                                <div class="mp-step-num">4</div>
                                <div class="mp-step-content">
                                    <strong>Paste your User Key in the Settings section below.</strong>
                                    <p>Click <strong>"Save Settings"</strong> to complete the setup. You are now ready to start sending push notifications to your users! 🎉</p>
                                </div>
                            </li>
                        </ol>
                    </div>

                    

                </div><!-- /mp-col-main -->

                <!-- ======================== -->
                <!-- RIGHT COLUMN: SUPPORT    -->
                <!-- ======================== -->
                <div class="mp-col-side">
                    <div class="mp-support-card">

                        <div class="mp-support-header">
                            <img src="<?php echo esc_url($logo_url); ?>" alt="Meta Pusher Logo">
                            <h3>💬 Need Help?</h3>
                            <p>Have questions? Our support team is here to help! Contact us using any of the methods below.</p>
                        </div>

                        <div class="mp-support-body">

                            <div class="mp-support-item">
                                <div class="mp-support-icon mp-icon-wa">💬</div>
                                <div class="mp-support-info">
                                    <span>WhatsApp</span>
                                    <a href="<?php echo esc_url($wa_link); ?>" target="_blank">Send Message</a>
                                </div>
                            </div>

                            <div class="mp-support-item">
                                <div class="mp-support-icon mp-icon-ph">✉️</div>
                                <div class="mp-support-info">
                                    <span>Email</span>
                                    <a href="mailto:support@metapusher.com">support@metapusher.com</a>
                                </div>
                            </div>

                            <div class="mp-support-item">
                                <div class="mp-support-icon mp-icon-hr">🕐</div>
                                <div class="mp-support-info">
                                    <span>Support Hours</span>
                                    <strong>Mon – Sat &nbsp;|&nbsp; 9am – 6pm</strong>
                                </div>
                            </div>

                            <a href="https://metapusher.com" target="_blank" class="any-help-link">
                                Any Help?
                            </a>

                        </div>
                    </div>
                </div><!-- /mp-col-side -->

            </div><!-- /mp-col-layout -->
        </form>
    </div>
    <?php
}

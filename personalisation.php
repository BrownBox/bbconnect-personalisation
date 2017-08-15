<?php
/**
 * Plugin Name: Connexions Personalisation
 * Plugin URI: n/a
 * Description: Generates a unique key for each of your users that can be pushed to other web systems or used as a placeholder to enable personalised digital correspondence and customisation
 * Version: 0.1.3
 * Author: Brown Box
 * Author URI: http://brownbox.net.au
 * License: Proprietary Brown Box
 */
define('BBCONNECT_PERSONALISATION_VERSION', '0.1.3');
define('BBCONNECT_PERSONALISATION_DIR', plugin_dir_path(__FILE__));
define('BBCONNECT_PERSONALISATION_URL', plugin_dir_url(__FILE__));

require_once (BBCONNECT_PERSONALISATION_DIR.'db.php');
require_once (BBCONNECT_PERSONALISATION_DIR.'fx.php');
require_once (BBCONNECT_PERSONALISATION_DIR.'gf.php');

function bbconnect_personalisation_init() {
    if (!defined('BBCONNECT_VER')) {
        add_action('admin_init', 'bbconnect_personalisation_deactivate');
        add_action('admin_notices', 'bbconnect_personalisation_deactivate_notice');
        return;
    }
    if (is_admin()) {
        // DB updates
        bbconnect_personalisation_updates();
        // Plugin updates
        new BbConnectUpdates(__FILE__, 'BrownBox', 'bbconnect-personalisation');
    }
}
add_action('plugins_loaded', 'bbconnect_personalisation_init');

function bbconnect_personalisation_deactivate() {
    deactivate_plugins(plugin_basename(__FILE__));
}

function bbconnect_personalisation_deactivate_notice() {
    echo '<div class="updated"><p><strong>Connexions Personalisation</strong> has been <strong>deactivated</strong> as it requires Connexions.</p></div>';
    if (isset($_GET['activate'])) {
        unset($_GET['activate']);
    }
}

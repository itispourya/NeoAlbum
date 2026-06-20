<?php
/**
 * Plugin Name: NeoAlbum
 * Description: A 3D book-style album slider with page flip effects, fullscreen mode, zoom, and screenshot prevention for WordPress
 * Version: 1.0
 * Author: NeoNotion
 * Author URI: https://neonotion.net
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: NeoAlbum
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('NEOALBUM_VERSION', '1.1.0');
define('NEOALBUM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NEOALBUM_PLUGIN_URL', plugin_dir_url(__FILE__));

require_once NEOALBUM_PLUGIN_DIR . 'includes/class-neoalbum.php';
require_once NEOALBUM_PLUGIN_DIR . 'includes/class-neoalbum-admin.php';
require_once NEOALBUM_PLUGIN_DIR . 'includes/class-neoalbum-shortcode.php';

register_activation_hook(__FILE__, array('NeoAlbum', 'activate'));
register_deactivation_hook(__FILE__, array('NeoAlbum', 'deactivate'));

function neoalbum_init() {
    NeoAlbum::get_instance();
}
add_action('plugins_loaded', 'neoalbum_init');

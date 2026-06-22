<?php
if (!defined('ABSPATH')) {
    exit;
}

class NeoAlbum {
    private static $instance = null;

    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        $this->init();
    }

    private function init() {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_scripts'));
    }

    public static function activate() {
        flush_rewrite_rules();
    }

    public static function deactivate() {
        flush_rewrite_rules();
    }

    public function enqueue_frontend_scripts() {
        wp_enqueue_style('neoalbum-style', NEOALBUM_PLUGIN_URL . 'assets/css/neoalbum.css', array(), NEOALBUM_VERSION);
        wp_enqueue_script('neoalbum-script', NEOALBUM_PLUGIN_URL . 'assets/js/neoalbum.js', array('jquery'), NEOALBUM_VERSION, true);
        wp_localize_script('neoalbum-script', 'neoalbum_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('neoalbum_nonce')
        ));
    }
}

<?php
if (!defined('ABSPATH')) {
    exit;
}

class NeoAlbum_Shortcode {
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
        add_shortcode('neoalbum', array($this, 'render_shortcode'));
        add_action('wp_ajax_neoalbum_verify_password', array($this, 'verify_password'));
        add_action('wp_ajax_nopriv_neoalbum_verify_password', array($this, 'verify_password'));
    }

    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'id' => ''
        ), $atts, 'neoalbum');

        if (empty($atts['id'])) {
            return '<p>' . esc_html__('شناسه آلبوم نامعتبر است', 'neoalbum') . '</p>';
        }

        $albums = get_option('neoalbum_albums', array());
        if (!isset($albums[$atts['id']])) {
            return '<p>' . esc_html__('آلبوم پیدا نشد', 'neoalbum') . '</p>';
        }

        $album = $albums[$atts['id']];
        $settings = get_option('neoalbum_settings', array());
        $width = isset($settings['width']) ? $settings['width'] : '800px';
        $height = isset($settings['height']) ? $settings['height'] : '1132px';
        $sound_enabled = isset($settings['sound']) ? $settings['sound'] : true;
        $speed = isset($settings['speed']) ? $settings['speed'] : 1000;
        $orientation = isset($settings['orientation']) ? $settings['orientation'] : 'portrait';
        $has_password = !empty($album['password']);
        $lock_images = isset($album['lock_images']) ? $album['lock_images'] : false;
        $prevent_screenshot = isset($settings['prevent_screenshot']) ? $settings['prevent_screenshot'] : false;
        $book_frame_color = isset($settings['book_frame_color']) ? $settings['book_frame_color'] : '#8B4513';
        $centering_offset = isset($settings['centering_offset']) ? $settings['centering_offset'] : 0;
        
        $image_border_width = isset($settings['image_border_width']) ? $settings['image_border_width'] : 0;
        $image_border_color = isset($settings['image_border_color']) ? $settings['image_border_color'] : '#000000';
        $image_border_style = isset($settings['image_border_style']) ? $settings['image_border_style'] : 'none';
        $button_bg_color = isset($settings['button_bg_color']) ? $settings['button_bg_color'] : '#8B4513';
        $button_text_color = isset($settings['button_text_color']) ? $settings['button_text_color'] : '#FFFFFF';
        $button_font_size = isset($settings['button_font_size']) ? $settings['button_font_size'] : 15;

        wp_enqueue_style('neoalbum-style');
        wp_enqueue_script('neoalbum-script');
        wp_localize_script('neoalbum-script', 'NEOALBUM_PLUGIN_URL', NEOALBUM_PLUGIN_URL);

        ob_start();
        ?>
        <div class="neoalbum-container" 
             data-album-id="<?php echo esc_attr($atts['id']); ?>"
             data-width="<?php echo esc_attr($width); ?>"
             data-height="<?php echo esc_attr($height); ?>"
             data-sound="<?php echo esc_attr($sound_enabled); ?>"
             data-speed="<?php echo esc_attr($speed); ?>"
             data-orientation="<?php echo esc_attr($orientation); ?>"
             data-has-password="<?php echo esc_attr($has_password ? '1' : '0'); ?>"
             data-lock-images="<?php echo esc_attr($lock_images ? '1' : '0'); ?>"
             data-prevent-screenshot="<?php echo esc_attr($prevent_screenshot ? '1' : '0'); ?>"
             style="margin-left: <?php echo esc_attr($centering_offset); ?>px; 
                    --img-border-width: <?php echo esc_attr($image_border_width); ?>px; 
                    --img-border-color: <?php echo esc_attr($image_border_color); ?>; 
                    --img-border-style: <?php echo esc_attr($image_border_style); ?>;
                    --btn-bg-color: <?php echo esc_attr($button_bg_color); ?>;
                    --btn-text-color: <?php echo esc_attr($button_text_color); ?>;
                    --btn-font-size: <?php echo esc_attr($button_font_size); ?>px;">
            
            <?php if ($has_password): ?>
                <div class="neoalbum-password-form">
                    <h3><?php echo esc_html__('این آلبوم با رمز عبور محافظت شده است', 'neoalbum'); ?></h3>
                    <p><?php echo esc_html__('لطفاً رمز عبور را برای مشاهده وارد کنید:', 'neoalbum'); ?></p>
                    <input type="password" class="neoalbum-password-input" placeholder="<?php echo esc_html__('رمز عبور را وارد کنید', 'neoalbum'); ?>" />
                    <button class="button neoalbum-submit-password"><?php echo esc_html__('مشاهده آلبوم', 'neoalbum'); ?></button>
                    <p class="neoalbum-password-error" style="display:none; color:red;"><?php echo esc_html__('رمز عبور اشتباه است', 'neoalbum'); ?></p>
                </div>
            <?php endif; ?>

            <div class="neoalbum-book-wrapper" <?php echo $has_password ? 'style="display:none;"' : ''; ?>>
                <div class="neoalbum-book" style="border-color: <?php echo esc_attr($book_frame_color); ?>;">
                    <div class="neoalbum-page-container left-container">
                        <div class="neoalbum-page left-page">
                            <div class="neoalbum-page-content">
                                <img src="" alt="" class="neoalbum-page-image">
                            </div>
                        </div>
                    </div>
                    <div class="neoalbum-page-container right-container">
                        <div class="neoalbum-page right-page">
                            <div class="neoalbum-page-content">
                                <img src="" alt="" class="neoalbum-page-image">
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="neoalbum-controls">
                    <button class="neoalbum-prev-btn" <?php echo $lock_images ? 'disabled' : ''; ?>>
                        قبلی
                    </button>
                    <button class="neoalbum-fullscreen-btn">
                        نمایش تمام صفحه
                    </button>
                    <button class="neoalbum-next-btn" <?php echo $lock_images ? 'disabled' : ''; ?>>
                        بعدی
                    </button>
                </div>
                
                <div class="neoalbum-fullscreen-zoom-icon" style="display:none;" title="زوم (بزرگنمایی)">
                    <svg viewBox="0 0 24 24" width="24" height="24" stroke="currentColor" stroke-width="2" fill="none" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"></circle>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        <line x1="11" y1="8" x2="11" y2="14"></line>
                        <line x1="8" y1="11" x2="14" y2="11"></line>
                    </svg>
                </div>
            </div>

            <div class="neoalbum-images-data" style="display:none;">
                <?php foreach ($album['images'] as $image_url): ?>
                    <span class="neoalbum-image-url" data-url="<?php echo esc_url($image_url); ?>"></span>
                <?php endforeach; ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function verify_password() {
        check_ajax_referer('neoalbum_nonce', 'nonce');

        $album_id = sanitize_text_field($_POST['album_id']);
        $password = sanitize_text_field($_POST['password']);
        
        $ip = $this->get_client_ip();
        $transient_key = 'neoalbum_rate_limit_' . md5($ip . '_' . $album_id);
        $max_attempts = 5;
        $lockout_duration = 900;
        
        $rate_limit = get_transient($transient_key);
        if ($rate_limit && $rate_limit['attempts'] >= $max_attempts) {
            wp_send_json_error(array('valid' => false, 'locked' => true, 'message' => __('تعداد تلاش‌های ناموفق بیش از حد مجاز است. لطفاً بعداً دوباره امتحان کنید.', 'neoalbum')));
        }

        $albums = get_option('neoalbum_albums', array());

        if (isset($albums[$album_id]) && $albums[$album_id]['password'] === $password) {
            delete_transient($transient_key);
            wp_send_json_success(array('valid' => true));
        }

        $new_attempts = $rate_limit ? $rate_limit['attempts'] + 1 : 1;
        set_transient($transient_key, array('attempts' => $new_attempts), $lockout_duration);
        wp_send_json_error(array('valid' => false, 'attempts' => $new_attempts, 'max_attempts' => $max_attempts));
    }
    
    private function get_client_ip() {
        $ip = '';
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return sanitize_text_field($ip);
    }
}

NeoAlbum_Shortcode::get_instance();

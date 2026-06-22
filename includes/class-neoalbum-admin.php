<?php
if (!defined('ABSPATH')) {
    exit;
}

class NeoAlbum_Admin {
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('wp_ajax_neoalbum_save_album', array($this, 'save_album'));
        add_action('wp_ajax_neoalbum_get_albums', array($this, 'get_albums'));
        add_action('wp_ajax_neoalbum_delete_album', array($this, 'delete_album'));
    }

    public function register_settings() {
        register_setting(
            'neoalbum_settings_group',
            'neoalbum_settings'
        );
    }

    public function add_admin_menu() {
        add_menu_page(
            'NeoAlbum',
            'NeoAlbum',
            'manage_options',
            'neoalbum',
            array($this, 'render_admin_page'),
            'dashicons-book-alt',
            30
        );

        add_submenu_page(
            'neoalbum',
            'Albums',
            'Albums',
            'manage_options',
            'neoalbum',
            array($this, 'render_admin_page')
        );

        add_submenu_page(
            'neoalbum',
            'Settings',
            'Settings',
            'manage_options',
            'neoalbum-settings',
            array($this, 'render_settings_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if ('toplevel_page_neoalbum' !== $hook && 'neoalbum_page_neoalbum-settings' !== $hook) {
            return;
        }

        wp_enqueue_style('neoalbum-admin-style', NEOALBUM_PLUGIN_URL . 'assets/css/admin.css', array(), NEOALBUM_VERSION);
        wp_enqueue_script('neoalbum-admin-script', NEOALBUM_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), NEOALBUM_VERSION, true);
        wp_enqueue_media();
        wp_localize_script('neoalbum-admin-script', 'neoalbum_admin', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('neoalbum_admin_nonce'),
            'strings' => array(
                'select_images' => __('انتخاب تصاویر', 'neoalbum'),
                'use_these_images' => __('استفاده از این تصاویر', 'neoalbum'),
                'delete_confirm' => __('آیا مطمئن هستید که می‌خواهید این آلبوم را حذف کنید؟', 'neoalbum')
            )
        ));
    }

    public function render_admin_page() {
        ?>
        <div class="wrap neoalbum-admin">
            <h1><?php echo esc_html__('آلبوم‌های نئو - آلبوم‌ها', 'neoalbum'); ?></h1>
            
            <div class="neoalbum-create-album">
                <h2><?php echo esc_html__('ایجاد آلبوم جدید', 'neoalbum'); ?></h2>
                <div class="neoalbum-form">
                    <div class="form-field">
                        <label for="neoalbum-name"><?php echo esc_html__('نام آلبوم', 'neoalbum'); ?></label>
                        <input type="text" id="neoalbum-name" class="regular-text" />
                    </div>
                    <div class="form-field">
                        <label><?php echo esc_html__('تصاویر آلبوم', 'neoalbum'); ?></label>
                        <button type="button" class="button button-primary neoalbum-upload-btn">
                            <?php echo esc_html__('انتخاب تصاویر', 'neoalbum'); ?>
                        </button>
                        <div class="neoalbum-images-preview"></div>
                    </div>
                    <div class="form-field">
                        <label for="neoalbum-password"><?php echo esc_html__('رمز عبور (اختیاری)', 'neoalbum'); ?></label>
                        <input type="text" id="neoalbum-password" class="regular-text" placeholder="<?php echo esc_html__('برای بدون رمز ماندن خالی بگذارید', 'neoalbum'); ?>" />
                    </div>
                    <div class="form-field">
                        <label for="neoalbum-lock-images">
                            <input type="checkbox" id="neoalbum-lock-images" />
                            <?php echo esc_html__('قفل کردن تصاویر (جلوگیری از ذخیره/کلیک)', 'neoalbum'); ?>
                        </label>
                    </div>
                    <button type="button" class="button button-primary neoalbum-save-btn">
                        <?php echo esc_html__('ایجاد آلبوم', 'neoalbum'); ?>
                    </button>
                </div>
            </div>

            <div class="neoalbum-albums-list">
                <h2><?php echo esc_html__('آلبوم‌های شما', 'neoalbum'); ?></h2>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php echo esc_html__('نام آلبوم', 'neoalbum'); ?></th>
                            <th><?php echo esc_html__('تصاویر آلبوم', 'neoalbum'); ?></th>
                            <th><?php echo esc_html__('کد کوتاه', 'neoalbum'); ?></th>
                            <th><?php echo esc_html__('عملیات', 'neoalbum'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="neoalbum-albums-tbody"></tbody>
                </table>
            </div>
        </div>
        <?php
    }

    public function render_settings_page() {
        $options = get_option('neoalbum_settings', array());
        ?>
        <div class="wrap neoalbum-admin">
            <h1><?php echo esc_html__('آلبوم‌های نئو - تنظیمات', 'neoalbum'); ?></h1>
            
            <form method="post" action="options.php">
                <?php
                settings_fields('neoalbum_settings_group');
                do_settings_sections('neoalbum_settings');
                ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('عرض پیش‌فرض', 'neoalbum'); ?></th>
                        <td>
                            <input type="text" name="neoalbum_settings[width]" value="<?php echo isset($options['width']) ? esc_attr($options['width']) : '800px'; ?>" />
                            <p class="description"><?php echo esc_html__('عرض پیش‌فرض آلبوم (مثلاً 800px یا 100%)', 'neoalbum'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('ارتفاع پیش‌فرض', 'neoalbum'); ?></th>
                        <td>
                            <input type="text" name="neoalbum_settings[height]" value="<?php echo isset($options['height']) ? esc_attr($options['height']) : '1132px'; ?>" />
                            <p class="description"><?php echo esc_html__('ارتفاع پیش‌فرض آلبوم (نسبت A4: حدود 1132px برای عرض 800px)', 'neoalbum'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('صدای ورق زدن صفحه', 'neoalbum'); ?></th>
                        <td>
                            <select name="neoalbum_settings[sound]">
                                <option value="1" <?php selected(isset($options['sound']) ? $options['sound'] : 1, 1); ?>><?php echo esc_html__('فعال', 'neoalbum'); ?></option>
                                <option value="0" <?php selected(isset($options['sound']) ? $options['sound'] : 1, 0); ?>><?php echo esc_html__('غیرفعال', 'neoalbum'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('سرعت انیمیشن', 'neoalbum'); ?></th>
                        <td>
                            <input type="number" name="neoalbum_settings[speed]" value="<?php echo isset($options['speed']) ? esc_attr($options['speed']) : 1000; ?>" min="100" max="3000" />
                            <p class="description"><?php echo esc_html__('سرعت انیمیشن ورق زدن بر حسب میلی‌ثانیه', 'neoalbum'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('جهت پیش‌فرض', 'neoalbum'); ?></th>
                        <td>
                            <select name="neoalbum_settings[orientation]">
                                <option value="portrait" <?php selected(isset($options['orientation']) ? $options['orientation'] : 'portrait', 'portrait'); ?>><?php echo esc_html__('عمودی (پیش‌فرض)', 'neoalbum'); ?></option>
                                <option value="landscape" <?php selected(isset($options['orientation']) ? $options['orientation'] : 'portrait', 'landscape'); ?>><?php echo esc_html__('افقی', 'neoalbum'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('جلوگیری از اسکرین‌شات', 'neoalbum'); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="neoalbum_settings[prevent_screenshot]" value="1" <?php checked(isset($options['prevent_screenshot']) ? $options['prevent_screenshot'] : false, true); ?> />
                                <?php echo esc_html__('فعال کردن جلوگیری از اسکرین‌شات (بستن دکمه PrintScreen و کلیک راست)', 'neoalbum'); ?>
                            </label>
                            <p class="description"><?php echo esc_html__('توجه: جلوگیری کامل از اسکرین‌شات در مرورگرها ممکن نیست، این فقط محافظت اولیه را فراهم می‌کند.', 'neoalbum'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('رنگ قاب کتاب', 'neoalbum'); ?></th>
                        <td>
                            <input type="color" name="neoalbum_settings[book_frame_color]" value="<?php echo isset($options['book_frame_color']) ? esc_attr($options['book_frame_color']) : '#8B4513'; ?>" />
                            <p class="description"><?php echo esc_html__('رنگ قاب/حاشیه کتاب را انتخاب کنید', 'neoalbum'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('تراز وسط', 'neoalbum'); ?></th>
                        <td>
                            <input type="number" name="neoalbum_settings[centering_offset]" value="<?php echo isset($options['centering_offset']) ? esc_attr($options['centering_offset']) : 0; ?>" min="-500" max="500" />
                            <p class="description"><?php echo esc_html__('تنظیم تراز افقی (پیکسل، منفی = چپ، مثبت = راست)', 'neoalbum'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('ضخامت حاشیه تصویر', 'neoalbum'); ?></th>
                        <td>
                            <input type="number" name="neoalbum_settings[image_border_width]" value="<?php echo isset($options['image_border_width']) ? esc_attr($options['image_border_width']) : 0; ?>" min="0" max="50" />
                            <p class="description"><?php echo esc_html__('ضخامت حاشیه دور تصاویر (به پیکسل)', 'neoalbum'); ?></p>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('رنگ حاشیه تصویر', 'neoalbum'); ?></th>
                        <td>
                            <input type="color" name="neoalbum_settings[image_border_color]" value="<?php echo isset($options['image_border_color']) ? esc_attr($options['image_border_color']) : '#000000'; ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('استایل حاشیه تصویر', 'neoalbum'); ?></th>
                        <td>
                            <select name="neoalbum_settings[image_border_style]">
                                <option value="none" <?php selected(isset($options['image_border_style']) ? $options['image_border_style'] : 'none', 'none'); ?>><?php echo esc_html__('بدون حاشیه', 'neoalbum'); ?></option>
                                <option value="solid" <?php selected(isset($options['image_border_style']) ? $options['image_border_style'] : 'none', 'solid'); ?>><?php echo esc_html__('خط صاف (Solid)', 'neoalbum'); ?></option>
                                <option value="dashed" <?php selected(isset($options['image_border_style']) ? $options['image_border_style'] : 'none', 'dashed'); ?>><?php echo esc_html__('خط چین (Dashed)', 'neoalbum'); ?></option>
                                <option value="dotted" <?php selected(isset($options['image_border_style']) ? $options['image_border_style'] : 'none', 'dotted'); ?>><?php echo esc_html__('نقطه‌ای (Dotted)', 'neoalbum'); ?></option>
                                <option value="double" <?php selected(isset($options['image_border_style']) ? $options['image_border_style'] : 'none', 'double'); ?>><?php echo esc_html__('دو خطی (Double)', 'neoalbum'); ?></option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('رنگ پس‌زمینه دکمه‌ها', 'neoalbum'); ?></th>
                        <td>
                            <input type="color" name="neoalbum_settings[button_bg_color]" value="<?php echo isset($options['button_bg_color']) ? esc_attr($options['button_bg_color']) : '#8B4513'; ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('رنگ متن دکمه‌ها', 'neoalbum'); ?></th>
                        <td>
                            <input type="color" name="neoalbum_settings[button_text_color]" value="<?php echo isset($options['button_text_color']) ? esc_attr($options['button_text_color']) : '#FFFFFF'; ?>" />
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row"><?php echo esc_html__('سایز فونت دکمه‌ها', 'neoalbum'); ?></th>
                        <td>
                            <input type="number" name="neoalbum_settings[button_font_size]" value="<?php echo isset($options['button_font_size']) ? esc_attr($options['button_font_size']) : 15; ?>" min="10" max="30" />
                            <p class="description"><?php echo esc_html__('سایز فونت متن دکمه‌ها (به پیکسل)', 'neoalbum'); ?></p>
                        </td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function save_album() {
        check_ajax_referer('neoalbum_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('غیرمجاز', 'neoalbum')));
        }

        $album_data = array(
            'name' => sanitize_text_field($_POST['name']),
            'images' => isset($_POST['images']) ? array_map('esc_url', $_POST['images']) : array(),
            'password' => sanitize_text_field($_POST['password']),
            'lock_images' => isset($_POST['lock_images']) ? (bool)$_POST['lock_images'] : false,
            'created_at' => current_time('mysql')
        );

        $albums = get_option('neoalbum_albums', array());
        $album_id = uniqid('neoalbum_');
        $albums[$album_id] = $album_data;
        update_option('neoalbum_albums', $albums);

        wp_send_json_success(array(
            'message' => __('آلبوم با موفقیت ایجاد شد!', 'neoalbum'),
            'album_id' => $album_id,
            'shortcode' => '[neoalbum id="' . $album_id . '"]'
        ));
    }

    public function get_albums() {
        check_ajax_referer('neoalbum_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('غیرمجاز', 'neoalbum')));
        }

        $albums = get_option('neoalbum_albums', array());
        $formatted_albums = array();

        foreach ($albums as $id => $album) {
            $formatted_albums[] = array(
                'id' => $id,
                'name' => $album['name'],
                'image_count' => count($album['images']),
                'shortcode' => '[neoalbum id="' . $id . '"]'
            );
        }

        wp_send_json_success(array('albums' => $formatted_albums));
    }

    public function delete_album() {
        check_ajax_referer('neoalbum_admin_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('غیرمجاز', 'neoalbum')));
        }

        $album_id = sanitize_text_field($_POST['album_id']);
        $albums = get_option('neoalbum_albums', array());

        if (isset($albums[$album_id])) {
            unset($albums[$album_id]);
            update_option('neoalbum_albums', $albums);
            wp_send_json_success(array('message' => __('آلبوم با موفقیت حذف شد!', 'neoalbum')));
        }

        wp_send_json_error(array('message' => __('آلبوم پیدا نشد', 'neoalbum')));
    }
}

NeoAlbum_Admin::get_instance();

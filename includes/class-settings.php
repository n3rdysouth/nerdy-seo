<?php
/**
 * Settings functionality
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Settings class
 */
class Nerdy_SEO_Settings {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Register settings
        register_setting('nerdy_seo_settings', 'nerdy_seo_home_title', array(
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('nerdy_seo_settings', 'nerdy_seo_home_description', array(
            'sanitize_callback' => 'sanitize_textarea_field',
        ));
        register_setting('nerdy_seo_settings', 'nerdy_seo_default_title_format', array(
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('nerdy_seo_settings', 'nerdy_seo_og_enabled', array(
            'sanitize_callback' => 'absint',
            'default' => 1,
        ));
        register_setting('nerdy_seo_settings', 'nerdy_seo_twitter_enabled', array(
            'sanitize_callback' => 'absint',
            'default' => 1,
        ));
        register_setting('nerdy_seo_settings', 'nerdy_seo_twitter_site', array(
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('nerdy_seo_settings', 'nerdy_seo_default_og_image', array(
            'sanitize_callback' => 'esc_url_raw',
        ));
        register_setting('nerdy_seo_settings', 'nerdy_seo_schema_enabled', array(
            'sanitize_callback' => 'absint',
            'default' => 1,
        ));

        // General section
        add_settings_section(
            'nerdy_seo_general_section',
            __('General Settings', 'nerdy-seo'),
            array($this, 'render_general_section'),
            'nerdy-seo'
        );

        add_settings_field(
            'nerdy_seo_home_title',
            __('Homepage Title', 'nerdy-seo'),
            array($this, 'render_text_field'),
            'nerdy-seo',
            'nerdy_seo_general_section',
            array(
                'name' => 'nerdy_seo_home_title',
                'placeholder' => get_bloginfo('name'),
            )
        );

        add_settings_field(
            'nerdy_seo_home_description',
            __('Homepage Description', 'nerdy-seo'),
            array($this, 'render_textarea_field'),
            'nerdy-seo',
            'nerdy_seo_general_section',
            array(
                'name' => 'nerdy_seo_home_description',
                'placeholder' => get_bloginfo('description'),
            )
        );

        add_settings_field(
            'nerdy_seo_default_title_format',
            __('Default Title Format', 'nerdy-seo'),
            array($this, 'render_text_field'),
            'nerdy-seo',
            'nerdy_seo_general_section',
            array(
                'name' => 'nerdy_seo_default_title_format',
                'placeholder' => '%title% | %sitename%',
                'description' => __('Variables: %title%, %sitename%, %sitedesc%, %excerpt%, %author%, %date%, %year%, %month%, %day%', 'nerdy-seo'),
            )
        );

        // Social Media section
        add_settings_section(
            'nerdy_seo_social_section',
            __('Social Media Settings', 'nerdy-seo'),
            array($this, 'render_social_section'),
            'nerdy-seo'
        );

        add_settings_field(
            'nerdy_seo_og_enabled',
            __('Enable Open Graph', 'nerdy-seo'),
            array($this, 'render_checkbox_field'),
            'nerdy-seo',
            'nerdy_seo_social_section',
            array(
                'name' => 'nerdy_seo_og_enabled',
                'description' => __('Enable Open Graph meta tags for Facebook and other social networks', 'nerdy-seo'),
            )
        );

        add_settings_field(
            'nerdy_seo_twitter_enabled',
            __('Enable Twitter Cards', 'nerdy-seo'),
            array($this, 'render_checkbox_field'),
            'nerdy-seo',
            'nerdy_seo_social_section',
            array(
                'name' => 'nerdy_seo_twitter_enabled',
                'description' => __('Enable Twitter Card meta tags', 'nerdy-seo'),
            )
        );

        add_settings_field(
            'nerdy_seo_twitter_site',
            __('Twitter Username', 'nerdy-seo'),
            array($this, 'render_text_field'),
            'nerdy-seo',
            'nerdy_seo_social_section',
            array(
                'name' => 'nerdy_seo_twitter_site',
                'placeholder' => '@yourusername',
                'description' => __('Your Twitter username (include @)', 'nerdy-seo'),
            )
        );

        add_settings_field(
            'nerdy_seo_default_og_image',
            __('Default Social Image', 'nerdy-seo'),
            array($this, 'render_image_field'),
            'nerdy-seo',
            'nerdy_seo_social_section',
            array(
                'name' => 'nerdy_seo_default_og_image',
                'description' => __('Fallback image for social sharing when no featured image is set (1200x630px recommended)', 'nerdy-seo'),
            )
        );

        // Schema section
        add_settings_section(
            'nerdy_seo_schema_section',
            __('Schema Markup Settings', 'nerdy-seo'),
            array($this, 'render_schema_section'),
            'nerdy-seo'
        );

        add_settings_field(
            'nerdy_seo_schema_enabled',
            __('Enable Schema Markup', 'nerdy-seo'),
            array($this, 'render_checkbox_field'),
            'nerdy-seo',
            'nerdy_seo_schema_section',
            array(
                'name' => 'nerdy_seo_schema_enabled',
                'description' => __('Enable automatic schema markup output (JSON-LD)', 'nerdy-seo'),
            )
        );
    }

    /**
     * Render general section
     */
    public function render_general_section() {
        echo '<p>' . __('Configure the basic SEO settings for your website.', 'nerdy-seo') . '</p>';
    }

    /**
     * Render social section
     */
    public function render_social_section() {
        echo '<p>' . __('Configure how your content appears when shared on social media.', 'nerdy-seo') . '</p>';
    }

    /**
     * Render schema section
     */
    public function render_schema_section() {
        echo '<p>' . __('Configure schema markup (structured data) settings.', 'nerdy-seo') . '</p>';
    }

    /**
     * Render text field
     */
    public function render_text_field($args) {
        $name = $args['name'];
        $value = get_option($name, '');
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        $description = isset($args['description']) ? $args['description'] : '';

        ?>
        <input
            type="text"
            name="<?php echo esc_attr($name); ?>"
            value="<?php echo esc_attr($value); ?>"
            placeholder="<?php echo esc_attr($placeholder); ?>"
            class="regular-text"
        />
        <?php if ($description): ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php
    }

    /**
     * Render textarea field
     */
    public function render_textarea_field($args) {
        $name = $args['name'];
        $value = get_option($name, '');
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        $description = isset($args['description']) ? $args['description'] : '';

        ?>
        <textarea
            name="<?php echo esc_attr($name); ?>"
            placeholder="<?php echo esc_attr($placeholder); ?>"
            rows="3"
            class="large-text"
        ><?php echo esc_textarea($value); ?></textarea>
        <?php if ($description): ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php
    }

    /**
     * Render checkbox field
     */
    public function render_checkbox_field($args) {
        $name = $args['name'];
        $value = get_option($name, true);
        $description = isset($args['description']) ? $args['description'] : '';

        ?>
        <label>
            <input
                type="checkbox"
                name="<?php echo esc_attr($name); ?>"
                value="1"
                <?php checked($value, true); ?>
            />
            <?php if ($description): ?>
                <?php echo esc_html($description); ?>
            <?php endif; ?>
        </label>
        <?php
    }

    /**
     * Render image field
     */
    public function render_image_field($args) {
        $name = $args['name'];
        $value = get_option($name, '');
        $description = isset($args['description']) ? $args['description'] : '';

        ?>
        <div class="nerdy-seo-image-field">
            <input
                type="text"
                name="<?php echo esc_attr($name); ?>"
                id="<?php echo esc_attr($name); ?>"
                value="<?php echo esc_url($value); ?>"
                class="regular-text"
            />
            <button
                type="button"
                class="button nerdy-seo-upload-image-btn"
                data-target="<?php echo esc_attr($name); ?>"
            >
                <?php _e('Upload Image', 'nerdy-seo'); ?>
            </button>
            <?php if ($value): ?>
                <br><br>
                <img src="<?php echo esc_url($value); ?>" style="max-width: 300px; height: auto; border: 1px solid #ddd; padding: 5px;" />
            <?php endif; ?>
            <?php if ($description): ?>
                <p class="description"><?php echo esc_html($description); ?></p>
            <?php endif; ?>
        </div>

        <script>
        (function($) {
            $(document).ready(function() {
                var mediaUploader;

                $('.nerdy-seo-upload-image-btn').on('click', function(e) {
                    e.preventDefault();
                    var button = $(this);
                    var targetId = button.data('target');

                    if (mediaUploader) {
                        mediaUploader.open();
                        return;
                    }

                    mediaUploader = wp.media({
                        title: '<?php _e('Choose Image', 'nerdy-seo'); ?>',
                        button: {
                            text: '<?php _e('Use this image', 'nerdy-seo'); ?>'
                        },
                        multiple: false
                    });

                    mediaUploader.on('select', function() {
                        var attachment = mediaUploader.state().get('selection').first().toJSON();
                        $('#' + targetId).val(attachment.url);
                        location.reload();
                    });

                    mediaUploader.open();
                });
            });
        })(jQuery);
        </script>
        <?php
    }
}

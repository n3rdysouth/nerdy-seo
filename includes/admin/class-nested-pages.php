<?php
/**
 * NestedPages Integration
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Nested Pages integration class
 */
class Nerdy_SEO_Nested_Pages {

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
        // Add SEO columns to NestedPages
        add_filter('nestedpages_row_data', array($this, 'add_seo_data_to_row'), 10, 2);

        // Add quick edit fields
        add_filter('nestedpages_quick_edit_data', array($this, 'add_quick_edit_fields'), 10, 2);

        // Save quick edit data
        add_action('wp_ajax_nerdy_seo_quick_edit', array($this, 'save_quick_edit'));

        // Add custom CSS for NestedPages integration
        add_action('admin_head', array($this, 'add_admin_css'));

        // Add inline JavaScript for quick edit
        add_action('admin_footer', array($this, 'add_admin_js'));
    }

    /**
     * Add SEO data to NestedPages row
     */
    public function add_seo_data_to_row($data, $post) {
        $meta_title = get_post_meta($post->ID, '_nerdy_seo_title', true);
        $meta_description = get_post_meta($post->ID, '_nerdy_seo_description', true);

        // Add SEO indicator
        $has_seo = !empty($meta_title) || !empty($meta_description);

        $data['nerdy_seo_status'] = $has_seo ? 'optimized' : 'needs-attention';
        $data['nerdy_seo_title'] = $meta_title;
        $data['nerdy_seo_description'] = $meta_description;

        return $data;
    }

    /**
     * Add quick edit fields
     */
    public function add_quick_edit_fields($fields, $post) {
        $meta_title = get_post_meta($post->ID, '_nerdy_seo_title', true);
        $meta_description = get_post_meta($post->ID, '_nerdy_seo_description', true);
        $focus_keyword = get_post_meta($post->ID, '_nerdy_seo_focus_keyword', true);

        // Add SEO fields to quick edit
        $fields['nerdy_seo'] = array(
            'label' => __('SEO Settings', 'nerdy-seo'),
            'type' => 'custom',
            'html' => $this->render_quick_edit_html($post, $meta_title, $meta_description, $focus_keyword),
        );

        return $fields;
    }

    /**
     * Render quick edit HTML
     */
    private function render_quick_edit_html($post, $title, $description, $keyword) {
        ob_start();
        ?>
        <div class="nerdy-seo-quick-edit">
            <div class="nerdy-seo-field">
                <label><?php esc_html_e('SEO Title', 'nerdy-seo'); ?></label>
                <input
                    type="text"
                    class="nerdy-seo-title-input"
                    data-post-id="<?php echo esc_attr($post->ID); ?>"
                    value="<?php echo esc_attr($title); ?>"
                    placeholder="<?php echo esc_attr($post->post_title); ?>"
                />
                <span class="nerdy-seo-char-count">
                    <?php
                    $length = mb_strlen($title);
                    echo sprintf(__('%d characters', 'nerdy-seo'), $length);
                    ?>
                </span>
            </div>

            <div class="nerdy-seo-field">
                <label><?php esc_html_e('Meta Description', 'nerdy-seo'); ?></label>
                <textarea
                    class="nerdy-seo-description-input"
                    data-post-id="<?php echo esc_attr($post->ID); ?>"
                    rows="3"
                    placeholder="<?php esc_html_e('Enter meta description...', 'nerdy-seo'); ?>"
                ><?php echo esc_textarea($description); ?></textarea>
                <span class="nerdy-seo-char-count">
                    <?php
                    $length = mb_strlen($description);
                    echo sprintf(__('%d characters', 'nerdy-seo'), $length);
                    ?>
                </span>
            </div>

            <div class="nerdy-seo-field">
                <label><?php esc_html_e('Focus Keyword', 'nerdy-seo'); ?></label>
                <input
                    type="text"
                    class="nerdy-seo-keyword-input"
                    data-post-id="<?php echo esc_attr($post->ID); ?>"
                    value="<?php echo esc_attr($keyword); ?>"
                    placeholder="<?php esc_html_e('Enter focus keyword...', 'nerdy-seo'); ?>"
                />
            </div>

            <button
                type="button"
                class="button button-primary nerdy-seo-save-btn"
                data-post-id="<?php echo esc_attr($post->ID); ?>"
            >
                <?php esc_html_e('Save SEO', 'nerdy-seo'); ?>
            </button>

            <span class="nerdy-seo-save-status"></span>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Save quick edit data
     */
    public function save_quick_edit() {
        // Check nonce
        check_ajax_referer('nerdy_seo_quick_edit', 'nonce');

        // Check permissions
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied', 'nerdy-seo')));
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

        if (!$post_id) {
            wp_send_json_error(array('message' => __('Invalid post ID', 'nerdy-seo')));
        }

        // Save meta data
        if (isset($_POST['title'])) {
            update_post_meta($post_id, '_nerdy_seo_title', sanitize_text_field($_POST['title']));
        }

        if (isset($_POST['description'])) {
            update_post_meta($post_id, '_nerdy_seo_description', sanitize_text_field($_POST['description']));
        }

        if (isset($_POST['keyword'])) {
            update_post_meta($post_id, '_nerdy_seo_focus_keyword', sanitize_text_field($_POST['keyword']));
        }

        wp_send_json_success(array(
            'message' => __('SEO data saved successfully', 'nerdy-seo'),
        ));
    }

    /**
     * Add admin CSS
     */
    public function add_admin_css() {
        $screen = get_current_screen();

        // Only load on NestedPages screen
        if (!$screen || $screen->id !== 'pages_page_nestedpages') {
            return;
        }

        ?>
        <style>
            .nerdy-seo-quick-edit {
                padding: 15px;
                background: #f9f9f9;
                border: 1px solid #ddd;
                border-radius: 3px;
                margin-top: 10px;
            }
            .nerdy-seo-quick-edit .nerdy-seo-field {
                margin-bottom: 15px;
            }
            .nerdy-seo-quick-edit label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
                font-size: 13px;
            }
            .nerdy-seo-quick-edit input[type="text"],
            .nerdy-seo-quick-edit textarea {
                width: 100%;
                padding: 6px 8px;
                border: 1px solid #ddd;
                border-radius: 3px;
            }
            .nerdy-seo-char-count {
                display: block;
                font-size: 11px;
                color: #666;
                margin-top: 3px;
            }
            .nerdy-seo-save-btn {
                margin-top: 10px;
            }
            .nerdy-seo-save-status {
                margin-left: 10px;
                font-size: 13px;
            }
            .nerdy-seo-save-status.success {
                color: #46b450;
            }
            .nerdy-seo-save-status.error {
                color: #dc3232;
            }
            .nerdy-seo-indicator {
                display: inline-block;
                width: 10px;
                height: 10px;
                border-radius: 50%;
                margin-right: 5px;
            }
            .nerdy-seo-indicator.optimized {
                background: #46b450;
            }
            .nerdy-seo-indicator.needs-attention {
                background: #ffb900;
            }
        </style>
        <?php
    }

    /**
     * Add admin JavaScript
     */
    public function add_admin_js() {
        $screen = get_current_screen();

        // Only load on NestedPages screen
        if (!$screen || $screen->id !== 'pages_page_nestedpages') {
            return;
        }

        ?>
        <script>
        (function($) {
            $(document).ready(function() {
                // Character counter
                function updateCharCount($field, $counter) {
                    var length = $field.val().length;
                    $counter.text(length + ' characters');

                    // Color coding
                    if ($field.hasClass('nerdy-seo-title-input')) {
                        if (length >= 50 && length <= 60) {
                            $counter.css('color', '#46b450');
                        } else if (length > 0 && length < 70) {
                            $counter.css('color', '#ffb900');
                        } else {
                            $counter.css('color', '#dc3232');
                        }
                    } else if ($field.hasClass('nerdy-seo-description-input')) {
                        if (length >= 150 && length <= 160) {
                            $counter.css('color', '#46b450');
                        } else if (length > 0 && length < 170) {
                            $counter.css('color', '#ffb900');
                        } else {
                            $counter.css('color', '#dc3232');
                        }
                    }
                }

                // Bind character counter
                $(document).on('input', '.nerdy-seo-title-input, .nerdy-seo-description-input', function() {
                    var $field = $(this);
                    var $counter = $field.siblings('.nerdy-seo-char-count');
                    updateCharCount($field, $counter);
                });

                // Save SEO data
                $(document).on('click', '.nerdy-seo-save-btn', function(e) {
                    e.preventDefault();

                    var $btn = $(this);
                    var $container = $btn.closest('.nerdy-seo-quick-edit');
                    var $status = $container.find('.nerdy-seo-save-status');
                    var postId = $btn.data('post-id');

                    var data = {
                        action: 'nerdy_seo_quick_edit',
                        nonce: '<?php echo wp_create_nonce("nerdy_seo_quick_edit"); ?>',
                        post_id: postId,
                        title: $container.find('.nerdy-seo-title-input').val(),
                        description: $container.find('.nerdy-seo-description-input').val(),
                        keyword: $container.find('.nerdy-seo-keyword-input').val()
                    };

                    $btn.prop('disabled', true).text('<?php esc_html_e('Saving...', 'nerdy-seo'); ?>');
                    $status.removeClass('success error').text('');

                    $.post(ajaxurl, data, function(response) {
                        if (response.success) {
                            $status.addClass('success').text('✓ ' + response.data.message);
                            setTimeout(function() {
                                $status.fadeOut();
                            }, 3000);
                        } else {
                            $status.addClass('error').text('✗ ' + (response.data.message || '<?php esc_html_e('Error saving', 'nerdy-seo'); ?>'));
                        }

                        $btn.prop('disabled', false).text('<?php esc_html_e('Save SEO', 'nerdy-seo'); ?>');
                    }).fail(function() {
                        $status.addClass('error').text('✗ <?php esc_html_e('Error saving', 'nerdy-seo'); ?>');
                        $btn.prop('disabled', false).text('<?php esc_html_e('Save SEO', 'nerdy-seo'); ?>');
                    });
                });
            });
        })(jQuery);
        </script>
        <?php
    }
}

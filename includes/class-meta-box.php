<?php
/**
 * Meta Box functionality
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Meta Box class
 */
class Nerdy_SEO_Meta_Box {

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
        add_action('add_meta_boxes', array($this, 'add_meta_box'));
        add_action('save_post', array($this, 'save_meta_box'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Add meta box to post types
     */
    public function add_meta_box() {
        $post_types = $this->get_enabled_post_types();

        foreach ($post_types as $post_type) {
            add_meta_box(
                'nerdy_seo_meta_box',
                __('Nerdy SEO Settings', 'nerdy-seo'),
                array($this, 'render_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    /**
     * Get enabled post types
     */
    private function get_enabled_post_types() {
        $post_types = get_post_types(array('public' => true), 'names');

        // Allow filtering
        return apply_filters('nerdy_seo_post_types', $post_types);
    }

    /**
     * Render meta box
     */
    public function render_meta_box($post) {
        // Add nonce for security
        wp_nonce_field('nerdy_seo_meta_box', 'nerdy_seo_meta_box_nonce');

        // Get existing values
        $meta_title = get_post_meta($post->ID, '_nerdy_seo_title', true);
        $meta_description = get_post_meta($post->ID, '_nerdy_seo_description', true);
        $focus_keyword = get_post_meta($post->ID, '_nerdy_seo_focus_keyword', true);
        $noindex = get_post_meta($post->ID, '_nerdy_seo_noindex', true);
        $nofollow = get_post_meta($post->ID, '_nerdy_seo_nofollow', true);
        $canonical_url = get_post_meta($post->ID, '_nerdy_seo_canonical', true);

        // Get character counts
        $title_length = mb_strlen($meta_title);
        $desc_length = mb_strlen($meta_description);

        ?>
        <div class="nerdy-seo-meta-box">
            <style>
                .nerdy-seo-meta-box { padding: 10px 0; }
                .nerdy-seo-field { margin-bottom: 20px; }
                .nerdy-seo-field label {
                    display: block;
                    font-weight: 600;
                    margin-bottom: 5px;
                    font-size: 13px;
                }
                .nerdy-seo-field input[type="text"],
                .nerdy-seo-field textarea {
                    width: 100%;
                    padding: 8px;
                    border: 1px solid #ddd;
                    border-radius: 3px;
                }
                .nerdy-seo-field textarea {
                    min-height: 80px;
                    resize: vertical;
                }
                .nerdy-seo-counter {
                    font-size: 12px;
                    color: #666;
                    margin-top: 5px;
                }
                .nerdy-seo-counter.good { color: #46b450; }
                .nerdy-seo-counter.warning { color: #ffb900; }
                .nerdy-seo-counter.bad { color: #dc3232; }
                .nerdy-seo-field-description {
                    font-size: 12px;
                    color: #666;
                    font-style: italic;
                    margin-top: 5px;
                }
                .nerdy-seo-checkbox-group { margin-top: 15px; }
                .nerdy-seo-checkbox-group label {
                    display: inline-block;
                    font-weight: normal;
                    margin-left: 5px;
                }
                .nerdy-seo-preview {
                    background: #f5f5f5;
                    padding: 15px;
                    border-radius: 3px;
                    margin-top: 15px;
                    border-left: 3px solid #2271b1;
                }
                .nerdy-seo-preview h4 {
                    margin: 0 0 10px 0;
                    font-size: 13px;
                }
                .nerdy-seo-preview-title {
                    color: #1a0dab;
                    font-size: 18px;
                    line-height: 1.2;
                    margin-bottom: 5px;
                }
                .nerdy-seo-preview-url {
                    color: #006621;
                    font-size: 14px;
                    margin-bottom: 5px;
                }
                .nerdy-seo-preview-description {
                    color: #545454;
                    font-size: 13px;
                    line-height: 1.4;
                }
            </style>

            <div class="nerdy-seo-field">
                <label for="nerdy_seo_title">
                    <?php _e('SEO Title', 'nerdy-seo'); ?>
                </label>
                <input
                    type="text"
                    id="nerdy_seo_title"
                    name="nerdy_seo_title"
                    value="<?php echo esc_attr($meta_title); ?>"
                    placeholder="<?php echo esc_attr($post->post_title); ?>"
                />
                <div class="nerdy-seo-counter" data-field="title">
                    <?php
                    $title_status = $this->get_length_status($title_length, 50, 60);
                    printf(
                        '<span class="%s">%d characters. Recommended: 50-60 characters.</span>',
                        esc_attr($title_status),
                        $title_length
                    );
                    ?>
                </div>
            </div>

            <div class="nerdy-seo-field">
                <label for="nerdy_seo_description">
                    <?php _e('Meta Description', 'nerdy-seo'); ?>
                </label>
                <textarea
                    id="nerdy_seo_description"
                    name="nerdy_seo_description"
                    placeholder="<?php _e('Brief description of this page for search engines...', 'nerdy-seo'); ?>"
                ><?php echo esc_textarea($meta_description); ?></textarea>
                <div class="nerdy-seo-counter" data-field="description">
                    <?php
                    $desc_status = $this->get_length_status($desc_length, 150, 160);
                    printf(
                        '<span class="%s">%d characters. Recommended: 150-160 characters.</span>',
                        esc_attr($desc_status),
                        $desc_length
                    );
                    ?>
                </div>
            </div>

            <div class="nerdy-seo-field">
                <label for="nerdy_seo_focus_keyword">
                    <?php _e('Focus Keyword', 'nerdy-seo'); ?>
                </label>
                <input
                    type="text"
                    id="nerdy_seo_focus_keyword"
                    name="nerdy_seo_focus_keyword"
                    value="<?php echo esc_attr($focus_keyword); ?>"
                    placeholder="<?php _e('e.g., wordpress seo', 'nerdy-seo'); ?>"
                />
                <p class="nerdy-seo-field-description">
                    <?php _e('Primary keyword you want this page to rank for', 'nerdy-seo'); ?>
                </p>
            </div>

            <div class="nerdy-seo-field">
                <label for="nerdy_seo_canonical">
                    <?php _e('Canonical URL', 'nerdy-seo'); ?>
                </label>
                <input
                    type="text"
                    id="nerdy_seo_canonical"
                    name="nerdy_seo_canonical"
                    value="<?php echo esc_attr($canonical_url); ?>"
                    placeholder="<?php echo esc_url(get_permalink($post->ID)); ?>"
                />
                <p class="nerdy-seo-field-description">
                    <?php _e('Override the canonical URL for this page (leave blank for default)', 'nerdy-seo'); ?>
                </p>
            </div>

            <div class="nerdy-seo-checkbox-group">
                <label>
                    <input
                        type="checkbox"
                        name="nerdy_seo_noindex"
                        value="1"
                        <?php checked($noindex, '1'); ?>
                    />
                    <?php _e('No Index (tell search engines not to index this page)', 'nerdy-seo'); ?>
                </label>
                <br>
                <label>
                    <input
                        type="checkbox"
                        name="nerdy_seo_nofollow"
                        value="1"
                        <?php checked($nofollow, '1'); ?>
                    />
                    <?php _e('No Follow (tell search engines not to follow links on this page)', 'nerdy-seo'); ?>
                </label>
            </div>

            <div class="nerdy-seo-preview">
                <h4><?php _e('Google Search Preview', 'nerdy-seo'); ?></h4>
                <div class="nerdy-seo-preview-title" id="preview-title">
                    <?php echo esc_html($meta_title ?: $post->post_title); ?>
                </div>
                <div class="nerdy-seo-preview-url">
                    <?php echo esc_url(get_permalink($post->ID)); ?>
                </div>
                <div class="nerdy-seo-preview-description" id="preview-description">
                    <?php
                    if ($meta_description) {
                        echo esc_html($meta_description);
                    } else {
                        echo esc_html(wp_trim_words($post->post_content, 20));
                    }
                    ?>
                </div>
            </div>
        </div>

        <script>
        (function($) {
            $(document).ready(function() {
                // Update character counts in real-time
                function updateCounter(field) {
                    var $field = $('#nerdy_seo_' + field);
                    var $counter = $('.nerdy-seo-counter[data-field="' + field + '"] span');
                    var length = $field.val().length;
                    var min = field === 'title' ? 50 : 150;
                    var max = field === 'title' ? 60 : 160;

                    var status = 'bad';
                    if (length >= min && length <= max) {
                        status = 'good';
                    } else if (length > 0 && length < max + 10) {
                        status = 'warning';
                    }

                    $counter.attr('class', status);
                    $counter.html(length + ' characters. Recommended: ' + min + '-' + max + ' characters.');
                }

                // Update preview in real-time
                function updatePreview() {
                    var title = $('#nerdy_seo_title').val() || $('input[name="post_title"]').val();
                    var description = $('#nerdy_seo_description').val();

                    $('#preview-title').text(title);
                    if (description) {
                        $('#preview-description').text(description);
                    }
                }

                $('#nerdy_seo_title, #nerdy_seo_description').on('input', function() {
                    var field = $(this).attr('id').replace('nerdy_seo_', '');
                    updateCounter(field);
                    updatePreview();
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Get length status class
     */
    private function get_length_status($length, $min, $max) {
        if ($length >= $min && $length <= $max) {
            return 'good';
        } elseif ($length > 0 && $length < $max + 10) {
            return 'warning';
        }
        return 'bad';
    }

    /**
     * Save meta box data
     */
    public function save_meta_box($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['nerdy_seo_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['nerdy_seo_meta_box_nonce'], 'nerdy_seo_meta_box')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save meta fields
        $fields = array(
            'nerdy_seo_title' => '_nerdy_seo_title',
            'nerdy_seo_description' => '_nerdy_seo_description',
            'nerdy_seo_focus_keyword' => '_nerdy_seo_focus_keyword',
            'nerdy_seo_canonical' => '_nerdy_seo_canonical',
        );

        foreach ($fields as $field_name => $meta_key) {
            if (isset($_POST[$field_name])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field_name]));
            }
        }

        // Save checkboxes
        update_post_meta($post_id, '_nerdy_seo_noindex', isset($_POST['nerdy_seo_noindex']) ? '1' : '0');
        update_post_meta($post_id, '_nerdy_seo_nofollow', isset($_POST['nerdy_seo_nofollow']) ? '1' : '0');
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        global $post_type;

        // Only load on post edit screens
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        // Check if post type is enabled
        if (!in_array($post_type, $this->get_enabled_post_types())) {
            return;
        }

        wp_enqueue_script('jquery');
    }
}

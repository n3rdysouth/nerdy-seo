<?php
/**
 * Social Media Meta Tags
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Social Meta class
 */
class Nerdy_SEO_Social_Meta {

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
        add_action('wp_head', array($this, 'output_open_graph'), 5);
        add_action('wp_head', array($this, 'output_twitter_card'), 5);
        add_action('add_meta_boxes', array($this, 'add_social_meta_box'));
        add_action('save_post', array($this, 'save_social_meta_box'), 10, 2);
    }

    /**
     * Add social meta box
     */
    public function add_social_meta_box() {
        $post_types = apply_filters('nerdy_seo_post_types', get_post_types(array('public' => true), 'names'));

        foreach ($post_types as $post_type) {
            add_meta_box(
                'nerdy_seo_social_meta_box',
                __('Social Media Preview', 'nerdy-seo'),
                array($this, 'render_social_meta_box'),
                $post_type,
                'normal',
                'default'
            );
        }
    }

    /**
     * Render social meta box
     */
    public function render_social_meta_box($post) {
        wp_nonce_field('nerdy_seo_social_meta_box', 'nerdy_seo_social_meta_box_nonce');

        $og_title = get_post_meta($post->ID, '_nerdy_seo_og_title', true);
        $og_description = get_post_meta($post->ID, '_nerdy_seo_og_description', true);
        $og_image = get_post_meta($post->ID, '_nerdy_seo_og_image', true);
        $twitter_title = get_post_meta($post->ID, '_nerdy_seo_twitter_title', true);
        $twitter_description = get_post_meta($post->ID, '_nerdy_seo_twitter_description', true);
        $twitter_image = get_post_meta($post->ID, '_nerdy_seo_twitter_image', true);

        ?>
        <style>
            .nerdy-seo-social-tabs {
                margin-bottom: 20px;
                border-bottom: 1px solid #ddd;
            }
            .nerdy-seo-social-tabs button {
                background: none;
                border: none;
                padding: 10px 20px;
                cursor: pointer;
                border-bottom: 2px solid transparent;
                font-size: 14px;
            }
            .nerdy-seo-social-tabs button.active {
                border-bottom-color: #2271b1;
                color: #2271b1;
                font-weight: 600;
            }
            .nerdy-seo-social-tab-content {
                display: none;
            }
            .nerdy-seo-social-tab-content.active {
                display: block;
            }
            .nerdy-seo-social-field {
                margin-bottom: 15px;
            }
            .nerdy-seo-social-field label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
                font-size: 13px;
            }
            .nerdy-seo-social-field input[type="text"],
            .nerdy-seo-social-field textarea {
                width: 100%;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 3px;
            }
            .nerdy-seo-social-field textarea {
                min-height: 60px;
            }
            .nerdy-seo-image-upload {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .nerdy-seo-image-preview {
                max-width: 200px;
                max-height: 200px;
                border: 1px solid #ddd;
                padding: 5px;
                display: none;
            }
            .nerdy-seo-image-preview.has-image {
                display: block;
            }
            .nerdy-seo-social-hint {
                font-size: 12px;
                color: #666;
                font-style: italic;
                margin-top: 5px;
            }
        </style>

        <div class="nerdy-seo-social-tabs">
            <button type="button" class="nerdy-seo-tab-btn active" data-tab="facebook">
                <?php _e('Facebook / Open Graph', 'nerdy-seo'); ?>
            </button>
            <button type="button" class="nerdy-seo-tab-btn" data-tab="twitter">
                <?php _e('Twitter', 'nerdy-seo'); ?>
            </button>
        </div>

        <div id="tab-facebook" class="nerdy-seo-social-tab-content active">
            <div class="nerdy-seo-social-field">
                <label for="nerdy_seo_og_title">
                    <?php _e('Facebook Title', 'nerdy-seo'); ?>
                </label>
                <input
                    type="text"
                    id="nerdy_seo_og_title"
                    name="nerdy_seo_og_title"
                    value="<?php echo esc_attr($og_title); ?>"
                    placeholder="<?php echo esc_attr($post->post_title); ?>"
                />
                <p class="nerdy-seo-social-hint">
                    <?php _e('Leave blank to use SEO title or post title', 'nerdy-seo'); ?>
                </p>
            </div>

            <div class="nerdy-seo-social-field">
                <label for="nerdy_seo_og_description">
                    <?php _e('Facebook Description', 'nerdy-seo'); ?>
                </label>
                <textarea
                    id="nerdy_seo_og_description"
                    name="nerdy_seo_og_description"
                    placeholder="<?php _e('Brief description for Facebook sharing...', 'nerdy-seo'); ?>"
                ><?php echo esc_textarea($og_description); ?></textarea>
                <p class="nerdy-seo-social-hint">
                    <?php _e('Leave blank to use meta description', 'nerdy-seo'); ?>
                </p>
            </div>

            <div class="nerdy-seo-social-field">
                <label for="nerdy_seo_og_image">
                    <?php _e('Facebook Image', 'nerdy-seo'); ?>
                </label>
                <div class="nerdy-seo-image-upload">
                    <input
                        type="text"
                        id="nerdy_seo_og_image"
                        name="nerdy_seo_og_image"
                        value="<?php echo esc_url($og_image); ?>"
                        placeholder="<?php _e('Image URL', 'nerdy-seo'); ?>"
                    />
                    <button type="button" class="button nerdy-seo-upload-image" data-target="nerdy_seo_og_image">
                        <?php _e('Upload Image', 'nerdy-seo'); ?>
                    </button>
                </div>
                <?php if ($og_image): ?>
                    <img src="<?php echo esc_url($og_image); ?>" class="nerdy-seo-image-preview has-image" id="preview-nerdy_seo_og_image" />
                <?php else: ?>
                    <img src="" class="nerdy-seo-image-preview" id="preview-nerdy_seo_og_image" />
                <?php endif; ?>
                <p class="nerdy-seo-social-hint">
                    <?php _e('Recommended: 1200x630px. Leave blank to use featured image.', 'nerdy-seo'); ?>
                </p>
            </div>
        </div>

        <div id="tab-twitter" class="nerdy-seo-social-tab-content">
            <div class="nerdy-seo-social-field">
                <label for="nerdy_seo_twitter_title">
                    <?php _e('Twitter Title', 'nerdy-seo'); ?>
                </label>
                <input
                    type="text"
                    id="nerdy_seo_twitter_title"
                    name="nerdy_seo_twitter_title"
                    value="<?php echo esc_attr($twitter_title); ?>"
                    placeholder="<?php echo esc_attr($post->post_title); ?>"
                />
                <p class="nerdy-seo-social-hint">
                    <?php _e('Leave blank to use Facebook/OG title', 'nerdy-seo'); ?>
                </p>
            </div>

            <div class="nerdy-seo-social-field">
                <label for="nerdy_seo_twitter_description">
                    <?php _e('Twitter Description', 'nerdy-seo'); ?>
                </label>
                <textarea
                    id="nerdy_seo_twitter_description"
                    name="nerdy_seo_twitter_description"
                    placeholder="<?php _e('Brief description for Twitter sharing...', 'nerdy-seo'); ?>"
                ><?php echo esc_textarea($twitter_description); ?></textarea>
                <p class="nerdy-seo-social-hint">
                    <?php _e('Leave blank to use Facebook/OG description', 'nerdy-seo'); ?>
                </p>
            </div>

            <div class="nerdy-seo-social-field">
                <label for="nerdy_seo_twitter_image">
                    <?php _e('Twitter Image', 'nerdy-seo'); ?>
                </label>
                <div class="nerdy-seo-image-upload">
                    <input
                        type="text"
                        id="nerdy_seo_twitter_image"
                        name="nerdy_seo_twitter_image"
                        value="<?php echo esc_url($twitter_image); ?>"
                        placeholder="<?php _e('Image URL', 'nerdy-seo'); ?>"/>
                    <button type="button" class="button nerdy-seo-upload-image" data-target="nerdy_seo_twitter_image">
                        <?php _e('Upload Image', 'nerdy-seo'); ?>
                    </button>
                </div>
                <?php if ($twitter_image): ?>
                    <img src="<?php echo esc_url($twitter_image); ?>" class="nerdy-seo-image-preview has-image" id="preview-nerdy_seo_twitter_image" />
                <?php else: ?>
                    <img src="" class="nerdy-seo-image-preview" id="preview-nerdy_seo_twitter_image" />
                <?php endif; ?>
                <p class="nerdy-seo-social-hint">
                    <?php _e('Recommended: 1200x675px. Leave blank to use Facebook/OG image.', 'nerdy-seo'); ?>
                </p>
            </div>
        </div>

        <script>
        (function($) {
            $(document).ready(function() {
                // Tab switching
                $('.nerdy-seo-tab-btn').on('click', function() {
                    var tab = $(this).data('tab');

                    $('.nerdy-seo-tab-btn').removeClass('active');
                    $(this).addClass('active');

                    $('.nerdy-seo-social-tab-content').removeClass('active');
                    $('#tab-' + tab).addClass('active');
                });

                // Image upload
                var mediaUploader;

                $('.nerdy-seo-upload-image').on('click', function(e) {
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
                        $('#preview-' + targetId).attr('src', attachment.url).addClass('has-image');
                    });

                    mediaUploader.open();
                });

                // Preview images on input change
                $('input[name="nerdy_seo_og_image"], input[name="nerdy_seo_twitter_image"]').on('change', function() {
                    var url = $(this).val();
                    var previewId = 'preview-' + $(this).attr('id');

                    if (url) {
                        $('#' + previewId).attr('src', url).addClass('has-image');
                    } else {
                        $('#' + previewId).attr('src', '').removeClass('has-image');
                    }
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Save social meta box
     */
    public function save_social_meta_box($post_id, $post) {
        if (!isset($_POST['nerdy_seo_social_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['nerdy_seo_social_meta_box_nonce'], 'nerdy_seo_social_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        $fields = array(
            'nerdy_seo_og_title' => '_nerdy_seo_og_title',
            'nerdy_seo_og_description' => '_nerdy_seo_og_description',
            'nerdy_seo_og_image' => '_nerdy_seo_og_image',
            'nerdy_seo_twitter_title' => '_nerdy_seo_twitter_title',
            'nerdy_seo_twitter_description' => '_nerdy_seo_twitter_description',
            'nerdy_seo_twitter_image' => '_nerdy_seo_twitter_image',
        );

        foreach ($fields as $field_name => $meta_key) {
            if (isset($_POST[$field_name])) {
                if (strpos($field_name, 'image') !== false) {
                    update_post_meta($post_id, $meta_key, esc_url_raw($_POST[$field_name]));
                } else {
                    update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field_name]));
                }
            }
        }
    }

    /**
     * Output Open Graph tags
     */
    public function output_open_graph() {
        if (!get_option('nerdy_seo_og_enabled', true)) {
            return;
        }

        echo "<!-- Open Graph / Facebook -->\n";

        // Type
        $og_type = is_front_page() ? 'website' : 'article';
        echo '<meta property="og:type" content="' . esc_attr($og_type) . '">' . "\n";

        // Site name
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '">' . "\n";

        // URL
        $og_url = $this->get_current_url();
        echo '<meta property="og:url" content="' . esc_url($og_url) . '">' . "\n";

        // Title
        $og_title = $this->get_og_title();
        if ($og_title) {
            echo '<meta property="og:title" content="' . esc_attr($og_title) . '">' . "\n";
        }

        // Description
        $og_description = $this->get_og_description();
        if ($og_description) {
            echo '<meta property="og:description" content="' . esc_attr($og_description) . '">' . "\n";
        }

        // Image
        $og_image = $this->get_og_image();
        if ($og_image) {
            echo '<meta property="og:image" content="' . esc_url($og_image) . '">' . "\n";

            // Image dimensions
            $image_id = attachment_url_to_postid($og_image);
            if ($image_id) {
                $image_meta = wp_get_attachment_metadata($image_id);
                if (!empty($image_meta['width'])) {
                    echo '<meta property="og:image:width" content="' . esc_attr($image_meta['width']) . '">' . "\n";
                }
                if (!empty($image_meta['height'])) {
                    echo '<meta property="og:image:height" content="' . esc_attr($image_meta['height']) . '">' . "\n";
                }
            }
        }

        // Locale
        echo '<meta property="og:locale" content="' . esc_attr(get_locale()) . '">' . "\n";

        // Article specific tags
        if (is_singular('post')) {
            global $post;

            echo '<meta property="article:published_time" content="' . esc_attr(get_the_date('c', $post->ID)) . '">' . "\n";
            echo '<meta property="article:modified_time" content="' . esc_attr(get_the_modified_date('c', $post->ID)) . '">' . "\n";

            $author_id = $post->post_author;
            echo '<meta property="article:author" content="' . esc_attr(get_the_author_meta('display_name', $author_id)) . '">' . "\n";
        }

        echo "<!-- / Open Graph -->\n\n";
    }

    /**
     * Output Twitter Card tags
     */
    public function output_twitter_card() {
        if (!get_option('nerdy_seo_twitter_enabled', true)) {
            return;
        }

        echo "<!-- Twitter Card -->\n";

        // Card type
        echo '<meta name="twitter:card" content="summary_large_image">' . "\n";

        // Site handle
        $twitter_site = get_option('nerdy_seo_twitter_site', '');
        if ($twitter_site) {
            echo '<meta name="twitter:site" content="' . esc_attr($twitter_site) . '">' . "\n";
        }

        // Title
        $twitter_title = $this->get_twitter_title();
        if ($twitter_title) {
            echo '<meta name="twitter:title" content="' . esc_attr($twitter_title) . '">' . "\n";
        }

        // Description
        $twitter_description = $this->get_twitter_description();
        if ($twitter_description) {
            echo '<meta name="twitter:description" content="' . esc_attr($twitter_description) . '">' . "\n";
        }

        // Image
        $twitter_image = $this->get_twitter_image();
        if ($twitter_image) {
            echo '<meta name="twitter:image" content="' . esc_url($twitter_image) . '">' . "\n";
        }

        echo "<!-- / Twitter Card -->\n\n";
    }

    /**
     * Get Open Graph title
     */
    private function get_og_title() {
        if (is_singular()) {
            global $post;
            $og_title = get_post_meta($post->ID, '_nerdy_seo_og_title', true);

            if ($og_title) {
                return $this->replace_variables($og_title, $post);
            }

            // Fall back to SEO title
            $seo_title = get_post_meta($post->ID, '_nerdy_seo_title', true);
            if ($seo_title) {
                return $this->replace_variables($seo_title, $post);
            }

            return get_the_title($post->ID);
        } elseif (is_front_page()) {
            $home_title = get_option('nerdy_seo_home_title', get_bloginfo('name'));
            return $this->replace_variables($home_title);
        }

        return get_bloginfo('name');
    }

    /**
     * Get Open Graph description
     */
    private function get_og_description() {
        if (is_singular()) {
            global $post;
            $og_description = get_post_meta($post->ID, '_nerdy_seo_og_description', true);

            if ($og_description) {
                return $this->replace_variables($og_description, $post);
            }

            // Fall back to meta description
            $meta_description = get_post_meta($post->ID, '_nerdy_seo_description', true);
            if ($meta_description) {
                return $this->replace_variables($meta_description, $post);
            }

            // Fall back to excerpt or content
            if ($post->post_excerpt) {
                return wp_trim_words($post->post_excerpt, 20);
            }

            return wp_trim_words(strip_shortcodes($post->post_content), 20);
        } elseif (is_front_page()) {
            $home_description = get_option('nerdy_seo_home_description', get_bloginfo('description'));
            return $this->replace_variables($home_description);
        }

        return get_bloginfo('description');
    }

    /**
     * Get Open Graph image
     */
    private function get_og_image() {
        if (is_singular()) {
            global $post;
            $og_image = get_post_meta($post->ID, '_nerdy_seo_og_image', true);

            if ($og_image) {
                return $og_image;
            }

            // Fall back to featured image
            if (has_post_thumbnail($post->ID)) {
                return get_the_post_thumbnail_url($post->ID, 'full');
            }

            // Fall back to first image in content
            $first_image = $this->get_first_image($post->post_content);
            if ($first_image) {
                return $first_image;
            }
        }

        // Fall back to default image
        $default_image = get_option('nerdy_seo_default_og_image', '');
        if ($default_image) {
            return $default_image;
        }

        return '';
    }

    /**
     * Get Twitter title
     */
    private function get_twitter_title() {
        if (is_singular()) {
            global $post;
            $twitter_title = get_post_meta($post->ID, '_nerdy_seo_twitter_title', true);

            if ($twitter_title) {
                return $twitter_title;
            }
        }

        // Fall back to OG title
        return $this->get_og_title();
    }

    /**
     * Get Twitter description
     */
    private function get_twitter_description() {
        if (is_singular()) {
            global $post;
            $twitter_description = get_post_meta($post->ID, '_nerdy_seo_twitter_description', true);

            if ($twitter_description) {
                return $twitter_description;
            }
        }

        // Fall back to OG description
        return $this->get_og_description();
    }

    /**
     * Get Twitter image
     */
    private function get_twitter_image() {
        if (is_singular()) {
            global $post;
            $twitter_image = get_post_meta($post->ID, '_nerdy_seo_twitter_image', true);

            if ($twitter_image) {
                return $twitter_image;
            }
        }

        // Fall back to OG image
        return $this->get_og_image();
    }

    /**
     * Get current URL
     */
    private function get_current_url() {
        global $wp;
        return home_url(add_query_arg(array(), $wp->request));
    }

    /**
     * Get first image from content
     */
    private function get_first_image($content) {
        preg_match('/<img.+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $matches);
        return !empty($matches[1]) ? $matches[1] : '';
    }

    /**
     * Replace variables in text
     */
    private function replace_variables($text, $post = null) {
        if (empty($text)) {
            return $text;
        }

        if (!$post) {
            global $post;
        }

        // Get separator
        $separator = get_option('nerdy_seo_separator', '|');

        // Get categories and tags
        $categories = '';
        $tags = '';
        if ($post) {
            $post_categories = get_the_category($post->ID);
            if (!empty($post_categories)) {
                $categories = implode(', ', wp_list_pluck($post_categories, 'name'));
            }

            $post_tags = get_the_tags($post->ID);
            if (!empty($post_tags)) {
                $tags = implode(', ', wp_list_pluck($post_tags, 'name'));
            }
        }

        $replacements = array(
            '%title%' => $post ? get_the_title($post->ID) : '',
            '%sitename%' => get_bloginfo('name'),
            '%sitedesc%' => get_bloginfo('description'),
            '%separator%' => $separator,
            '%excerpt%' => $post && $post->post_excerpt ? $post->post_excerpt : '',
            '%author%' => $post ? get_the_author_meta('display_name', $post->post_author) : '',
            '%date%' => $post ? get_the_date('', $post->ID) : '',
            '%year%' => date('Y'),
            '%month%' => date('F'),
            '%day%' => date('j'),
            '%categories%' => $categories,
            '%tags%' => $tags,
        );

        // Allow filtering
        $replacements = apply_filters('nerdy_seo_variables', $replacements, $post);

        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
}

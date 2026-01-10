<?php
/**
 * Schema Markup functionality
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Schema class
 */
class Nerdy_SEO_Schema {

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
        add_action('wp_head', array($this, 'output_schema'), 20);
        add_action('add_meta_boxes', array($this, 'add_schema_meta_box'));
        add_action('save_post', array($this, 'save_schema_meta_box'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Add schema meta box
     */
    public function add_schema_meta_box() {
        $post_types = apply_filters('nerdy_seo_post_types', get_post_types(array('public' => true), 'names'));

        foreach ($post_types as $post_type) {
            add_meta_box(
                'nerdy_seo_schema_meta_box',
                __('Schema Markup', 'nerdy-seo'),
                array($this, 'render_schema_meta_box'),
                $post_type,
                'normal',
                'default'
            );
        }
    }

    /**
     * Render schema meta box
     */
    public function render_schema_meta_box($post) {
        wp_nonce_field('nerdy_seo_schema_meta_box', 'nerdy_seo_schema_meta_box_nonce');

        $schema_type = get_post_meta($post->ID, '_nerdy_seo_schema_type', true);
        $schema_faqs = get_post_meta($post->ID, '_nerdy_seo_schema_faqs', true);
        $schema_review_item = get_post_meta($post->ID, '_nerdy_seo_schema_review_item', true);
        $schema_review_rating = get_post_meta($post->ID, '_nerdy_seo_schema_review_rating', true);
        $schema_review_author = get_post_meta($post->ID, '_nerdy_seo_schema_review_author', true);
        $schema_review_text = get_post_meta($post->ID, '_nerdy_seo_schema_review_text', true);

        ?>
        <style>
            .nerdy-seo-schema-field {
                margin-bottom: 20px;
            }
            .nerdy-seo-schema-field label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
                font-size: 13px;
            }
            .nerdy-seo-schema-field select,
            .nerdy-seo-schema-field input[type="text"],
            .nerdy-seo-schema-field input[type="number"],
            .nerdy-seo-schema-field textarea {
                width: 100%;
                max-width: 500px;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 3px;
            }
            .nerdy-seo-schema-conditional {
                display: none;
                padding: 15px;
                background: #f9f9f9;
                border-left: 3px solid #2271b1;
                margin-top: 10px;
            }
            .nerdy-seo-schema-conditional.active {
                display: block;
            }
            .nerdy-seo-faq-item {
                background: white;
                border: 1px solid #ddd;
                padding: 15px;
                margin-bottom: 10px;
                border-radius: 3px;
            }
            .nerdy-seo-faq-item input,
            .nerdy-seo-faq-item textarea {
                width: 100%;
                margin-bottom: 10px;
            }
            .nerdy-seo-faq-item textarea {
                min-height: 60px;
            }
            .nerdy-seo-faq-remove {
                color: #dc3232;
                text-decoration: none;
                font-size: 12px;
            }
            .nerdy-seo-faq-remove:hover {
                color: #a00;
            }
            .nerdy-seo-schema-hint {
                font-size: 12px;
                color: #666;
                font-style: italic;
                margin-top: 5px;
            }
        </style>

        <div class="nerdy-seo-schema-field">
            <label for="nerdy_seo_schema_type">
                <?php _e('Schema Type', 'nerdy-seo'); ?>
            </label>
            <select id="nerdy_seo_schema_type" name="nerdy_seo_schema_type">
                <option value=""><?php _e('Default (Auto)', 'nerdy-seo'); ?></option>
                <option value="Article" <?php selected($schema_type, 'Article'); ?>><?php _e('Article', 'nerdy-seo'); ?></option>
                <option value="WebPage" <?php selected($schema_type, 'WebPage'); ?>><?php _e('Web Page', 'nerdy-seo'); ?></option>
                <option value="FAQ" <?php selected($schema_type, 'FAQ'); ?>><?php _e('FAQ Page', 'nerdy-seo'); ?></option>
                <option value="Review" <?php selected($schema_type, 'Review'); ?>><?php _e('Review', 'nerdy-seo'); ?></option>
                <option value="Product" <?php selected($schema_type, 'Product'); ?>><?php _e('Product', 'nerdy-seo'); ?></option>
                <option value="Service" <?php selected($schema_type, 'Service'); ?>><?php _e('Service', 'nerdy-seo'); ?></option>
                <option value="LocalBusiness" <?php selected($schema_type, 'LocalBusiness'); ?>><?php _e('Local Business', 'nerdy-seo'); ?></option>
            </select>
            <p class="nerdy-seo-schema-hint">
                <?php _e('Default (Auto): Blog posts = Article schema, Pages = WebPage schema. Override by selecting a specific type.', 'nerdy-seo'); ?>
            </p>
        </div>

        <!-- FAQ Schema -->
        <div id="schema-faq" class="nerdy-seo-schema-conditional <?php echo $schema_type === 'FAQ' ? 'active' : ''; ?>">
            <h4><?php _e('FAQ Items', 'nerdy-seo'); ?></h4>
            <div id="nerdy-seo-faq-items">
                <?php
                if (is_array($schema_faqs) && !empty($schema_faqs)) {
                    foreach ($schema_faqs as $index => $faq) {
                        ?>
                        <div class="nerdy-seo-faq-item" data-index="<?php echo $index; ?>">
                            <input
                                type="text"
                                name="nerdy_seo_schema_faqs[<?php echo $index; ?>][question]"
                                placeholder="<?php _e('Question', 'nerdy-seo'); ?>"
                                value="<?php echo esc_attr($faq['question'] ?? ''); ?>"
                            />
                            <textarea
                                name="nerdy_seo_schema_faqs[<?php echo $index; ?>][answer]"
                                placeholder="<?php _e('Answer', 'nerdy-seo'); ?>"
                            ><?php echo esc_textarea($faq['answer'] ?? ''); ?></textarea>
                            <a href="#" class="nerdy-seo-faq-remove"><?php _e('Remove', 'nerdy-seo'); ?></a>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            <button type="button" class="button" id="nerdy-seo-add-faq">
                <?php _e('Add FAQ Item', 'nerdy-seo'); ?>
            </button>
        </div>

        <!-- Review Schema -->
        <div id="schema-review" class="nerdy-seo-schema-conditional <?php echo $schema_type === 'Review' ? 'active' : ''; ?>">
            <h4><?php _e('Review Details', 'nerdy-seo'); ?></h4>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_schema_review_item">
                    <?php _e('Item Being Reviewed', 'nerdy-seo'); ?>
                </label>
                <input
                    type="text"
                    id="nerdy_seo_schema_review_item"
                    name="nerdy_seo_schema_review_item"
                    value="<?php echo esc_attr($schema_review_item); ?>"
                    placeholder="<?php _e('e.g., Product Name, Service Name', 'nerdy-seo'); ?>"
                />
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_schema_review_rating">
                    <?php _e('Rating (1-5)', 'nerdy-seo'); ?>
                </label>
                <input
                    type="number"
                    id="nerdy_seo_schema_review_rating"
                    name="nerdy_seo_schema_review_rating"
                    value="<?php echo esc_attr($schema_review_rating); ?>"
                    min="1"
                    max="5"
                    step="0.1"
                />
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_schema_review_author">
                    <?php _e('Review Author', 'nerdy-seo'); ?>
                </label>
                <input
                    type="text"
                    id="nerdy_seo_schema_review_author"
                    name="nerdy_seo_schema_review_author"
                    value="<?php echo esc_attr($schema_review_author); ?>"
                    placeholder="<?php echo esc_attr(get_the_author_meta('display_name', $post->post_author)); ?>"
                />
                <p class="nerdy-seo-schema-hint">
                    <?php _e('Leave blank to use post author', 'nerdy-seo'); ?>
                </p>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_schema_review_text">
                    <?php _e('Review Text', 'nerdy-seo'); ?>
                </label>
                <textarea
                    id="nerdy_seo_schema_review_text"
                    name="nerdy_seo_schema_review_text"
                    rows="4"
                    placeholder="<?php _e('Brief summary of the review...', 'nerdy-seo'); ?>"
                ><?php echo esc_textarea($schema_review_text); ?></textarea>
            </div>
        </div>

        <script type="text/template" id="nerdy-seo-faq-template">
            <div class="nerdy-seo-faq-item" data-index="{{INDEX}}">
                <input
                    type="text"
                    name="nerdy_seo_schema_faqs[{{INDEX}}][question]"
                    placeholder="<?php _e('Question', 'nerdy-seo'); ?>"
                />
                <textarea
                    name="nerdy_seo_schema_faqs[{{INDEX}}][answer]"
                    placeholder="<?php _e('Answer', 'nerdy-seo'); ?>"
                ></textarea>
                <a href="#" class="nerdy-seo-faq-remove"><?php _e('Remove', 'nerdy-seo'); ?></a>
            </div>
        </script>

        <script>
        (function($) {
            $(document).ready(function() {
                // Schema type change
                $('#nerdy_seo_schema_type').on('change', function() {
                    var type = $(this).val();

                    $('.nerdy-seo-schema-conditional').removeClass('active');

                    if (type === 'FAQ') {
                        $('#schema-faq').addClass('active');
                    } else if (type === 'Review') {
                        $('#schema-review').addClass('active');
                    }
                });

                // Add FAQ item
                $('#nerdy-seo-add-faq').on('click', function(e) {
                    e.preventDefault();
                    var template = $('#nerdy-seo-faq-template').html();
                    var index = $('#nerdy-seo-faq-items .nerdy-seo-faq-item').length;
                    var html = template.replace(/{{INDEX}}/g, index);
                    $('#nerdy-seo-faq-items').append(html);
                });

                // Remove FAQ item
                $(document).on('click', '.nerdy-seo-faq-remove', function(e) {
                    e.preventDefault();
                    $(this).closest('.nerdy-seo-faq-item').remove();
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Save schema meta box
     */
    public function save_schema_meta_box($post_id, $post) {
        if (!isset($_POST['nerdy_seo_schema_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['nerdy_seo_schema_meta_box_nonce'], 'nerdy_seo_schema_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save schema type
        if (isset($_POST['nerdy_seo_schema_type'])) {
            update_post_meta($post_id, '_nerdy_seo_schema_type', sanitize_text_field($_POST['nerdy_seo_schema_type']));
        }

        // Save FAQ schema
        if (isset($_POST['nerdy_seo_schema_faqs']) && is_array($_POST['nerdy_seo_schema_faqs'])) {
            $faqs = array();
            foreach ($_POST['nerdy_seo_schema_faqs'] as $faq) {
                if (!empty($faq['question']) && !empty($faq['answer'])) {
                    $faqs[] = array(
                        'question' => sanitize_text_field($faq['question']),
                        'answer' => sanitize_textarea_field($faq['answer']),
                    );
                }
            }
            update_post_meta($post_id, '_nerdy_seo_schema_faqs', $faqs);
        } else {
            delete_post_meta($post_id, '_nerdy_seo_schema_faqs');
        }

        // Save review schema
        if (isset($_POST['nerdy_seo_schema_review_item'])) {
            update_post_meta($post_id, '_nerdy_seo_schema_review_item', sanitize_text_field($_POST['nerdy_seo_schema_review_item']));
        }

        if (isset($_POST['nerdy_seo_schema_review_rating'])) {
            $rating = floatval($_POST['nerdy_seo_schema_review_rating']);
            $rating = max(1, min(5, $rating)); // Clamp between 1 and 5
            update_post_meta($post_id, '_nerdy_seo_schema_review_rating', $rating);
        }

        if (isset($_POST['nerdy_seo_schema_review_author'])) {
            update_post_meta($post_id, '_nerdy_seo_schema_review_author', sanitize_text_field($_POST['nerdy_seo_schema_review_author']));
        }

        if (isset($_POST['nerdy_seo_schema_review_text'])) {
            update_post_meta($post_id, '_nerdy_seo_schema_review_text', sanitize_textarea_field($_POST['nerdy_seo_schema_review_text']));
        }
    }

    /**
     * Output schema markup
     */
    public function output_schema() {
        if (!get_option('nerdy_seo_schema_enabled', true)) {
            return;
        }

        $schema = $this->get_schema();

        if (empty($schema)) {
            return;
        }

        echo "<!-- Schema.org Markup -->\n";
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n" . '</script>' . "\n";
        echo "<!-- / Schema.org Markup -->\n\n";
    }

    /**
     * Get schema data
     */
    private function get_schema() {
        $schema_array = array();

        // Get page-specific schema
        $page_schema = null;

        if (is_front_page()) {
            $page_schema = $this->get_website_schema();
        } elseif (is_singular()) {
            global $post;
            $schema_type = get_post_meta($post->ID, '_nerdy_seo_schema_type', true);

            switch ($schema_type) {
                case 'FAQ':
                    $page_schema = $this->get_faq_schema($post);
                    break;
                case 'Review':
                    $page_schema = $this->get_review_schema($post);
                    break;
                case 'Article':
                    $page_schema = $this->get_article_schema($post);
                    break;
                case 'WebPage':
                    $page_schema = $this->get_webpage_schema($post);
                    break;
                default:
                    // Default schemas based on post type
                    if ($post->post_type === 'post') {
                        $page_schema = $this->get_article_schema($post);
                    } elseif ($post->post_type === 'page') {
                        $page_schema = $this->get_webpage_schema($post);
                    }
                    break;
            }
        }

        // Add page schema to array
        if (!empty($page_schema)) {
            $schema_array[] = $page_schema;
        }

        // Get global schema from settings
        $global_schema = get_option('nerdy_seo_global_schema', '');
        if (!empty($global_schema)) {
            $decoded_schema = json_decode($global_schema, true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($decoded_schema)) {
                // Add @context if not present
                if (!isset($decoded_schema['@context'])) {
                    $decoded_schema['@context'] = 'https://schema.org';
                }
                $schema_array[] = $decoded_schema;
            }
        }

        // If we have multiple schemas, wrap in @graph
        if (count($schema_array) > 1) {
            return array(
                '@context' => 'https://schema.org',
                '@graph' => $schema_array,
            );
        } elseif (count($schema_array) === 1) {
            return $schema_array[0];
        }

        return array();
    }

    /**
     * Get website schema
     */
    private function get_website_schema() {
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url' => home_url('/'),
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => home_url('/?s={search_term_string}'),
                'query-input' => 'required name=search_term_string',
            ),
        );
    }

    /**
     * Get FAQ schema
     */
    private function get_faq_schema($post) {
        $faqs = get_post_meta($post->ID, '_nerdy_seo_schema_faqs', true);

        if (empty($faqs) || !is_array($faqs)) {
            return array();
        }

        $main_entity = array();
        foreach ($faqs as $faq) {
            $main_entity[] = array(
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text' => $faq['answer'],
                ),
            );
        }

        return array(
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $main_entity,
        );
    }

    /**
     * Get review schema
     */
    private function get_review_schema($post) {
        $item_name = get_post_meta($post->ID, '_nerdy_seo_schema_review_item', true);
        $rating = get_post_meta($post->ID, '_nerdy_seo_schema_review_rating', true);
        $author = get_post_meta($post->ID, '_nerdy_seo_schema_review_author', true);
        $review_text = get_post_meta($post->ID, '_nerdy_seo_schema_review_text', true);

        if (empty($item_name) || empty($rating)) {
            return array();
        }

        if (empty($author)) {
            $author = get_the_author_meta('display_name', $post->post_author);
        }

        if (empty($review_text)) {
            $review_text = wp_trim_words(strip_shortcodes($post->post_content), 50);
        }

        return array(
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'itemReviewed' => array(
                '@type' => 'Thing',
                'name' => $item_name,
            ),
            'reviewRating' => array(
                '@type' => 'Rating',
                'ratingValue' => $rating,
                'bestRating' => '5',
                'worstRating' => '1',
            ),
            'author' => array(
                '@type' => 'Person',
                'name' => $author,
            ),
            'reviewBody' => $review_text,
            'datePublished' => get_the_date('c', $post->ID),
        );
    }

    /**
     * Get article schema
     */
    private function get_article_schema($post) {
        $image_url = '';

        if (has_post_thumbnail($post->ID)) {
            $image_url = get_the_post_thumbnail_url($post->ID, 'full');
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title($post->ID),
            'description' => get_post_meta($post->ID, '_nerdy_seo_description', true) ?: wp_trim_words(strip_shortcodes($post->post_content), 20),
            'datePublished' => get_the_date('c', $post->ID),
            'dateModified' => get_the_modified_date('c', $post->ID),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', $post->post_author),
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url(),
                ),
            ),
        );

        if ($image_url) {
            $schema['image'] = $image_url;
        }

        return $schema;
    }

    /**
     * Get webpage schema
     */
    private function get_webpage_schema($post) {
        $image_url = '';

        if (has_post_thumbnail($post->ID)) {
            $image_url = get_the_post_thumbnail_url($post->ID, 'full');
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => get_the_title($post->ID),
            'description' => get_post_meta($post->ID, '_nerdy_seo_description', true) ?: wp_trim_words(strip_shortcodes($post->post_content), 20),
            'url' => get_permalink($post->ID),
            'datePublished' => get_the_date('c', $post->ID),
            'dateModified' => get_the_modified_date('c', $post->ID),
        );

        if ($image_url) {
            $schema['image'] = $image_url;
        }

        // Add breadcrumb if available
        $schema['breadcrumb'] = array(
            '@type' => 'BreadcrumbList',
            'itemListElement' => array(
                array(
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => home_url('/'),
                ),
                array(
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => get_the_title($post->ID),
                    'item' => get_permalink($post->ID),
                ),
            ),
        );

        return $schema;
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        wp_enqueue_script('jquery');
    }
}

<?php
/**
 * Breadcrumbs functionality
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Breadcrumbs class
 */
class Nerdy_SEO_Breadcrumbs {

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
        // Shortcode
        add_shortcode('nerdy_breadcrumbs', array($this, 'breadcrumbs_shortcode'));

        // Output schema
        add_action('wp_head', array($this, 'output_breadcrumb_schema'), 30);

        // Enqueue styles
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));

        // Settings
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Enqueue breadcrumb styles
     */
    public function enqueue_styles() {
        if (!get_option('nerdy_seo_breadcrumb_enabled', true)) {
            return;
        }

        wp_enqueue_style(
            'nerdy-seo-breadcrumbs',
            NERDY_SEO_PLUGIN_URL . 'assets/css/breadcrumbs.css',
            array(),
            NERDY_SEO_VERSION
        );
    }

    /**
     * Generate breadcrumbs
     */
    public function generate_breadcrumbs($args = array()) {
        $defaults = array(
            'show_home' => true,
            'home_text' => __('Home', 'nerdy-seo'),
            'separator' => '›',
            'show_current' => true,
            'before' => '<nav class="nerdy-breadcrumbs" aria-label="Breadcrumb">',
            'after' => '</nav>',
            'wrap_before' => '<ol class="breadcrumb-list">',
            'wrap_after' => '</ol>',
            'item_before' => '<li class="breadcrumb-item">',
            'item_after' => '</li>',
            'link_before' => '',
            'link_after' => '',
        );

        $args = wp_parse_args($args, $defaults);

        // Get breadcrumb items
        $items = $this->get_breadcrumb_items($args);

        if (empty($items)) {
            return '';
        }

        // Build output
        $output = $args['before'];
        $output .= $args['wrap_before'];

        $position = 1;
        $total = count($items);

        foreach ($items as $item) {
            $is_last = ($position === $total);

            $output .= $args['item_before'];

            if (!empty($item['url']) && !$is_last) {
                $output .= $args['link_before'];
                $output .= sprintf(
                    '<a href="%s" itemprop="item"><span itemprop="name">%s</span></a>',
                    esc_url($item['url']),
                    esc_html($item['text'])
                );
                $output .= $args['link_after'];
            } else {
                $output .= '<span itemprop="name">' . esc_html($item['text']) . '</span>';
            }

            $output .= '<meta itemprop="position" content="' . $position . '" />';
            $output .= $args['item_after'];

            if (!$is_last && $args['separator']) {
                $output .= ' <span class="breadcrumb-separator">' . esc_html($args['separator']) . '</span> ';
            }

            $position++;
        }

        $output .= $args['wrap_after'];
        $output .= $args['after'];

        return apply_filters('nerdy_seo_breadcrumbs_output', $output, $items, $args);
    }

    /**
     * Get breadcrumb items
     */
    private function get_breadcrumb_items($args) {
        $items = array();

        // Home
        if ($args['show_home']) {
            $items[] = array(
                'text' => $args['home_text'],
                'url' => home_url('/'),
            );
        }

        // Don't show breadcrumbs on homepage
        if (is_front_page()) {
            return $args['show_home'] ? $items : array();
        }

        // Single post
        if (is_single()) {
            global $post;

            // Add categories for posts
            if ($post->post_type === 'post') {
                $categories = get_the_category($post->ID);
                if (!empty($categories)) {
                    $category = $categories[0];

                    // Add parent categories
                    if ($category->parent) {
                        $parents = get_category_parents($category->parent, true, '|||');
                        $parents = explode('|||', $parents);
                        foreach ($parents as $parent) {
                            if (empty($parent)) continue;
                            preg_match('/<a href="([^"]+)">([^<]+)<\/a>/', $parent, $matches);
                            if (!empty($matches[2])) {
                                $items[] = array(
                                    'text' => $matches[2],
                                    'url' => $matches[1],
                                );
                            }
                        }
                    }

                    $items[] = array(
                        'text' => $category->name,
                        'url' => get_category_link($category->term_id),
                    );
                }
            }

            // Add custom post type archive
            if ($post->post_type !== 'post' && $post->post_type !== 'page') {
                $post_type_object = get_post_type_object($post->post_type);
                if ($post_type_object && $post_type_object->has_archive) {
                    $items[] = array(
                        'text' => $post_type_object->labels->name,
                        'url' => get_post_type_archive_link($post->post_type),
                    );
                }
            }

            // Current post
            if ($args['show_current']) {
                $items[] = array(
                    'text' => get_the_title($post->ID),
                    'url' => '',
                );
            }
        }

        // Page
        elseif (is_page()) {
            global $post;

            // Add parent pages
            if ($post->post_parent) {
                $parents = array_reverse(get_post_ancestors($post->ID));
                foreach ($parents as $parent_id) {
                    $items[] = array(
                        'text' => get_the_title($parent_id),
                        'url' => get_permalink($parent_id),
                    );
                }
            }

            // Current page
            if ($args['show_current']) {
                $items[] = array(
                    'text' => get_the_title($post->ID),
                    'url' => '',
                );
            }
        }

        // Category
        elseif (is_category()) {
            $category = get_queried_object();

            // Add parent categories
            if ($category->parent) {
                $parents = get_category_parents($category->parent, true, '|||');
                $parents = explode('|||', $parents);
                foreach ($parents as $parent) {
                    if (empty($parent)) continue;
                    preg_match('/<a href="([^"]+)">([^<]+)<\/a>/', $parent, $matches);
                    if (!empty($matches[2])) {
                        $items[] = array(
                            'text' => $matches[2],
                            'url' => $matches[1],
                        );
                    }
                }
            }

            // Current category
            if ($args['show_current']) {
                $items[] = array(
                    'text' => $category->name,
                    'url' => '',
                );
            }
        }

        // Tag
        elseif (is_tag()) {
            $tag = get_queried_object();

            if ($args['show_current']) {
                $items[] = array(
                    'text' => sprintf(__('Tag: %s', 'nerdy-seo'), $tag->name),
                    'url' => '',
                );
            }
        }

        // Taxonomy
        elseif (is_tax()) {
            $term = get_queried_object();
            $taxonomy = get_taxonomy($term->taxonomy);

            // Add taxonomy name
            $items[] = array(
                'text' => $taxonomy->labels->name,
                'url' => '',
            );

            // Current term
            if ($args['show_current']) {
                $items[] = array(
                    'text' => $term->name,
                    'url' => '',
                );
            }
        }

        // Post type archive
        elseif (is_post_type_archive()) {
            $post_type = get_query_var('post_type');
            $post_type_object = get_post_type_object($post_type);

            if ($args['show_current'] && $post_type_object) {
                $items[] = array(
                    'text' => $post_type_object->labels->name,
                    'url' => '',
                );
            }
        }

        // Author
        elseif (is_author()) {
            $author = get_queried_object();

            if ($args['show_current']) {
                $items[] = array(
                    'text' => sprintf(__('Author: %s', 'nerdy-seo'), $author->display_name),
                    'url' => '',
                );
            }
        }

        // Date archive
        elseif (is_date()) {
            if (is_day()) {
                $items[] = array(
                    'text' => get_the_time('Y'),
                    'url' => get_year_link(get_the_time('Y')),
                );
                $items[] = array(
                    'text' => get_the_time('F'),
                    'url' => get_month_link(get_the_time('Y'), get_the_time('m')),
                );
                if ($args['show_current']) {
                    $items[] = array(
                        'text' => get_the_time('d'),
                        'url' => '',
                    );
                }
            } elseif (is_month()) {
                $items[] = array(
                    'text' => get_the_time('Y'),
                    'url' => get_year_link(get_the_time('Y')),
                );
                if ($args['show_current']) {
                    $items[] = array(
                        'text' => get_the_time('F'),
                        'url' => '',
                    );
                }
            } elseif (is_year()) {
                if ($args['show_current']) {
                    $items[] = array(
                        'text' => get_the_time('Y'),
                        'url' => '',
                    );
                }
            }
        }

        // Search
        elseif (is_search()) {
            if ($args['show_current']) {
                $items[] = array(
                    'text' => sprintf(__('Search Results for: %s', 'nerdy-seo'), get_search_query()),
                    'url' => '',
                );
            }
        }

        // 404
        elseif (is_404()) {
            if ($args['show_current']) {
                $items[] = array(
                    'text' => __('404 - Page Not Found', 'nerdy-seo'),
                    'url' => '',
                );
            }
        }

        return apply_filters('nerdy_seo_breadcrumb_items', $items);
    }

    /**
     * Breadcrumbs shortcode
     */
    public function breadcrumbs_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show_home' => true,
            'home_text' => __('Home', 'nerdy-seo'),
            'separator' => get_option('nerdy_seo_breadcrumb_separator', '›'),
            'show_current' => true,
        ), $atts);

        return $this->generate_breadcrumbs($atts);
    }

    /**
     * Output breadcrumb schema
     */
    public function output_breadcrumb_schema() {
        if (!get_option('nerdy_seo_breadcrumb_schema', true)) {
            return;
        }

        // Don't output on homepage
        if (is_front_page()) {
            return;
        }

        $items = $this->get_breadcrumb_items(array(
            'show_home' => true,
            'home_text' => __('Home', 'nerdy-seo'),
            'show_current' => true,
        ));

        if (empty($items)) {
            return;
        }

        $schema_items = array();
        $position = 1;

        foreach ($items as $item) {
            $schema_item = array(
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $item['text'],
            );

            if (!empty($item['url'])) {
                $schema_item['item'] = $item['url'];
            }

            $schema_items[] = $schema_item;
            $position++;
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $schema_items,
        );

        echo "<!-- Breadcrumb Schema -->\n";
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n" . '</script>' . "\n";
        echo "<!-- / Breadcrumb Schema -->\n\n";
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('nerdy_seo_settings', 'nerdy_seo_breadcrumb_enabled', array(
            'sanitize_callback' => 'absint',
            'default' => 1,
        ));
        register_setting('nerdy_seo_settings', 'nerdy_seo_breadcrumb_schema', array(
            'sanitize_callback' => 'absint',
            'default' => 1,
        ));
        register_setting('nerdy_seo_settings', 'nerdy_seo_breadcrumb_separator', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => '›',
        ));
        register_setting('nerdy_seo_settings', 'nerdy_seo_breadcrumb_home_text', array(
            'sanitize_callback' => 'sanitize_text_field',
            'default' => 'Home',
        ));

        // Breadcrumb section
        add_settings_section(
            'nerdy_seo_breadcrumb_section',
            __('Breadcrumb Settings', 'nerdy-seo'),
            array($this, 'render_breadcrumb_section'),
            'nerdy-seo'
        );

        add_settings_field(
            'nerdy_seo_breadcrumb_enabled',
            __('Enable Breadcrumbs', 'nerdy-seo'),
            array($this, 'render_breadcrumb_enabled_field'),
            'nerdy-seo',
            'nerdy_seo_breadcrumb_section'
        );

        add_settings_field(
            'nerdy_seo_breadcrumb_schema',
            __('Breadcrumb Schema', 'nerdy-seo'),
            array($this, 'render_breadcrumb_schema_field'),
            'nerdy-seo',
            'nerdy_seo_breadcrumb_section'
        );

        add_settings_field(
            'nerdy_seo_breadcrumb_separator',
            __('Separator', 'nerdy-seo'),
            array($this, 'render_breadcrumb_separator_field'),
            'nerdy-seo',
            'nerdy_seo_breadcrumb_section'
        );

        add_settings_field(
            'nerdy_seo_breadcrumb_home_text',
            __('Home Text', 'nerdy-seo'),
            array($this, 'render_breadcrumb_home_text_field'),
            'nerdy-seo',
            'nerdy_seo_breadcrumb_section'
        );
    }

    /**
     * Render breadcrumb section
     */
    public function render_breadcrumb_section() {
        echo '<p>' . __('Configure breadcrumb navigation for your site.', 'nerdy-seo') . '</p>';
        echo '<p><strong>' . __('Usage:', 'nerdy-seo') . '</strong></p>';
        echo '<p>' . __('Add breadcrumbs to your theme with:', 'nerdy-seo') . '</p>';
        echo '<code>if (function_exists(\'nerdy_seo_breadcrumbs\')) nerdy_seo_breadcrumbs();</code>';
        echo '<p>' . __('Or use shortcode:', 'nerdy-seo') . ' <code>[nerdy_breadcrumbs]</code></p>';
    }

    /**
     * Render breadcrumb enabled field
     */
    public function render_breadcrumb_enabled_field() {
        $enabled = get_option('nerdy_seo_breadcrumb_enabled', true);
        ?>
        <label>
            <input type="checkbox" name="nerdy_seo_breadcrumb_enabled" value="1" <?php checked($enabled, true); ?> />
            <?php esc_html_e('Enable breadcrumb functionality', 'nerdy-seo'); ?>
        </label>
        <?php
    }

    /**
     * Render breadcrumb schema field
     */
    public function render_breadcrumb_schema_field() {
        $enabled = get_option('nerdy_seo_breadcrumb_schema', true);
        ?>
        <label>
            <input type="checkbox" name="nerdy_seo_breadcrumb_schema" value="1" <?php checked($enabled, true); ?> />
            <?php esc_html_e('Output BreadcrumbList schema markup in page head', 'nerdy-seo'); ?>
        </label>
        <p class="description"><?php esc_html_e('Helps search engines understand your site structure', 'nerdy-seo'); ?></p>
        <?php
    }

    /**
     * Render breadcrumb separator field
     */
    public function render_breadcrumb_separator_field() {
        $separator = get_option('nerdy_seo_breadcrumb_separator', '›');
        ?>
        <input type="text" name="nerdy_seo_breadcrumb_separator" value="<?php echo esc_attr($separator); ?>" class="small-text" />
        <p class="description">
            <?php esc_html_e('Common options:', 'nerdy-seo'); ?>
            › / » &gt; &raquo; •
        </p>
        <?php
    }

    /**
     * Render breadcrumb home text field
     */
    public function render_breadcrumb_home_text_field() {
        $home_text = get_option('nerdy_seo_breadcrumb_home_text', __('Home', 'nerdy-seo'));
        ?>
        <input type="text" name="nerdy_seo_breadcrumb_home_text" value="<?php echo esc_attr($home_text); ?>" class="regular-text" />
        <p class="description"><?php esc_html_e('Text for the home link in breadcrumbs', 'nerdy-seo'); ?></p>
        <?php
    }
}

/**
 * Template function to display breadcrumbs
 */
function nerdy_seo_breadcrumbs($args = array()) {
    if (!get_option('nerdy_seo_breadcrumb_enabled', true)) {
        return;
    }

    $breadcrumbs = Nerdy_SEO_Breadcrumbs::get_instance();
    echo $breadcrumbs->generate_breadcrumbs($args);
}

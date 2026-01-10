<?php
/**
 * Frontend SEO output
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Frontend class
 */
class Nerdy_SEO_Frontend {

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
        // Remove default WordPress title tag filter
        add_action('after_setup_theme', array($this, 'setup_theme_support'));

        // Add custom title
        add_filter('pre_get_document_title', array($this, 'get_title'), 10);
        add_filter('document_title_separator', array($this, 'title_separator'));

        // Add meta tags
        add_action('wp_head', array($this, 'output_meta_tags'), 1);

        // Add robots meta tag
        add_filter('wp_robots', array($this, 'add_robots'), 10, 1);
    }

    /**
     * Setup theme support
     */
    public function setup_theme_support() {
        add_theme_support('title-tag');
    }

    /**
     * Get page title
     */
    public function get_title($title) {
        // Generate title based on page type
        if (is_front_page()) {
            $custom_title = get_option('nerdy_seo_home_title', get_bloginfo('name'));
            return $this->replace_variables($custom_title);
        } elseif (is_singular()) {
            global $post;
            $meta_title = get_post_meta($post->ID, '_nerdy_seo_title', true);

            if ($meta_title) {
                return $this->replace_variables($meta_title, $post);
            }

            return get_the_title($post->ID);
        } elseif (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            return $term->name;
        } elseif (is_author()) {
            $author = get_queried_object();
            return sprintf(__('Author: %s', 'nerdy-seo'), $author->display_name);
        } elseif (is_archive()) {
            return get_the_archive_title();
        } elseif (is_search()) {
            return sprintf(__('Search Results for: %s', 'nerdy-seo'), get_search_query());
        } elseif (is_404()) {
            return __('Page Not Found', 'nerdy-seo');
        }

        return $title;
    }

    /**
     * Title separator
     */
    public function title_separator($sep) {
        $separator = get_option('nerdy_seo_separator', '|');
        return apply_filters('nerdy_seo_title_separator', $separator);
    }

    /**
     * Output meta tags
     */
    public function output_meta_tags() {
        $description = $this->get_meta_description();
        $canonical = $this->get_canonical_url();

        // Meta description
        if ($description) {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }

        // Canonical URL
        if ($canonical) {
            echo '<link rel="canonical" href="' . esc_url($canonical) . '">' . "\n";
        }

        // Generator tag
        echo '<meta name="generator" content="Nerdy SEO ' . NERDY_SEO_VERSION . '">' . "\n";
    }

    /**
     * Get meta description
     */
    private function get_meta_description() {
        if (is_front_page()) {
            $description = get_option('nerdy_seo_home_description', get_bloginfo('description'));
            return $this->replace_variables($description);
        } elseif (is_singular()) {
            global $post;
            $meta_description = get_post_meta($post->ID, '_nerdy_seo_description', true);

            if ($meta_description) {
                return $this->replace_variables($meta_description, $post);
            }

            // Generate from excerpt or content
            if ($post->post_excerpt) {
                return wp_trim_words($post->post_excerpt, 20);
            } else {
                return wp_trim_words(strip_shortcodes($post->post_content), 20);
            }
        } elseif (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            if ($term->description) {
                return wp_trim_words($term->description, 20);
            }
        } elseif (is_author()) {
            $author = get_queried_object();
            return sprintf(__('All posts by %s', 'nerdy-seo'), $author->display_name);
        }

        return '';
    }

    /**
     * Get canonical URL
     */
    private function get_canonical_url() {
        if (is_singular()) {
            global $post;
            $custom_canonical = get_post_meta($post->ID, '_nerdy_seo_canonical', true);

            if ($custom_canonical) {
                return $custom_canonical;
            }

            return get_permalink($post->ID);
        } elseif (is_front_page()) {
            return home_url('/');
        } elseif (is_category() || is_tag() || is_tax()) {
            $term = get_queried_object();
            return get_term_link($term);
        } elseif (is_author()) {
            $author = get_queried_object();
            return get_author_posts_url($author->ID);
        } elseif (is_archive()) {
            global $wp;
            return home_url($wp->request);
        }

        return '';
    }

    /**
     * Add robots meta tag
     */
    public function add_robots($robots) {
        if (is_singular()) {
            global $post;
            $noindex = get_post_meta($post->ID, '_nerdy_seo_noindex', true);
            $nofollow = get_post_meta($post->ID, '_nerdy_seo_nofollow', true);

            if ($noindex === '1') {
                $robots['noindex'] = true;
                unset($robots['index']);
            }

            if ($nofollow === '1') {
                $robots['nofollow'] = true;
                unset($robots['follow']);
            }
        }

        return $robots;
    }

    /**
     * Replace variables in text
     */
    private function replace_variables($text, $post = null) {
        if (!$post) {
            global $post;
        }

        // Get separator
        $separator = $this->title_separator('|');

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

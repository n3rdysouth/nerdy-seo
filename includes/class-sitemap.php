<?php
/**
 * XML Sitemap functionality
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Sitemap class
 */
class Nerdy_SEO_Sitemap {

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
        // WordPress native sitemap filters
        add_filter('wp_sitemaps_enabled', array($this, 'enable_sitemaps'));
        add_filter('wp_sitemaps_max_urls', array($this, 'max_urls'));
        add_filter('wp_sitemaps_posts_entry', array($this, 'modify_post_entry'), 10, 3);
        add_filter('wp_sitemaps_add_provider', array($this, 'add_custom_providers'), 10, 2);
        add_filter('wp_sitemaps_post_types', array($this, 'filter_post_types'));
        add_filter('wp_sitemaps_taxonomies', array($this, 'filter_taxonomies'));

        // Add custom sitemap types
        add_action('init', array($this, 'register_custom_sitemaps'));

        // Add meta box for per-post sitemap settings
        add_action('add_meta_boxes', array($this, 'add_sitemap_meta_box'));
        add_action('save_post', array($this, 'save_sitemap_meta_box'), 10, 2);

        // Add sitemap settings to admin
        add_action('admin_init', array($this, 'register_sitemap_settings'));

        // Flush rewrite rules on settings change
        add_action('update_option_nerdy_seo_sitemap_enabled', array($this, 'flush_rewrites'));
    }

    /**
     * Enable/disable sitemaps
     */
    public function enable_sitemaps($enabled) {
        return get_option('nerdy_seo_sitemap_enabled', true);
    }

    /**
     * Set max URLs per sitemap
     */
    public function max_urls($max_urls) {
        return get_option('nerdy_seo_sitemap_max_urls', 2000);
    }

    /**
     * Modify post entry in sitemap
     */
    public function modify_post_entry($entry, $post, $post_type) {
        // Check if post should be excluded
        $exclude = get_post_meta($post->ID, '_nerdy_seo_sitemap_exclude', true);
        if ($exclude === '1') {
            return null; // Remove from sitemap
        }

        // Get custom priority
        $priority = get_post_meta($post->ID, '_nerdy_seo_sitemap_priority', true);
        if ($priority) {
            $entry['priority'] = floatval($priority);
        } else {
            // Default priorities based on post type and date
            $entry['priority'] = $this->calculate_priority($post);
        }

        // Get custom change frequency
        $changefreq = get_post_meta($post->ID, '_nerdy_seo_sitemap_changefreq', true);
        if ($changefreq) {
            $entry['changefreq'] = $changefreq;
        } else {
            $entry['changefreq'] = $this->calculate_changefreq($post);
        }

        return $entry;
    }

    /**
     * Calculate priority based on post
     */
    private function calculate_priority($post) {
        // Homepage gets highest priority
        if ($post->ID === (int) get_option('page_on_front')) {
            return 1.0;
        }

        // Recent posts get higher priority
        $post_age_days = (time() - strtotime($post->post_date)) / DAY_IN_SECONDS;

        if ($post_age_days < 30) {
            return 0.8;
        } elseif ($post_age_days < 90) {
            return 0.6;
        } elseif ($post_age_days < 365) {
            return 0.5;
        } else {
            return 0.4;
        }
    }

    /**
     * Calculate change frequency based on post
     */
    private function calculate_changefreq($post) {
        $post_age_days = (time() - strtotime($post->post_modified)) / DAY_IN_SECONDS;

        if ($post_age_days < 7) {
            return 'daily';
        } elseif ($post_age_days < 30) {
            return 'weekly';
        } elseif ($post_age_days < 180) {
            return 'monthly';
        } else {
            return 'yearly';
        }
    }

    /**
     * Filter post types in sitemap
     */
    public function filter_post_types($post_types) {
        $excluded_types = get_option('nerdy_seo_sitemap_exclude_post_types', array());

        if (!empty($excluded_types)) {
            foreach ($excluded_types as $type) {
                unset($post_types[$type]);
            }
        }

        return $post_types;
    }

    /**
     * Filter taxonomies in sitemap
     */
    public function filter_taxonomies($taxonomies) {
        $excluded_taxonomies = get_option('nerdy_seo_sitemap_exclude_taxonomies', array());

        if (!empty($excluded_taxonomies)) {
            foreach ($excluded_taxonomies as $taxonomy) {
                unset($taxonomies[$taxonomy]);
            }
        }

        return $taxonomies;
    }

    /**
     * Register custom sitemap types
     */
    public function register_custom_sitemaps() {
        // Image sitemap
        if (get_option('nerdy_seo_image_sitemap_enabled', false)) {
            add_action('init', array($this, 'add_image_sitemap_rewrite'));
            add_action('template_redirect', array($this, 'serve_image_sitemap'));
        }

        // Video sitemap
        if (get_option('nerdy_seo_video_sitemap_enabled', false)) {
            add_action('init', array($this, 'add_video_sitemap_rewrite'));
            add_action('template_redirect', array($this, 'serve_video_sitemap'));
        }

        // News sitemap
        if (get_option('nerdy_seo_news_sitemap_enabled', false)) {
            add_action('init', array($this, 'add_news_sitemap_rewrite'));
            add_action('template_redirect', array($this, 'serve_news_sitemap'));
        }
    }

    /**
     * Add image sitemap rewrite
     */
    public function add_image_sitemap_rewrite() {
        add_rewrite_rule('^image-sitemap\.xml$', 'index.php?nerdy_seo_sitemap=image', 'top');
        add_rewrite_tag('%nerdy_seo_sitemap%', '([^&]+)');
    }

    /**
     * Add video sitemap rewrite
     */
    public function add_video_sitemap_rewrite() {
        add_rewrite_rule('^video-sitemap\.xml$', 'index.php?nerdy_seo_sitemap=video', 'top');
        add_rewrite_tag('%nerdy_seo_sitemap%', '([^&]+)');
    }

    /**
     * Add news sitemap rewrite
     */
    public function add_news_sitemap_rewrite() {
        add_rewrite_rule('^news-sitemap\.xml$', 'index.php?nerdy_seo_sitemap=news', 'top');
        add_rewrite_tag('%nerdy_seo_sitemap%', '([^&]+)');
    }

    /**
     * Serve image sitemap
     */
    public function serve_image_sitemap() {
        $sitemap_type = get_query_var('nerdy_seo_sitemap');

        if ($sitemap_type !== 'image') {
            return;
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";

        $posts = get_posts(array(
            'post_type' => 'any',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_nerdy_seo_sitemap_exclude',
                    'compare' => 'NOT EXISTS',
                ),
            ),
        ));

        foreach ($posts as $post) {
            $images = $this->get_post_images($post);

            if (empty($images)) {
                continue;
            }

            echo "\t<url>\n";
            echo "\t\t<loc>" . esc_url(get_permalink($post->ID)) . "</loc>\n";

            foreach ($images as $image) {
                echo "\t\t<image:image>\n";
                echo "\t\t\t<image:loc>" . esc_url($image['url']) . "</image:loc>\n";

                if (!empty($image['title'])) {
                    echo "\t\t\t<image:title><![CDATA[" . $image['title'] . "]]></image:title>\n";
                }

                if (!empty($image['caption'])) {
                    echo "\t\t\t<image:caption><![CDATA[" . $image['caption'] . "]]></image:caption>\n";
                }

                echo "\t\t</image:image>\n";
            }

            echo "\t</url>\n";
        }

        echo '</urlset>';
        exit;
    }

    /**
     * Serve video sitemap
     */
    public function serve_video_sitemap() {
        $sitemap_type = get_query_var('nerdy_seo_sitemap');

        if ($sitemap_type !== 'video') {
            return;
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";

        $posts = get_posts(array(
            'post_type' => 'any',
            'post_status' => 'publish',
            'posts_per_page' => -1,
        ));

        foreach ($posts as $post) {
            $videos = $this->get_post_videos($post);

            if (empty($videos)) {
                continue;
            }

            echo "\t<url>\n";
            echo "\t\t<loc>" . esc_url(get_permalink($post->ID)) . "</loc>\n";

            foreach ($videos as $video) {
                echo "\t\t<video:video>\n";
                echo "\t\t\t<video:content_loc>" . esc_url($video['url']) . "</video:content_loc>\n";
                echo "\t\t\t<video:title><![CDATA[" . ($video['title'] ?: get_the_title($post->ID)) . "]]></video:title>\n";
                echo "\t\t\t<video:description><![CDATA[" . ($video['description'] ?: wp_trim_words($post->post_content, 50)) . "]]></video:description>\n";

                if (!empty($video['thumbnail'])) {
                    echo "\t\t\t<video:thumbnail_loc>" . esc_url($video['thumbnail']) . "</video:thumbnail_loc>\n";
                }

                echo "\t\t</video:video>\n";
            }

            echo "\t</url>\n";
        }

        echo '</urlset>';
        exit;
    }

    /**
     * Serve news sitemap
     */
    public function serve_news_sitemap() {
        $sitemap_type = get_query_var('nerdy_seo_sitemap');

        if ($sitemap_type !== 'news') {
            return;
        }

        header('Content-Type: application/xml; charset=utf-8');
        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";

        // News sitemap only includes posts from last 2 days
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => 1000,
            'date_query' => array(
                array(
                    'after' => '2 days ago',
                ),
            ),
        ));

        foreach ($posts as $post) {
            echo "\t<url>\n";
            echo "\t\t<loc>" . esc_url(get_permalink($post->ID)) . "</loc>\n";
            echo "\t\t<news:news>\n";
            echo "\t\t\t<news:publication>\n";
            echo "\t\t\t\t<news:name>" . esc_html(get_bloginfo('name')) . "</news:name>\n";
            echo "\t\t\t\t<news:language>en</news:language>\n";
            echo "\t\t\t</news:publication>\n";
            echo "\t\t\t<news:publication_date>" . get_the_date('c', $post->ID) . "</news:publication_date>\n";
            echo "\t\t\t<news:title><![CDATA[" . get_the_title($post->ID) . "]]></news:title>\n";
            echo "\t\t</news:news>\n";
            echo "\t</url>\n";
        }

        echo '</urlset>';
        exit;
    }

    /**
     * Get images from post
     */
    private function get_post_images($post) {
        $images = array();

        // Featured image
        if (has_post_thumbnail($post->ID)) {
            $thumbnail_id = get_post_thumbnail_id($post->ID);
            $image_url = get_the_post_thumbnail_url($post->ID, 'full');
            $image_alt = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
            $attachment = get_post($thumbnail_id);

            $images[] = array(
                'url' => $image_url,
                'title' => $image_alt ?: $attachment->post_title,
                'caption' => $attachment->post_excerpt,
            );
        }

        // Content images
        preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $post->post_content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $img_url) {
                $attachment_id = attachment_url_to_postid($img_url);

                if ($attachment_id) {
                    $image_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
                    $attachment = get_post($attachment_id);

                    $images[] = array(
                        'url' => $img_url,
                        'title' => $image_alt ?: ($attachment ? $attachment->post_title : ''),
                        'caption' => $attachment ? $attachment->post_excerpt : '',
                    );
                }
            }
        }

        return array_slice($images, 0, 10); // Max 10 images per page
    }

    /**
     * Get videos from post
     */
    private function get_post_videos($post) {
        $videos = array();

        // YouTube embeds
        preg_match_all('/youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)|youtu\.be\/([a-zA-Z0-9_-]+)/i', $post->post_content, $youtube_matches);

        if (!empty($youtube_matches[1]) || !empty($youtube_matches[2])) {
            foreach (array_merge($youtube_matches[1], $youtube_matches[2]) as $video_id) {
                if (empty($video_id)) continue;

                $videos[] = array(
                    'url' => 'https://www.youtube.com/watch?v=' . $video_id,
                    'title' => get_the_title($post->ID),
                    'description' => wp_trim_words($post->post_content, 50),
                    'thumbnail' => 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg',
                );
            }
        }

        // Vimeo embeds
        preg_match_all('/vimeo\.com\/([0-9]+)/i', $post->post_content, $vimeo_matches);

        if (!empty($vimeo_matches[1])) {
            foreach ($vimeo_matches[1] as $video_id) {
                $videos[] = array(
                    'url' => 'https://vimeo.com/' . $video_id,
                    'title' => get_the_title($post->ID),
                    'description' => wp_trim_words($post->post_content, 50),
                    'thumbnail' => '',
                );
            }
        }

        return $videos;
    }

    /**
     * Add sitemap meta box
     */
    public function add_sitemap_meta_box() {
        $post_types = apply_filters('nerdy_seo_post_types', get_post_types(array('public' => true), 'names'));

        foreach ($post_types as $post_type) {
            add_meta_box(
                'nerdy_seo_sitemap_meta_box',
                __('Sitemap Settings', 'nerdy-seo'),
                array($this, 'render_sitemap_meta_box'),
                $post_type,
                'side',
                'default'
            );
        }
    }

    /**
     * Render sitemap meta box
     */
    public function render_sitemap_meta_box($post) {
        wp_nonce_field('nerdy_seo_sitemap_meta_box', 'nerdy_seo_sitemap_meta_box_nonce');

        $exclude = get_post_meta($post->ID, '_nerdy_seo_sitemap_exclude', true);
        $priority = get_post_meta($post->ID, '_nerdy_seo_sitemap_priority', true);
        $changefreq = get_post_meta($post->ID, '_nerdy_seo_sitemap_changefreq', true);

        ?>
        <style>
            .nerdy-seo-sitemap-field { margin-bottom: 15px; }
            .nerdy-seo-sitemap-field label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
                font-size: 13px;
            }
            .nerdy-seo-sitemap-field select,
            .nerdy-seo-sitemap-field input[type="number"] {
                width: 100%;
            }
        </style>

        <div class="nerdy-seo-sitemap-field">
            <label>
                <input
                    type="checkbox"
                    name="nerdy_seo_sitemap_exclude"
                    value="1"
                    <?php checked($exclude, '1'); ?>
                />
                <?php _e('Exclude from XML Sitemap', 'nerdy-seo'); ?>
            </label>
        </div>

        <div class="nerdy-seo-sitemap-field">
            <label for="nerdy_seo_sitemap_priority">
                <?php _e('Priority', 'nerdy-seo'); ?>
            </label>
            <input
                type="number"
                id="nerdy_seo_sitemap_priority"
                name="nerdy_seo_sitemap_priority"
                value="<?php echo esc_attr($priority); ?>"
                min="0"
                max="1"
                step="0.1"
                placeholder="<?php _e('Auto', 'nerdy-seo'); ?>"
            />
            <p class="description"><?php _e('0.0 to 1.0 (leave blank for automatic)', 'nerdy-seo'); ?></p>
        </div>

        <div class="nerdy-seo-sitemap-field">
            <label for="nerdy_seo_sitemap_changefreq">
                <?php _e('Change Frequency', 'nerdy-seo'); ?>
            </label>
            <select id="nerdy_seo_sitemap_changefreq" name="nerdy_seo_sitemap_changefreq">
                <option value=""><?php _e('Auto', 'nerdy-seo'); ?></option>
                <option value="always" <?php selected($changefreq, 'always'); ?>><?php _e('Always', 'nerdy-seo'); ?></option>
                <option value="hourly" <?php selected($changefreq, 'hourly'); ?>><?php _e('Hourly', 'nerdy-seo'); ?></option>
                <option value="daily" <?php selected($changefreq, 'daily'); ?>><?php _e('Daily', 'nerdy-seo'); ?></option>
                <option value="weekly" <?php selected($changefreq, 'weekly'); ?>><?php _e('Weekly', 'nerdy-seo'); ?></option>
                <option value="monthly" <?php selected($changefreq, 'monthly'); ?>><?php _e('Monthly', 'nerdy-seo'); ?></option>
                <option value="yearly" <?php selected($changefreq, 'yearly'); ?>><?php _e('Yearly', 'nerdy-seo'); ?></option>
                <option value="never" <?php selected($changefreq, 'never'); ?>><?php _e('Never', 'nerdy-seo'); ?></option>
            </select>
        </div>
        <?php
    }

    /**
     * Save sitemap meta box
     */
    public function save_sitemap_meta_box($post_id, $post) {
        if (!isset($_POST['nerdy_seo_sitemap_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['nerdy_seo_sitemap_meta_box_nonce'], 'nerdy_seo_sitemap_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save exclude
        update_post_meta($post_id, '_nerdy_seo_sitemap_exclude', isset($_POST['nerdy_seo_sitemap_exclude']) ? '1' : '0');

        // Save priority
        if (isset($_POST['nerdy_seo_sitemap_priority']) && $_POST['nerdy_seo_sitemap_priority'] !== '') {
            $priority = floatval($_POST['nerdy_seo_sitemap_priority']);
            $priority = max(0, min(1, $priority));
            update_post_meta($post_id, '_nerdy_seo_sitemap_priority', $priority);
        } else {
            delete_post_meta($post_id, '_nerdy_seo_sitemap_priority');
        }

        // Save change frequency
        if (isset($_POST['nerdy_seo_sitemap_changefreq'])) {
            update_post_meta($post_id, '_nerdy_seo_sitemap_changefreq', sanitize_text_field($_POST['nerdy_seo_sitemap_changefreq']));
        }
    }

    /**
     * Register sitemap settings
     */
    public function register_sitemap_settings() {
        register_setting('nerdy_seo_settings', 'nerdy_seo_sitemap_enabled');
        register_setting('nerdy_seo_settings', 'nerdy_seo_sitemap_max_urls');
        register_setting('nerdy_seo_settings', 'nerdy_seo_sitemap_exclude_post_types');
        register_setting('nerdy_seo_settings', 'nerdy_seo_sitemap_exclude_taxonomies');
        register_setting('nerdy_seo_settings', 'nerdy_seo_image_sitemap_enabled');
        register_setting('nerdy_seo_settings', 'nerdy_seo_video_sitemap_enabled');
        register_setting('nerdy_seo_settings', 'nerdy_seo_news_sitemap_enabled');

        // Sitemap section
        add_settings_section(
            'nerdy_seo_sitemap_section',
            __('XML Sitemap Settings', 'nerdy-seo'),
            array($this, 'render_sitemap_section'),
            'nerdy-seo'
        );

        add_settings_field(
            'nerdy_seo_sitemap_enabled',
            __('Enable XML Sitemaps', 'nerdy-seo'),
            array($this, 'render_sitemap_enabled_field'),
            'nerdy-seo',
            'nerdy_seo_sitemap_section'
        );

        add_settings_field(
            'nerdy_seo_sitemap_max_urls',
            __('Max URLs per Sitemap', 'nerdy-seo'),
            array($this, 'render_max_urls_field'),
            'nerdy-seo',
            'nerdy_seo_sitemap_section'
        );

        add_settings_field(
            'nerdy_seo_image_sitemap_enabled',
            __('Enable Image Sitemap', 'nerdy-seo'),
            array($this, 'render_image_sitemap_field'),
            'nerdy-seo',
            'nerdy_seo_sitemap_section'
        );

        add_settings_field(
            'nerdy_seo_video_sitemap_enabled',
            __('Enable Video Sitemap', 'nerdy-seo'),
            array($this, 'render_video_sitemap_field'),
            'nerdy-seo',
            'nerdy_seo_sitemap_section'
        );

        add_settings_field(
            'nerdy_seo_news_sitemap_enabled',
            __('Enable News Sitemap', 'nerdy-seo'),
            array($this, 'render_news_sitemap_field'),
            'nerdy-seo',
            'nerdy_seo_sitemap_section'
        );

        add_settings_field(
            'nerdy_seo_sitemap_exclude_post_types',
            __('Exclude Post Types', 'nerdy-seo'),
            array($this, 'render_exclude_post_types_field'),
            'nerdy-seo',
            'nerdy_seo_sitemap_section'
        );
    }

    /**
     * Render sitemap section
     */
    public function render_sitemap_section() {
        $sitemap_url = home_url('/wp-sitemap.xml');
        echo '<p>' . __('Configure your XML sitemaps. Your main sitemap is located at:', 'nerdy-seo') . '</p>';
        echo '<p><a href="' . esc_url($sitemap_url) . '" target="_blank">' . esc_html($sitemap_url) . '</a></p>';
    }

    /**
     * Render sitemap enabled field
     */
    public function render_sitemap_enabled_field() {
        $value = get_option('nerdy_seo_sitemap_enabled', true);
        ?>
        <label>
            <input type="checkbox" name="nerdy_seo_sitemap_enabled" value="1" <?php checked($value, true); ?> />
            <?php _e('Enable WordPress XML sitemaps', 'nerdy-seo'); ?>
        </label>
        <?php
    }

    /**
     * Render max URLs field
     */
    public function render_max_urls_field() {
        $value = get_option('nerdy_seo_sitemap_max_urls', 2000);
        ?>
        <input type="number" name="nerdy_seo_sitemap_max_urls" value="<?php echo esc_attr($value); ?>" min="1" max="50000" class="small-text" />
        <p class="description"><?php _e('Maximum URLs per sitemap file (default: 2000, max: 50000)', 'nerdy-seo'); ?></p>
        <?php
    }

    /**
     * Render image sitemap field
     */
    public function render_image_sitemap_field() {
        $value = get_option('nerdy_seo_image_sitemap_enabled', false);
        $sitemap_url = home_url('/image-sitemap.xml');
        ?>
        <label>
            <input type="checkbox" name="nerdy_seo_image_sitemap_enabled" value="1" <?php checked($value, true); ?> />
            <?php _e('Generate image sitemap', 'nerdy-seo'); ?>
        </label>
        <?php if ($value): ?>
            <p class="description">
                <a href="<?php echo esc_url($sitemap_url); ?>" target="_blank"><?php echo esc_html($sitemap_url); ?></a>
            </p>
        <?php endif; ?>
        <?php
    }

    /**
     * Render video sitemap field
     */
    public function render_video_sitemap_field() {
        $value = get_option('nerdy_seo_video_sitemap_enabled', false);
        $sitemap_url = home_url('/video-sitemap.xml');
        ?>
        <label>
            <input type="checkbox" name="nerdy_seo_video_sitemap_enabled" value="1" <?php checked($value, true); ?> />
            <?php _e('Generate video sitemap (YouTube, Vimeo)', 'nerdy-seo'); ?>
        </label>
        <?php if ($value): ?>
            <p class="description">
                <a href="<?php echo esc_url($sitemap_url); ?>" target="_blank"><?php echo esc_html($sitemap_url); ?></a>
            </p>
        <?php endif; ?>
        <?php
    }

    /**
     * Render news sitemap field
     */
    public function render_news_sitemap_field() {
        $value = get_option('nerdy_seo_news_sitemap_enabled', false);
        $sitemap_url = home_url('/news-sitemap.xml');
        ?>
        <label>
            <input type="checkbox" name="nerdy_seo_news_sitemap_enabled" value="1" <?php checked($value, true); ?> />
            <?php _e('Generate Google News sitemap', 'nerdy-seo'); ?>
        </label>
        <?php if ($value): ?>
            <p class="description">
                <a href="<?php echo esc_url($sitemap_url); ?>" target="_blank"><?php echo esc_html($sitemap_url); ?></a>
            </p>
        <?php endif; ?>
        <p class="description"><?php _e('Only includes posts from the last 2 days for Google News', 'nerdy-seo'); ?></p>
        <?php
    }

    /**
     * Render exclude post types field
     */
    public function render_exclude_post_types_field() {
        $excluded = get_option('nerdy_seo_sitemap_exclude_post_types', array());
        $post_types = get_post_types(array('public' => true), 'objects');

        echo '<fieldset>';
        foreach ($post_types as $post_type) {
            $checked = in_array($post_type->name, $excluded);
            ?>
            <label>
                <input
                    type="checkbox"
                    name="nerdy_seo_sitemap_exclude_post_types[]"
                    value="<?php echo esc_attr($post_type->name); ?>"
                    <?php checked($checked); ?>
                />
                <?php echo esc_html($post_type->label); ?>
            </label><br>
            <?php
        }
        echo '</fieldset>';
        echo '<p class="description">' . __('Exclude these post types from the sitemap', 'nerdy-seo') . '</p>';
    }

    /**
     * Flush rewrite rules
     */
    public function flush_rewrites() {
        flush_rewrite_rules();
    }

    /**
     * Add custom providers
     */
    public function add_custom_providers($provider, $name) {
        return $provider;
    }
}

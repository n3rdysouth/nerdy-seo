<?php
/**
 * Custom XML Sitemap functionality
 *
 * Pre-generates sitemaps via WP Cron for better performance and reliability
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
        // Disable WordPress native sitemaps
        add_filter('wp_sitemaps_enabled', '__return_false');

        // Add rewrite rules for sitemap
        add_action('init', array($this, 'add_rewrite_rules'));

        // Serve sitemap
        add_action('template_redirect', array($this, 'serve_sitemap'), 1);

        // Schedule sitemap generation
        add_action('nerdy_seo_generate_sitemap', array($this, 'generate_all_sitemaps'));

        // Regenerate on post save/delete
        add_action('save_post', array($this, 'schedule_regeneration'), 99);
        add_action('delete_post', array($this, 'schedule_regeneration'), 99);
        add_action('transition_post_status', array($this, 'schedule_regeneration'), 99);

        // Add meta box for per-post sitemap settings
        add_action('add_meta_boxes', array($this, 'add_sitemap_meta_box'));
        add_action('save_post', array($this, 'save_sitemap_meta_box'), 10, 2);

        // Add sitemap settings to admin
        add_action('admin_init', array($this, 'register_sitemap_settings'));

        // AJAX handler for manual generation
        add_action('wp_ajax_nerdy_seo_generate_sitemap', array($this, 'ajax_generate_sitemap'));

        // Schedule cron if not scheduled
        if (!wp_next_scheduled('nerdy_seo_generate_sitemap')) {
            wp_schedule_event(time(), 'daily', 'nerdy_seo_generate_sitemap');
        }
    }

    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules() {
        // Main sitemap index
        add_rewrite_rule('^sitemap\.xml$', 'index.php?nerdy_seo_sitemap=index', 'top');
        add_rewrite_rule('^sitemap_index\.xml$', 'index.php?nerdy_seo_sitemap=index', 'top');

        // Post type sitemaps
        add_rewrite_rule('^sitemap-([a-z_]+)\.xml$', 'index.php?nerdy_seo_sitemap=$matches[1]', 'top');

        // Taxonomy sitemaps
        add_rewrite_rule('^sitemap-tax-([a-z_]+)\.xml$', 'index.php?nerdy_seo_sitemap_tax=$matches[1]', 'top');

        // Add query vars
        add_rewrite_tag('%nerdy_seo_sitemap%', '([^&]+)');
        add_rewrite_tag('%nerdy_seo_sitemap_tax%', '([^&]+)');
    }

    /**
     * Serve sitemap from cache
     */
    public function serve_sitemap() {
        $sitemap_type = get_query_var('nerdy_seo_sitemap');
        $sitemap_tax = get_query_var('nerdy_seo_sitemap_tax');

        if (empty($sitemap_type) && empty($sitemap_tax)) {
            return;
        }

        // Check if sitemaps are enabled
        if (!get_option('nerdy_seo_sitemap_enabled', true)) {
            return;
        }

        // Get cached sitemap
        $cache_key = '';
        if ($sitemap_type === 'index') {
            $cache_key = 'nerdy_seo_sitemap_index';
        } elseif (!empty($sitemap_tax)) {
            $cache_key = "nerdy_seo_sitemap_tax_{$sitemap_tax}";
        } elseif (!empty($sitemap_type)) {
            $cache_key = "nerdy_seo_sitemap_{$sitemap_type}";
        }

        $xml = get_option($cache_key);

        // If not cached, generate on the fly
        if (empty($xml)) {
            if ($sitemap_type === 'index') {
                $xml = $this->build_sitemap_index();
            } elseif (!empty($sitemap_tax)) {
                $xml = $this->build_taxonomy_sitemap($sitemap_tax);
            } elseif (!empty($sitemap_type)) {
                $xml = $this->build_post_type_sitemap($sitemap_type);
            }

            if ($xml) {
                update_option($cache_key, $xml, false);
            }
        }

        if ($xml) {
            status_header(200);
            header('Content-Type: application/xml; charset=utf-8');
            header('X-Robots-Tag: noindex, follow', true);
            echo $xml;
            exit;
        }
    }

    /**
     * Generate all sitemaps (called by cron)
     */
    public function generate_all_sitemaps() {
        // Get WordPress root directory
        $sitemap_dir = ABSPATH;

        // Generate sitemap index
        $index = $this->build_sitemap_index();

        // Save to database (backup)
        update_option('nerdy_seo_sitemap_index', $index, false);

        // Write physical file
        $this->write_sitemap_file($sitemap_dir . 'sitemap.xml', $index);

        // Generate post type sitemaps
        $post_types = get_post_types(array('public' => true), 'names');
        $excluded_types = get_option('nerdy_seo_sitemap_exclude_post_types', array());

        foreach ($post_types as $post_type) {
            if (in_array($post_type, $excluded_types)) {
                continue;
            }

            $count = wp_count_posts($post_type);
            if ($count->publish > 0) {
                $xml = $this->build_post_type_sitemap($post_type);

                // Save to database (backup)
                update_option("nerdy_seo_sitemap_{$post_type}", $xml, false);

                // Write physical file
                $this->write_sitemap_file($sitemap_dir . "sitemap-{$post_type}.xml", $xml);
            }
        }

        // Generate taxonomy sitemaps
        $taxonomies = get_taxonomies(array('public' => true), 'names');
        $excluded_taxonomies = get_option('nerdy_seo_sitemap_exclude_taxonomies', array());

        foreach ($taxonomies as $taxonomy) {
            if (in_array($taxonomy, $excluded_taxonomies)) {
                continue;
            }

            $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => true));
            if (!empty($terms) && !is_wp_error($terms)) {
                $xml = $this->build_taxonomy_sitemap($taxonomy);

                // Save to database (backup)
                update_option("nerdy_seo_sitemap_tax_{$taxonomy}", $xml, false);

                // Write physical file
                $this->write_sitemap_file($sitemap_dir . "sitemap-tax-{$taxonomy}.xml", $xml);
            }
        }

        // Update last generated time
        update_option('nerdy_seo_sitemap_last_generated', current_time('mysql'), false);
    }

    /**
     * Write sitemap to physical file
     */
    private function write_sitemap_file($filepath, $content) {
        global $wp_filesystem;

        // Initialize WordPress filesystem if not already
        if (empty($wp_filesystem)) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
            WP_Filesystem();
        }

        // Use WordPress filesystem API if available, otherwise fall back to file_put_contents
        if ($wp_filesystem && is_object($wp_filesystem)) {
            $wp_filesystem->put_contents($filepath, $content, FS_CHMOD_FILE);
        } else {
            // Fallback to direct file writing
            @file_put_contents($filepath, $content);
            @chmod($filepath, 0644);
        }
    }

    /**
     * Build sitemap index
     */
    private function build_sitemap_index() {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        // Get post types
        $post_types = get_post_types(array('public' => true), 'names');
        $excluded_types = get_option('nerdy_seo_sitemap_exclude_post_types', array());

        foreach ($post_types as $post_type) {
            if (in_array($post_type, $excluded_types)) {
                continue;
            }

            $count = wp_count_posts($post_type);
            if ($count->publish > 0) {
                $xml .= "\t<sitemap>\n";
                $xml .= "\t\t<loc>" . home_url("/sitemap-{$post_type}.xml") . "</loc>\n";
                $xml .= "\t\t<lastmod>" . date('c') . "</lastmod>\n";
                $xml .= "\t</sitemap>\n";
            }
        }

        // Get taxonomies
        $taxonomies = get_taxonomies(array('public' => true), 'names');
        $excluded_taxonomies = get_option('nerdy_seo_sitemap_exclude_taxonomies', array());

        foreach ($taxonomies as $taxonomy) {
            if (in_array($taxonomy, $excluded_taxonomies)) {
                continue;
            }

            $terms = get_terms(array('taxonomy' => $taxonomy, 'hide_empty' => true));
            if (!empty($terms) && !is_wp_error($terms)) {
                $xml .= "\t<sitemap>\n";
                $xml .= "\t\t<loc>" . home_url("/sitemap-tax-{$taxonomy}.xml") . "</loc>\n";
                $xml .= "\t\t<lastmod>" . date('c') . "</lastmod>\n";
                $xml .= "\t</sitemap>\n";
            }
        }

        $xml .= '</sitemapindex>';
        return $xml;
    }

    /**
     * Build post type sitemap
     */
    private function build_post_type_sitemap($post_type) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => 2000,
            'orderby' => 'modified',
            'order' => 'DESC',
            'no_found_rows' => true,
            'update_post_meta_cache' => false,
            'update_post_term_cache' => false,
        );

        $posts = get_posts($args);

        foreach ($posts as $post) {
            // Check if excluded
            $exclude = get_post_meta($post->ID, '_nerdy_seo_sitemap_exclude', true);
            if ($exclude === '1') {
                continue;
            }

            // Check if noindex
            $noindex = get_post_meta($post->ID, '_nerdy_seo_noindex', true);
            if ($noindex === '1') {
                continue;
            }

            $xml .= "\t<url>\n";
            $xml .= "\t\t<loc>" . esc_url(get_permalink($post->ID)) . "</loc>\n";
            $xml .= "\t\t<lastmod>" . get_the_modified_date('c', $post->ID) . "</lastmod>\n";

            // Priority
            $priority = get_post_meta($post->ID, '_nerdy_seo_sitemap_priority', true);
            if (empty($priority)) {
                $priority = $this->calculate_priority($post);
            }
            $xml .= "\t\t<priority>" . number_format($priority, 1) . "</priority>\n";

            // Change frequency
            $changefreq = get_post_meta($post->ID, '_nerdy_seo_sitemap_changefreq', true);
            if (empty($changefreq)) {
                $changefreq = $this->calculate_changefreq($post);
            }
            $xml .= "\t\t<changefreq>{$changefreq}</changefreq>\n";

            $xml .= "\t</url>\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }

    /**
     * Build taxonomy sitemap
     */
    private function build_taxonomy_sitemap($taxonomy) {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => true,
            'orderby' => 'count',
            'order' => 'DESC',
        ));

        if (!is_wp_error($terms) && !empty($terms)) {
            foreach ($terms as $term) {
                $xml .= "\t<url>\n";
                $xml .= "\t\t<loc>" . esc_url(get_term_link($term)) . "</loc>\n";
                $xml .= "\t\t<lastmod>" . date('c') . "</lastmod>\n";
                $xml .= "\t\t<priority>0.6</priority>\n";
                $xml .= "\t\t<changefreq>weekly</changefreq>\n";
                $xml .= "\t</url>\n";
            }
        }

        $xml .= '</urlset>';
        return $xml;
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
            return 0.7;
        } elseif ($post_age_days < 365) {
            return 0.6;
        } else {
            return 0.5;
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
     * Schedule regeneration (debounced to avoid multiple regenerations)
     */
    public function schedule_regeneration() {
        if (!wp_next_scheduled('nerdy_seo_generate_sitemap')) {
            wp_schedule_single_event(time() + 300, 'nerdy_seo_generate_sitemap');
        }
    }

    /**
     * AJAX handler for manual generation
     */
    public function ajax_generate_sitemap() {
        check_ajax_referer('nerdy_seo_generate_sitemap', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'nerdy-seo')));
        }

        $this->generate_all_sitemaps();

        wp_send_json_success(array(
            'message' => __('Sitemap generated successfully!', 'nerdy-seo'),
            'last_generated' => get_option('nerdy_seo_sitemap_last_generated')
        ));
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
        register_setting('nerdy_seo_settings', 'nerdy_seo_sitemap_exclude_post_types');
        register_setting('nerdy_seo_settings', 'nerdy_seo_sitemap_exclude_taxonomies');

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
            'nerdy_seo_sitemap_exclude_post_types',
            __('Exclude Post Types', 'nerdy-seo'),
            array($this, 'render_exclude_post_types_field'),
            'nerdy-seo',
            'nerdy_seo_sitemap_section'
        );

        add_settings_field(
            'nerdy_seo_generate_sitemap',
            __('Generate Sitemap', 'nerdy-seo'),
            array($this, 'render_generate_field'),
            'nerdy-seo',
            'nerdy_seo_sitemap_section'
        );
    }

    /**
     * Render sitemap section
     */
    public function render_sitemap_section() {
        $sitemap_url = home_url('/sitemap.xml');
        $last_generated = get_option('nerdy_seo_sitemap_last_generated');

        echo '<p>' . __('Your XML sitemap is located at:', 'nerdy-seo') . '</p>';
        echo '<p><a href="' . esc_url($sitemap_url) . '" target="_blank" class="button">' . esc_html($sitemap_url) . '</a></p>';

        if ($last_generated) {
            echo '<p class="description">' . sprintf(__('Last generated: %s', 'nerdy-seo'), $last_generated) . '</p>';
        }

        echo '<p class="description">' . __('Sitemap is automatically regenerated daily and when content changes.', 'nerdy-seo') . '</p>';
    }

    /**
     * Render sitemap enabled field
     */
    public function render_sitemap_enabled_field() {
        $value = get_option('nerdy_seo_sitemap_enabled', true);
        ?>
        <label>
            <input type="checkbox" name="nerdy_seo_sitemap_enabled" value="1" <?php checked($value, true); ?> />
            <?php _e('Enable XML sitemaps', 'nerdy-seo'); ?>
        </label>
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
            <label style="display: block; margin-bottom: 5px;">
                <input
                    type="checkbox"
                    name="nerdy_seo_sitemap_exclude_post_types[]"
                    value="<?php echo esc_attr($post_type->name); ?>"
                    <?php checked($checked); ?>
                />
                <?php echo esc_html($post_type->label); ?>
            </label>
            <?php
        }
        echo '</fieldset>';
        echo '<p class="description">' . __('Exclude these post types from the sitemap', 'nerdy-seo') . '</p>';
    }

    /**
     * Render generate field
     */
    public function render_generate_field() {
        ?>
        <button type="button" class="button button-primary" id="nerdy-seo-generate-sitemap">
            <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
            <?php _e('Generate Now', 'nerdy-seo'); ?>
        </button>
        <span id="nerdy-seo-sitemap-status" style="margin-left: 10px;"></span>
        <p class="description"><?php _e('Manually regenerate all sitemap files. This happens automatically daily and when content changes.', 'nerdy-seo'); ?></p>

        <script>
        jQuery(document).ready(function($) {
            $('#nerdy-seo-generate-sitemap').on('click', function() {
                var $btn = $(this);
                var $status = $('#nerdy-seo-sitemap-status');

                $btn.prop('disabled', true);
                $btn.find('.dashicons').addClass('spin');
                $status.html('<span style="color: #666;">Generating...</span>');

                $.post(ajaxurl, {
                    action: 'nerdy_seo_generate_sitemap',
                    nonce: '<?php echo wp_create_nonce('nerdy_seo_generate_sitemap'); ?>'
                }, function(response) {
                    $btn.prop('disabled', false);
                    $btn.find('.dashicons').removeClass('spin');

                    if (response.success) {
                        $status.html('<span style="color: #00a32a;">✓ ' + response.data.message + '</span>');
                        setTimeout(function() {
                            $status.fadeOut();
                        }, 3000);
                    } else {
                        $status.html('<span style="color: #d63638;">✗ Error generating sitemap</span>');
                    }
                });
            });
        });
        </script>
        <style>
        .dashicons.spin {
            animation: spin 1s linear infinite;
        }
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        </style>
        <?php
    }
}

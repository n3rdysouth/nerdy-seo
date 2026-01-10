<?php
/**
 * AIOSEO Migration Tool
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Migration class
 */
class Nerdy_SEO_Migration {

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
        // Admin menu (priority 20 to load after main menu)
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);

        // AJAX handler
        add_action('wp_ajax_nerdy_seo_migrate_aioseo', array($this, 'ajax_migrate_aioseo'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'nerdy-seo',
            __('Migration', 'nerdy-seo'),
            __('Migration', 'nerdy-seo'),
            'manage_options',
            'nerdy-seo-migration',
            array($this, 'render_migration_page'),
            100
        );
    }

    /**
     * Render migration page
     */
    public function render_migration_page() {
        $aioseo_active = $this->is_aioseo_active();
        $aioseo_data = $this->check_aioseo_data();

        ?>
        <div class="wrap">
            <h1><?php _e('SEO Data Migration', 'nerdy-seo'); ?></h1>

            <div class="nerdy-seo-migration-status" style="background: white; padding: 20px; margin: 20px 0; border-left: 4px solid <?php echo $aioseo_active ? '#2271b1' : '#dba617'; ?>;">
                <h2><?php _e('Migration Status', 'nerdy-seo'); ?></h2>

                <?php if ($aioseo_active): ?>
                    <p style="color: #46b450;">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('All in One SEO (AIOSEO) is installed and active', 'nerdy-seo'); ?>
                    </p>
                <?php else: ?>
                    <p style="color: #dba617;">
                        <span class="dashicons dashicons-info"></span>
                        <?php _e('All in One SEO (AIOSEO) is not currently active', 'nerdy-seo'); ?>
                    </p>
                <?php endif; ?>

                <h3><?php _e('Detectable AIOSEO Data', 'nerdy-seo'); ?></h3>
                <ul>
                    <li>
                        <strong><?php _e('Posts with SEO data:', 'nerdy-seo'); ?></strong>
                        <?php echo number_format($aioseo_data['posts']); ?>
                    </li>
                    <li>
                        <strong><?php _e('Pages with SEO data:', 'nerdy-seo'); ?></strong>
                        <?php echo number_format($aioseo_data['pages']); ?>
                    </li>
                    <li>
                        <strong><?php _e('Total items to migrate:', 'nerdy-seo'); ?></strong>
                        <?php echo number_format($aioseo_data['total']); ?>
                    </li>
                </ul>
            </div>

            <?php if ($aioseo_data['total'] > 0): ?>
                <div class="nerdy-seo-migration-info" style="background: #f0f6fc; padding: 20px; margin: 20px 0; border-left: 4px solid #2271b1;">
                    <h3><?php _e('What Will Be Migrated?', 'nerdy-seo'); ?></h3>
                    <ul>
                        <li>✓ SEO titles</li>
                        <li>✓ Meta descriptions</li>
                        <li>✓ Focus keywords</li>
                        <li>✓ Canonical URLs</li>
                        <li>✓ Robots meta (noindex, nofollow)</li>
                        <li>✓ Open Graph titles and descriptions</li>
                        <li>✓ Open Graph images</li>
                        <li>✓ Twitter Card titles and descriptions</li>
                        <li>✓ Twitter Card images</li>
                        <li>✓ Schema settings</li>
                        <li>✓ Local Business information (name, address, hours, etc.)</li>
                        <li>✓ Social media profiles</li>
                        <li>✓ Global SEO settings (separator, home title/description)</li>
                    </ul>
                    <p><strong><?php _e('Note:', 'nerdy-seo'); ?></strong> <?php _e('This will NOT delete your AIOSEO data. You can keep AIOSEO active or deactivate it after migration.', 'nerdy-seo'); ?></p>
                </div>

                <div class="nerdy-seo-migration-actions" style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
                    <h3><?php _e('Start Migration', 'nerdy-seo'); ?></h3>
                    <p><?php _e('Click the button below to migrate all SEO data from AIOSEO to Nerdy SEO.', 'nerdy-seo'); ?></p>

                    <button type="button" class="button button-primary button-large" id="nerdy-seo-start-migration">
                        <?php _e('Start Migration', 'nerdy-seo'); ?>
                    </button>

                    <div id="nerdy-seo-migration-progress" style="display: none; margin-top: 20px;">
                        <h4><?php _e('Migration in Progress...', 'nerdy-seo'); ?></h4>
                        <div style="background: #f0f0f0; height: 30px; border-radius: 5px; overflow: hidden; position: relative;">
                            <div id="nerdy-seo-progress-bar" style="background: #2271b1; height: 100%; width: 0%; transition: width 0.3s;"></div>
                            <div id="nerdy-seo-progress-text" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); font-weight: 600; color: #333;">
                                0%
                            </div>
                        </div>
                        <p id="nerdy-seo-progress-status" style="margin-top: 10px; font-style: italic;">
                            <?php _e('Preparing migration...', 'nerdy-seo'); ?>
                        </p>
                    </div>

                    <div id="nerdy-seo-migration-complete" style="display: none; margin-top: 20px; background: #d4edda; padding: 15px; border-left: 4px solid #46b450;">
                        <h4 style="margin: 0 0 10px 0; color: #155724;">
                            <span class="dashicons dashicons-yes-alt"></span>
                            <?php _e('Migration Complete!', 'nerdy-seo'); ?>
                        </h4>
                        <p id="nerdy-seo-migration-stats"></p>
                        <p>
                            <strong><?php _e('Next Steps:', 'nerdy-seo'); ?></strong>
                        </p>
                        <ol>
                            <li><?php _e('Review a few pages to ensure data migrated correctly', 'nerdy-seo'); ?></li>
                            <li><?php _e('If everything looks good, you can deactivate AIOSEO', 'nerdy-seo'); ?></li>
                            <li><?php _e('Consider keeping AIOSEO installed (but inactive) for a backup', 'nerdy-seo'); ?></li>
                        </ol>
                    </div>

                    <div id="nerdy-seo-migration-error" style="display: none; margin-top: 20px; background: #f8d7da; padding: 15px; border-left: 4px solid #dc3232;">
                        <h4 style="margin: 0 0 10px 0; color: #721c24;">
                            <span class="dashicons dashicons-warning"></span>
                            <?php _e('Migration Error', 'nerdy-seo'); ?>
                        </h4>
                        <p id="nerdy-seo-migration-error-message"></p>
                    </div>
                </div>
            <?php else: ?>
                <div class="notice notice-info">
                    <p><?php _e('No AIOSEO data found to migrate. This could mean:', 'nerdy-seo'); ?></p>
                    <ul>
                        <li><?php _e('AIOSEO was never used on this site', 'nerdy-seo'); ?></li>
                        <li><?php _e('AIOSEO data has already been migrated', 'nerdy-seo'); ?></li>
                        <li><?php _e('AIOSEO data has been deleted', 'nerdy-seo'); ?></li>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Debug Information -->
            <?php
            $debug_info = get_option('nerdy_seo_migration_debug', false);
            if ($debug_info && current_user_can('manage_options')):
            ?>
                <div style="background: white; padding: 20px; margin: 20px 0; border: 1px solid #ddd;">
                    <h3><?php _e('Migration Debug Info', 'nerdy-seo'); ?></h3>
                    <p style="font-size: 12px; color: #666; font-style: italic;">
                        <?php _e('This information helps diagnose migration issues. Share this with support if you experience problems.', 'nerdy-seo'); ?>
                    </p>
                    <details>
                        <summary style="cursor: pointer; font-weight: 600; padding: 10px 0;">
                            <?php _e('Show Debug Data', 'nerdy-seo'); ?>
                        </summary>
                        <pre style="background: #f5f5f5; padding: 15px; overflow-x: auto; font-size: 11px; border: 1px solid #ddd; margin-top: 10px;"><?php echo esc_html(print_r($debug_info, true)); ?></pre>

                        <?php
                        // Show what AIOSEO options keys exist
                        $aioseo_opts = get_option('aioseo_options', false);
                        if ($aioseo_opts && is_array($aioseo_opts)):
                        ?>
                            <h4 style="margin-top: 20px;"><?php _e('AIOSEO Option Structure', 'nerdy-seo'); ?></h4>
                            <p style="font-size: 12px; color: #666;">
                                <?php _e('Top-level keys found in AIOSEO options:', 'nerdy-seo'); ?>
                            </p>
                            <ul style="list-style: none; padding: 0; font-family: monospace; font-size: 12px;">
                                <?php foreach (array_keys($aioseo_opts) as $key): ?>
                                    <li style="padding: 3px 0;">
                                        <code style="background: #f0f0f1; padding: 2px 6px; border-radius: 3px;">
                                            <?php echo esc_html($key); ?>
                                        </code>
                                        <?php if (is_array($aioseo_opts[$key])): ?>
                                            <span style="color: #666;">(<?php echo count($aioseo_opts[$key]); ?> items)</span>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </details>
                </div>
            <?php endif; ?>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#nerdy-seo-start-migration').on('click', function() {
                if (!confirm('<?php _e('Are you sure you want to migrate all SEO data from AIOSEO? This will copy data to Nerdy SEO fields.', 'nerdy-seo'); ?>')) {
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true);
                $('#nerdy-seo-migration-progress').show();

                $.post(ajaxurl, {
                    action: 'nerdy_seo_migrate_aioseo',
                    nonce: '<?php echo wp_create_nonce('nerdy_seo_migration'); ?>'
                }, function(response) {
                    if (response.success) {
                        $('#nerdy-seo-migration-progress').hide();
                        $('#nerdy-seo-migration-complete').show();
                        $('#nerdy-seo-migration-stats').html(response.data.message);
                    } else {
                        $('#nerdy-seo-migration-progress').hide();
                        $('#nerdy-seo-migration-error').show();
                        $('#nerdy-seo-migration-error-message').text(response.data.message || '<?php _e('Unknown error occurred', 'nerdy-seo'); ?>');
                        $btn.prop('disabled', false);
                    }
                }).fail(function() {
                    $('#nerdy-seo-migration-progress').hide();
                    $('#nerdy-seo-migration-error').show();
                    $('#nerdy-seo-migration-error-message').text('<?php _e('Server error occurred', 'nerdy-seo'); ?>');
                    $btn.prop('disabled', false);
                });

                // Simulate progress (since migration happens server-side)
                var progress = 0;
                var interval = setInterval(function() {
                    progress += Math.random() * 15;
                    if (progress > 90) {
                        progress = 90;
                        clearInterval(interval);
                    }
                    $('#nerdy-seo-progress-bar').css('width', progress + '%');
                    $('#nerdy-seo-progress-text').text(Math.round(progress) + '%');
                }, 500);
            });
        });
        </script>
        <?php
    }

    /**
     * Check if AIOSEO is active
     */
    private function is_aioseo_active() {
        return class_exists('AIOSEO\\Plugin\\AIOSEO') || function_exists('aioseo');
    }

    /**
     * Check AIOSEO data
     */
    private function check_aioseo_data() {
        global $wpdb;

        $data = array(
            'posts' => 0,
            'pages' => 0,
            'total' => 0,
        );

        // Check for AIOSEO postmeta
        $posts = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id)
            FROM {$wpdb->postmeta}
            WHERE meta_key LIKE '_aioseo_%'
            AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'post' AND post_status = 'publish')
        ");

        $pages = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id)
            FROM {$wpdb->postmeta}
            WHERE meta_key LIKE '_aioseo_%'
            AND post_id IN (SELECT ID FROM {$wpdb->posts} WHERE post_type = 'page' AND post_status = 'publish')
        ");

        $data['posts'] = (int) $posts;
        $data['pages'] = (int) $pages;
        $data['total'] = $data['posts'] + $data['pages'];

        return $data;
    }

    /**
     * AJAX: Migrate AIOSEO data
     */
    public function ajax_migrate_aioseo() {
        check_ajax_referer('nerdy_seo_migration', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'nerdy-seo')));
        }

        $migrated = $this->migrate_aioseo_data();

        if ($migrated['success']) {
            wp_send_json_success(array(
                'message' => sprintf(
                    __('Successfully migrated %d posts and %d pages. Total: %d items', 'nerdy-seo'),
                    $migrated['posts'],
                    $migrated['pages'],
                    $migrated['total']
                ),
            ));
        } else {
            wp_send_json_error(array('message' => $migrated['message']));
        }
    }

    /**
     * Migrate AIOSEO data
     */
    private function migrate_aioseo_data() {
        global $wpdb;

        $result = array(
            'success' => false,
            'posts' => 0,
            'pages' => 0,
            'total' => 0,
            'message' => '',
        );

        // Get all posts/pages with AIOSEO data
        $items = $wpdb->get_results("
            SELECT DISTINCT p.ID, p.post_type
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
            WHERE pm.meta_key LIKE '_aioseo_%'
            AND p.post_status = 'publish'
            AND p.post_type IN ('post', 'page')
        ");

        if (empty($items)) {
            $result['message'] = __('No AIOSEO data found to migrate', 'nerdy-seo');
            return $result;
        }

        foreach ($items as $item) {
            $this->migrate_post_data($item->ID);

            if ($item->post_type === 'post') {
                $result['posts']++;
            } elseif ($item->post_type === 'page') {
                $result['pages']++;
            }

            $result['total']++;
        }

        // Migrate global settings (local business, etc.)
        $this->migrate_global_settings();

        $result['success'] = true;

        return $result;
    }

    /**
     * Migrate single post data
     */
    private function migrate_post_data($post_id) {
        // SEO Title
        $aioseo_title = get_post_meta($post_id, '_aioseo_title', true);
        if ($aioseo_title) {
            update_post_meta($post_id, '_nerdy_seo_title', $this->convert_aioseo_variables($aioseo_title));
        }

        // Meta Description
        $aioseo_description = get_post_meta($post_id, '_aioseo_description', true);
        if ($aioseo_description) {
            update_post_meta($post_id, '_nerdy_seo_description', $this->convert_aioseo_variables($aioseo_description));
        }

        // Focus Keyword
        $aioseo_keyphrases = get_post_meta($post_id, '_aioseo_keyphrases', true);
        if ($aioseo_keyphrases) {
            $keyphrases = json_decode($aioseo_keyphrases, true);
            if (!empty($keyphrases['focus']['keyphrase'])) {
                update_post_meta($post_id, '_nerdy_seo_focus_keyword', $keyphrases['focus']['keyphrase']);
            }
        }

        // Canonical URL
        $aioseo_canonical = get_post_meta($post_id, '_aioseo_canonical_url', true);
        if ($aioseo_canonical) {
            update_post_meta($post_id, '_nerdy_seo_canonical', $aioseo_canonical);
        }

        // Robots Meta
        $aioseo_robots = get_post_meta($post_id, '_aioseo_robots_default', true);
        if ($aioseo_robots === '0') {
            $robots_noindex = get_post_meta($post_id, '_aioseo_robots_noindex', true);
            $robots_nofollow = get_post_meta($post_id, '_aioseo_robots_nofollow', true);

            if ($robots_noindex) {
                update_post_meta($post_id, '_nerdy_seo_noindex', '1');
            }

            if ($robots_nofollow) {
                update_post_meta($post_id, '_nerdy_seo_nofollow', '1');
            }
        }

        // Open Graph Title
        $aioseo_og_title = get_post_meta($post_id, '_aioseo_og_title', true);
        if ($aioseo_og_title) {
            update_post_meta($post_id, '_nerdy_seo_og_title', $this->convert_aioseo_variables($aioseo_og_title));
        }

        // Open Graph Description
        $aioseo_og_description = get_post_meta($post_id, '_aioseo_og_description', true);
        if ($aioseo_og_description) {
            update_post_meta($post_id, '_nerdy_seo_og_description', $this->convert_aioseo_variables($aioseo_og_description));
        }

        // Open Graph Image
        $aioseo_og_image = get_post_meta($post_id, '_aioseo_og_image_custom_url', true);
        if ($aioseo_og_image) {
            update_post_meta($post_id, '_nerdy_seo_og_image', $aioseo_og_image);
        }

        // Twitter Title
        $aioseo_twitter_title = get_post_meta($post_id, '_aioseo_twitter_title', true);
        if ($aioseo_twitter_title) {
            update_post_meta($post_id, '_nerdy_seo_twitter_title', $this->convert_aioseo_variables($aioseo_twitter_title));
        }

        // Twitter Description
        $aioseo_twitter_description = get_post_meta($post_id, '_aioseo_twitter_description', true);
        if ($aioseo_twitter_description) {
            update_post_meta($post_id, '_nerdy_seo_twitter_description', $this->convert_aioseo_variables($aioseo_twitter_description));
        }

        // Twitter Image
        $aioseo_twitter_image = get_post_meta($post_id, '_aioseo_twitter_image_custom_url', true);
        if ($aioseo_twitter_image) {
            update_post_meta($post_id, '_nerdy_seo_twitter_image', $aioseo_twitter_image);
        }

        // Schema Type (if available)
        $aioseo_schema_type = get_post_meta($post_id, '_aioseo_schema_type', true);
        if ($aioseo_schema_type) {
            // Map AIOSEO schema types to Nerdy SEO
            $schema_map = array(
                'Article' => 'Article',
                'FAQPage' => 'FAQ',
                'Review' => 'Review',
                'Product' => 'Product',
                'Service' => 'Service',
                'LocalBusiness' => 'LocalBusiness',
            );

            if (isset($schema_map[$aioseo_schema_type])) {
                update_post_meta($post_id, '_nerdy_seo_schema_type', $schema_map[$aioseo_schema_type]);
            }
        }
    }

    /**
     * Convert AIOSEO variables to Nerdy SEO format
     */
    private function convert_aioseo_variables($text) {
        if (empty($text)) {
            return $text;
        }

        // Map AIOSEO tags to Nerdy SEO variables
        $variable_map = array(
            // Common variables
            '#post_title' => '%title%',
            '#site_title' => '%sitename%',
            '#tagline' => '%sitedesc%',
            '#separator_sa' => '%separator%',
            '#post_excerpt' => '%excerpt%',
            '#post_author' => '%author%',
            '#post_date' => '%date%',
            '#current_year' => '%year%',
            '#current_month' => '%month%',
            '#current_day' => '%day%',

            // Alternative separator formats
            '#separator' => '%separator%',

            // Taxonomies
            '#categories' => '%categories%',
            '#tags' => '%tags%',

            // Additional common patterns
            '#post_content' => '%excerpt%',
            '#site_description' => '%sitedesc%',
        );

        // Replace AIOSEO variables with Nerdy SEO variables
        return str_replace(array_keys($variable_map), array_values($variable_map), $text);
    }

    /**
     * Migrate global settings from AIOSEO
     */
    private function migrate_global_settings() {
        global $wpdb;

        // Get AIOSEO options - they store it serialized in wp_options
        $aioseo_options = get_option('aioseo_options', false);

        // If not found as array, try getting the raw value
        if (!$aioseo_options) {
            // AIOSEO stores data in multiple option keys, try them all
            $option_keys = array(
                'aioseo_options',
                'aioseo_options_internal',
                '_aioseo_options',
            );

            foreach ($option_keys as $key) {
                $aioseo_options = get_option($key, false);
                if ($aioseo_options) {
                    break;
                }
            }
        }

        // Try to decode if it's JSON
        if (is_string($aioseo_options)) {
            $decoded = json_decode($aioseo_options, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $aioseo_options = $decoded;
            }
        }

        if (!$aioseo_options || !is_array($aioseo_options)) {
            return;
        }

        // Store debug info to help troubleshoot migration issues
        update_option('nerdy_seo_migration_debug', array(
            'timestamp' => current_time('mysql'),
            'aioseo_option_keys' => array_keys($aioseo_options),
            'has_local_business' => isset($aioseo_options['localBusiness']),
            'has_local' => isset($aioseo_options['local']),
            'has_search_appearance' => isset($aioseo_options['searchAppearance']),
            'has_social' => isset($aioseo_options['social']),
        ));

        // Migrate Local Business settings
        // AIOSEO can store this under different paths
        $lb = null;
        if (isset($aioseo_options['localBusiness'])) {
            $lb = $aioseo_options['localBusiness'];
        } elseif (isset($aioseo_options['local'])) {
            $lb = $aioseo_options['local'];
        } elseif (isset($aioseo_options['localSeo'])) {
            $lb = $aioseo_options['localSeo'];
        }

        if ($lb && is_array($lb)) {
            // Check if local business is enabled in AIOSEO
            $lb_enabled = false;
            if (isset($lb['enable'])) {
                $lb_enabled = !empty($lb['enable']);
            } elseif (isset($lb['enabled'])) {
                $lb_enabled = !empty($lb['enabled']);
            } elseif (!empty($lb['locations']) || !empty($lb['name'])) {
                // If they have data, assume it's enabled
                $lb_enabled = true;
            }

            if ($lb_enabled) {
                update_option('nerdy_seo_lb_enabled', true);
            }

            // Business name
            if (!empty($lb['name'])) {
                update_option('nerdy_seo_lb_name', sanitize_text_field($lb['name']));
            }

            // Business type/schema
            if (!empty($lb['type'])) {
                // AIOSEO uses schema.org types directly
                update_option('nerdy_seo_lb_type', sanitize_text_field($lb['type']));
            }

            // URLs
            if (!empty($lb['url'])) {
                update_option('nerdy_seo_lb_url', esc_url($lb['url']));
            }

            if (!empty($lb['image'])) {
                update_option('nerdy_seo_lb_image', esc_url($lb['image']));
            }

            if (!empty($lb['logo'])) {
                update_option('nerdy_seo_lb_logo', esc_url($lb['logo']));
            }

            // Contact info
            if (!empty($lb['phone'])) {
                update_option('nerdy_seo_lb_phone', sanitize_text_field($lb['phone']));
            }

            if (!empty($lb['email'])) {
                update_option('nerdy_seo_lb_email', sanitize_email($lb['email']));
            }

            // Price range
            if (!empty($lb['priceRange'])) {
                update_option('nerdy_seo_lb_price_range', sanitize_text_field($lb['priceRange']));
            }

            // Address
            if (!empty($lb['address'])) {
                $address = $lb['address'];

                if (!empty($address['streetAddress'])) {
                    update_option('nerdy_seo_lb_street_address', sanitize_text_field($address['streetAddress']));
                }

                if (!empty($address['addressLocality'])) {
                    update_option('nerdy_seo_lb_city', sanitize_text_field($address['addressLocality']));
                }

                if (!empty($address['addressRegion'])) {
                    update_option('nerdy_seo_lb_state', sanitize_text_field($address['addressRegion']));
                }

                if (!empty($address['postalCode'])) {
                    update_option('nerdy_seo_lb_postal_code', sanitize_text_field($address['postalCode']));
                }

                if (!empty($address['addressCountry'])) {
                    update_option('nerdy_seo_lb_country', sanitize_text_field($address['addressCountry']));
                }
            }

            // Geo coordinates
            if (!empty($lb['latitude'])) {
                update_option('nerdy_seo_lb_latitude', sanitize_text_field($lb['latitude']));
            }

            if (!empty($lb['longitude'])) {
                update_option('nerdy_seo_lb_longitude', sanitize_text_field($lb['longitude']));
            }

            // Opening hours - AIOSEO can store this in multiple formats
            $hours_data = null;
            if (!empty($lb['openingHours'])) {
                $hours_data = $lb['openingHours'];
            } elseif (!empty($lb['hours'])) {
                $hours_data = $lb['hours'];
            } elseif (!empty($lb['businessHours'])) {
                $hours_data = $lb['businessHours'];
            }

            if ($hours_data && is_array($hours_data)) {
                $hours = array();

                // Format 1: Array of day objects with 'day', 'openTime', 'closeTime'
                if (isset($hours_data[0]) && is_array($hours_data[0])) {
                    foreach ($hours_data as $day_data) {
                        if (empty($day_data['day'])) {
                            continue;
                        }

                        $day = strtolower($day_data['day']);

                        if (!empty($day_data['openTime'])) {
                            $hours[$day . '_open'] = sanitize_text_field($day_data['openTime']);
                        }

                        if (!empty($day_data['closeTime'])) {
                            $hours[$day . '_close'] = sanitize_text_field($day_data['closeTime']);
                        }
                    }
                } else {
                    // Format 2: Direct key-value pairs (monday_open, monday_close, etc.)
                    $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
                    foreach ($days as $day) {
                        if (isset($hours_data[$day . '_open'])) {
                            $hours[$day . '_open'] = sanitize_text_field($hours_data[$day . '_open']);
                        } elseif (isset($hours_data[$day]['open'])) {
                            $hours[$day . '_open'] = sanitize_text_field($hours_data[$day]['open']);
                        }

                        if (isset($hours_data[$day . '_close'])) {
                            $hours[$day . '_close'] = sanitize_text_field($hours_data[$day . '_close']);
                        } elseif (isset($hours_data[$day]['close'])) {
                            $hours[$day . '_close'] = sanitize_text_field($hours_data[$day]['close']);
                        }
                    }
                }

                if (!empty($hours)) {
                    update_option('nerdy_seo_lb_hours', $hours);
                }
            }

            // Social profiles - check multiple possible structures
            $social_urls = array();

            // Format 1: urls object
            if (!empty($lb['urls']) && is_array($lb['urls'])) {
                $social_urls = $lb['urls'];
            }
            // Format 2: social object
            elseif (!empty($lb['social']) && is_array($lb['social'])) {
                $social_urls = $lb['social'];
            }
            // Format 3: socialProfiles object
            elseif (!empty($lb['socialProfiles']) && is_array($lb['socialProfiles'])) {
                $social_urls = $lb['socialProfiles'];
            }

            // Map the social URLs
            if (!empty($social_urls)) {
                // Check different key names that AIOSEO might use
                $social_map = array(
                    'facebook' => array('facebook', 'facebookUrl', 'facebook_url'),
                    'twitter' => array('twitter', 'twitterUrl', 'twitter_url'),
                    'instagram' => array('instagram', 'instagramUrl', 'instagram_url'),
                    'linkedin' => array('linkedin', 'linkedinUrl', 'linkedin_url'),
                    'youtube' => array('youtube', 'youtubeUrl', 'youtube_url'),
                );

                foreach ($social_map as $platform => $possible_keys) {
                    foreach ($possible_keys as $key) {
                        if (!empty($social_urls[$key])) {
                            update_option('nerdy_seo_lb_' . $platform, esc_url($social_urls[$key]));
                            break;
                        }
                    }
                }
            }

            // Alternative: Check for sameAs array (standard schema.org format)
            if (!empty($lb['sameAs']) && is_array($lb['sameAs'])) {
                foreach ($lb['sameAs'] as $url) {
                    $url = trim($url);
                    if (empty($url)) {
                        continue;
                    }

                    if (stripos($url, 'facebook.com') !== false) {
                        update_option('nerdy_seo_lb_facebook', esc_url($url));
                    } elseif (stripos($url, 'twitter.com') !== false) {
                        update_option('nerdy_seo_lb_twitter', esc_url($url));
                    } elseif (stripos($url, 'instagram.com') !== false) {
                        update_option('nerdy_seo_lb_instagram', esc_url($url));
                    } elseif (stripos($url, 'linkedin.com') !== false) {
                        update_option('nerdy_seo_lb_linkedin', esc_url($url));
                    } elseif (stripos($url, 'youtube.com') !== false || stripos($url, 'youtu.be') !== false) {
                        update_option('nerdy_seo_lb_youtube', esc_url($url));
                    }
                }
            }

            // Also check direct fields on the $lb object
            $direct_social_keys = array('facebook', 'twitter', 'instagram', 'linkedin', 'youtube');
            foreach ($direct_social_keys as $platform) {
                if (!empty($lb[$platform]) && is_string($lb[$platform]) && filter_var($lb[$platform], FILTER_VALIDATE_URL)) {
                    update_option('nerdy_seo_lb_' . $platform, esc_url($lb[$platform]));
                }
            }
        }

        // Migrate other global settings
        if (isset($aioseo_options['searchAppearance'])) {
            $sa = $aioseo_options['searchAppearance'];

            // Home page title
            if (!empty($sa['global']['siteTitle'])) {
                update_option('nerdy_seo_home_title', $this->convert_aioseo_variables($sa['global']['siteTitle']));
            }

            // Home page description
            if (!empty($sa['global']['metaDescription'])) {
                update_option('nerdy_seo_home_description', $this->convert_aioseo_variables($sa['global']['metaDescription']));
            }

            // Separator
            if (!empty($sa['global']['separator'])) {
                update_option('nerdy_seo_separator', sanitize_text_field($sa['global']['separator']));
            }
        }

        // Migrate social settings
        if (isset($aioseo_options['social'])) {
            $social = $aioseo_options['social'];

            // Facebook
            if (!empty($social['facebook']['general']['enable'])) {
                update_option('nerdy_seo_og_enabled', true);
            }

            if (!empty($social['facebook']['general']['defaultImagePosts'])) {
                update_option('nerdy_seo_default_og_image', esc_url($social['facebook']['general']['defaultImagePosts']));
            }

            // Twitter
            if (!empty($social['twitter']['general']['enable'])) {
                update_option('nerdy_seo_twitter_enabled', true);
            }

            if (!empty($social['twitter']['general']['defaultCardType'])) {
                update_option('nerdy_seo_twitter_card_type', sanitize_text_field($social['twitter']['general']['defaultCardType']));
            }
        }
    }
}

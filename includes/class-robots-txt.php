<?php
/**
 * Robots.txt Editor
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Robots.txt class
 */
class Nerdy_SEO_Robots_Txt {

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
        // Hook into robots.txt generation
        add_filter('robots_txt', array($this, 'filter_robots_txt'), 10, 2);

        // Admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);

        // Handle form submission
        add_action('admin_init', array($this, 'handle_save'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'nerdy-seo',
            __('Robots.txt Editor', 'nerdy-seo'),
            __('Robots.txt', 'nerdy-seo'),
            'manage_options',
            'nerdy-seo-robots',
            array($this, 'render_page')
        );
    }

    /**
     * Filter robots.txt output
     */
    public function filter_robots_txt($output, $public) {
        // Check if custom robots.txt is enabled
        if (!get_option('nerdy_seo_robots_custom_enabled', false)) {
            return $output;
        }

        // Get custom content
        $custom_content = get_option('nerdy_seo_robots_custom_content', '');

        if (!empty($custom_content)) {
            return $custom_content;
        }

        return $output;
    }

    /**
     * Handle form save
     */
    public function handle_save() {
        if (!isset($_POST['nerdy_seo_robots_nonce'])) {
            return;
        }

        if (!wp_verify_nonce($_POST['nerdy_seo_robots_nonce'], 'nerdy_seo_robots_save')) {
            return;
        }

        if (!current_user_can('manage_options')) {
            return;
        }

        // Save enabled status
        $enabled = isset($_POST['nerdy_seo_robots_custom_enabled']) ? true : false;
        update_option('nerdy_seo_robots_custom_enabled', $enabled);

        // Save custom content
        if (isset($_POST['nerdy_seo_robots_custom_content'])) {
            $content = wp_kses_post(stripslashes($_POST['nerdy_seo_robots_custom_content']));
            update_option('nerdy_seo_robots_custom_content', $content);
        }

        // Set success message
        add_settings_error(
            'nerdy_seo_robots',
            'nerdy_seo_robots_saved',
            __('Robots.txt settings saved successfully!', 'nerdy-seo'),
            'success'
        );
    }

    /**
     * Get default robots.txt content
     */
    private function get_default_robots() {
        $site_url = parse_url(site_url());
        $path = (!empty($site_url['path'])) ? $site_url['path'] : '';

        $output = "User-agent: *\n";
        $output .= "Disallow: /wp-admin/\n";
        $output .= "Allow: /wp-admin/admin-ajax.php\n";
        $output .= "Disallow: /wp-includes/\n";
        $output .= "Disallow: /wp-content/plugins/\n";
        $output .= "Disallow: /wp-content/themes/\n";
        $output .= "Disallow: /readme.html\n";
        $output .= "Disallow: /license.txt\n";
        $output .= "\n";

        // Add sitemap if enabled
        if (get_option('nerdy_seo_sitemap_enabled', true)) {
            $output .= "Sitemap: " . home_url('/sitemap.xml') . "\n";
        }

        return $output;
    }

    /**
     * Render the page
     */
    public function render_page() {
        $enabled = get_option('nerdy_seo_robots_custom_enabled', false);
        $custom_content = get_option('nerdy_seo_robots_custom_content', '');

        // If no custom content set, use default
        if (empty($custom_content)) {
            $custom_content = $this->get_default_robots();
        }

        $robots_url = home_url('/robots.txt');
        ?>
        <div class="wrap">
            <h1><?php _e('Robots.txt Editor', 'nerdy-seo'); ?></h1>

            <?php settings_errors('nerdy_seo_robots'); ?>

            <div class="nerdy-seo-robots-header" style="background: #fff; padding: 20px; margin: 20px 0; border-left: 4px solid #2271b1;">
                <h2 style="margin-top: 0;"><?php _e('About Robots.txt', 'nerdy-seo'); ?></h2>
                <p>
                    <?php _e('The robots.txt file tells search engines which pages or files they can or can\'t request from your site. This is mainly used to avoid overloading your site with requests.', 'nerdy-seo'); ?>
                </p>
                <p>
                    <strong><?php _e('Current robots.txt URL:', 'nerdy-seo'); ?></strong>
                    <a href="<?php echo esc_url($robots_url); ?>" target="_blank"><?php echo esc_html($robots_url); ?></a>
                    <span class="dashicons dashicons-external" style="text-decoration: none;"></span>
                </p>
                <p class="description">
                    <?php _e('Note: If you have a physical robots.txt file in your WordPress root directory, it will override this virtual robots.txt.', 'nerdy-seo'); ?>
                </p>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('nerdy_seo_robots_save', 'nerdy_seo_robots_nonce'); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="nerdy_seo_robots_custom_enabled">
                                <?php _e('Custom Robots.txt', 'nerdy-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <label>
                                <input
                                    type="checkbox"
                                    id="nerdy_seo_robots_custom_enabled"
                                    name="nerdy_seo_robots_custom_enabled"
                                    value="1"
                                    <?php checked($enabled, true); ?>
                                />
                                <?php _e('Enable custom robots.txt', 'nerdy-seo'); ?>
                            </label>
                            <p class="description">
                                <?php _e('When disabled, WordPress will use its default robots.txt rules.', 'nerdy-seo'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="nerdy_seo_robots_custom_content">
                                <?php _e('Robots.txt Content', 'nerdy-seo'); ?>
                            </label>
                        </th>
                        <td>
                            <textarea
                                id="nerdy_seo_robots_custom_content"
                                name="nerdy_seo_robots_custom_content"
                                rows="20"
                                class="large-text code"
                                style="font-family: monospace; width: 100%; max-width: 800px;"
                            ><?php echo esc_textarea($custom_content); ?></textarea>
                            <p class="description">
                                <?php _e('Edit your robots.txt rules above. Use "User-agent:" to specify which bots the rules apply to, "Disallow:" to block access, and "Allow:" to permit access.', 'nerdy-seo'); ?>
                            </p>
                        </td>
                    </tr>
                </table>

                <div class="nerdy-seo-robots-examples" style="background: #f0f0f1; padding: 20px; margin: 20px 0; border-radius: 4px; max-width: 800px;">
                    <h3 style="margin-top: 0;"><?php _e('Common Examples', 'nerdy-seo'); ?></h3>

                    <h4><?php _e('Block All Bots', 'nerdy-seo'); ?></h4>
                    <pre style="background: #fff; padding: 10px; overflow-x: auto;">User-agent: *
Disallow: /</pre>

                    <h4><?php _e('Block Specific Directory', 'nerdy-seo'); ?></h4>
                    <pre style="background: #fff; padding: 10px; overflow-x: auto;">User-agent: *
Disallow: /private/
Disallow: /temp/</pre>

                    <h4><?php _e('Block Specific Bot', 'nerdy-seo'); ?></h4>
                    <pre style="background: #fff; padding: 10px; overflow-x: auto;">User-agent: BadBot
Disallow: /

User-agent: *
Disallow: /wp-admin/</pre>

                    <h4><?php _e('Crawl Delay', 'nerdy-seo'); ?></h4>
                    <pre style="background: #fff; padding: 10px; overflow-x: auto;">User-agent: *
Crawl-delay: 10
Disallow: /wp-admin/</pre>

                    <h4><?php _e('Reference Sitemap', 'nerdy-seo'); ?></h4>
                    <pre style="background: #fff; padding: 10px; overflow-x: auto;">User-agent: *
Disallow: /wp-admin/

Sitemap: <?php echo esc_url(home_url('/sitemap.xml')); ?></pre>
                </div>

                <p class="submit">
                    <button type="submit" class="button button-primary">
                        <?php _e('Save Changes', 'nerdy-seo'); ?>
                    </button>
                    <button type="button" class="button" id="nerdy-seo-reset-robots">
                        <?php _e('Reset to Default', 'nerdy-seo'); ?>
                    </button>
                </p>
            </form>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Reset to default
            $('#nerdy-seo-reset-robots').on('click', function() {
                if (confirm('<?php _e('Are you sure you want to reset to default robots.txt rules? This will overwrite your current content.', 'nerdy-seo'); ?>')) {
                    $('#nerdy_seo_robots_custom_content').val(<?php echo json_encode($this->get_default_robots()); ?>);
                }
            });

            // Toggle textarea based on checkbox
            $('#nerdy_seo_robots_custom_enabled').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#nerdy_seo_robots_custom_content').prop('disabled', false).css('opacity', '1');
                } else {
                    $('#nerdy_seo_robots_custom_content').prop('disabled', true).css('opacity', '0.5');
                }
            }).trigger('change');
        });
        </script>

        <style>
            .nerdy-seo-robots-examples h4 {
                margin-top: 15px;
                margin-bottom: 5px;
            }
            .nerdy-seo-robots-examples pre {
                margin-top: 5px;
                margin-bottom: 15px;
            }
        </style>
        <?php
    }
}

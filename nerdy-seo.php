<?php
/**
 * Plugin Name: Nerdy SEO
 * Plugin URI: https://nerdysouthinc.com
 * Description: Lightweight, powerful SEO plugin with title/meta management, social media integration, schema markup, and NestedPages support
 * Version: 1.0.0
 * Author: Nerdy South Inc
 * Author URI: https://nerdysouthinc.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: nerdy-seo
 * Domain Path: /languages
 * Requires at least: 5.5
 * Requires PHP: 7.4
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('NERDY_SEO_VERSION', '1.0.0');
define('NERDY_SEO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('NERDY_SEO_PLUGIN_URL', plugin_dir_url(__FILE__));
define('NERDY_SEO_PLUGIN_FILE', __FILE__);

/**
 * Main Nerdy_SEO class
 */
class Nerdy_SEO {

    /**
     * Single instance of the class
     */
    private static $instance = null;

    /**
     * Get singleton instance
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
        $this->includes();
        $this->init_hooks();
    }

    /**
     * Include required files
     */
    private function includes() {
        // Core functionality
        require_once NERDY_SEO_PLUGIN_DIR . 'includes/class-meta-box.php';
        require_once NERDY_SEO_PLUGIN_DIR . 'includes/class-frontend.php';
        require_once NERDY_SEO_PLUGIN_DIR . 'includes/class-social-meta.php';
        require_once NERDY_SEO_PLUGIN_DIR . 'includes/class-schema.php';
        require_once NERDY_SEO_PLUGIN_DIR . 'includes/class-settings.php';
        require_once NERDY_SEO_PLUGIN_DIR . 'includes/class-sitemap.php';
        require_once NERDY_SEO_PLUGIN_DIR . 'includes/class-redirects.php';
        require_once NERDY_SEO_PLUGIN_DIR . 'includes/class-local-seo.php';
        require_once NERDY_SEO_PLUGIN_DIR . 'includes/class-image-seo.php';
        require_once NERDY_SEO_PLUGIN_DIR . 'includes/class-breadcrumbs.php';
        require_once NERDY_SEO_PLUGIN_DIR . 'includes/class-woocommerce.php';
        require_once NERDY_SEO_PLUGIN_DIR . 'includes/class-robots-txt.php';

        // Local Business (needed on both frontend and admin)
        require_once NERDY_SEO_PLUGIN_DIR . 'includes/admin/class-local-business.php';

        // Admin only files
        if (is_admin()) {
            require_once NERDY_SEO_PLUGIN_DIR . 'includes/admin/class-admin.php';
            require_once NERDY_SEO_PLUGIN_DIR . 'includes/admin/class-nested-pages.php';
            require_once NERDY_SEO_PLUGIN_DIR . 'includes/admin/class-list-columns.php';
            require_once NERDY_SEO_PLUGIN_DIR . 'includes/class-migration.php';
        }
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation/Deactivation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));

        // Load plugin text domain
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        // Initialize components
        add_action('init', array($this, 'init'));
    }

    /**
     * Initialize plugin components
     */
    public function init() {
        // Initialize meta box
        Nerdy_SEO_Meta_Box::get_instance();

        // Initialize frontend output
        Nerdy_SEO_Frontend::get_instance();

        // Initialize social meta
        Nerdy_SEO_Social_Meta::get_instance();

        // Initialize schema
        Nerdy_SEO_Schema::get_instance();

        // Initialize settings
        Nerdy_SEO_Settings::get_instance();

        // Initialize sitemap
        Nerdy_SEO_Sitemap::get_instance();

        // Initialize redirects
        Nerdy_SEO_Redirects::get_instance();

        // Initialize local SEO
        Nerdy_SEO_Local_SEO::get_instance();

        // Initialize image SEO
        Nerdy_SEO_Image_SEO::get_instance();

        // Initialize breadcrumbs
        Nerdy_SEO_Breadcrumbs::get_instance();

        // Initialize robots.txt editor
        Nerdy_SEO_Robots_Txt::get_instance();

        // Initialize WooCommerce integration
        if (class_exists('WooCommerce')) {
            Nerdy_SEO_WooCommerce::get_instance();
        }

        // Initialize local business (frontend schema + admin page)
        Nerdy_SEO_Local_Business::get_instance();

        // Initialize admin features
        if (is_admin()) {
            Nerdy_SEO_Admin::get_instance();

            // Initialize list table columns
            Nerdy_SEO_List_Columns::get_instance();

            // Initialize NestedPages integration if plugin is active
            if (class_exists('NestedPages')) {
                Nerdy_SEO_Nested_Pages::get_instance();
            }

            // Initialize migration tool
            Nerdy_SEO_Migration::get_instance();
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Set default options
        $defaults = array(
            'default_title_format' => '%title% | %sitename%',
            'home_title' => get_bloginfo('name'),
            'home_description' => get_bloginfo('description'),
            'og_enabled' => true,
            'twitter_enabled' => true,
            'schema_enabled' => true,
            'sitemap_enabled' => true,
            'track_404s' => true,
        );

        foreach ($defaults as $key => $value) {
            if (get_option('nerdy_seo_' . $key) === false) {
                add_option('nerdy_seo_' . $key, $value);
            }
        }

        // Create redirect tables
        Nerdy_SEO_Redirects::create_tables();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain('nerdy-seo', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }
}

/**
 * Initialize the plugin
 */
function nerdy_seo() {
    return Nerdy_SEO::get_instance();
}

// Start the plugin
nerdy_seo();

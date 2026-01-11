<?php
/**
 * Admin functionality
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin class
 */
class Nerdy_SEO_Admin {

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
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Enqueue media uploader for post editor and settings page
        if (in_array($hook, array('post.php', 'post-new.php', 'toplevel_page_nerdy-seo'))) {
            wp_enqueue_media();
        }

        // Enqueue admin CSS on all admin pages
        $css_file = NERDY_SEO_PLUGIN_DIR . 'assets/css/admin.css';
        $version = file_exists($css_file) ? filemtime($css_file) : NERDY_SEO_VERSION;

        wp_enqueue_style(
            'nerdy-seo-admin',
            NERDY_SEO_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $version
        );
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('Nerdy SEO', 'nerdy-seo'),
            __('Nerdy SEO', 'nerdy-seo'),
            'manage_options',
            'nerdy-seo',
            array($this, 'render_settings_page'),
            'dashicons-search',
            65
        );

        // Rename first submenu to "Settings" (removes duplicate "Nerdy SEO")
        add_submenu_page(
            'nerdy-seo',
            __('General Settings', 'nerdy-seo'),
            __('Settings', 'nerdy-seo'),
            'manage_options',
            'nerdy-seo',
            array($this, 'render_settings_page')
        );
    }

    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Save settings if form submitted
        if (isset($_POST['nerdy_seo_save_settings'])) {
            check_admin_referer('nerdy_seo_settings_nonce');

            // Save each setting
            $settings = array(
                'nerdy_seo_home_title',
                'nerdy_seo_home_description',
                'nerdy_seo_default_title_format',
                'nerdy_seo_separator',
                'nerdy_seo_og_enabled',
                'nerdy_seo_twitter_enabled',
                'nerdy_seo_twitter_site',
                'nerdy_seo_default_og_image',
                'nerdy_seo_schema_enabled',
                'nerdy_seo_sitemap_enabled',
                'nerdy_seo_ai_provider',
                'nerdy_seo_ai_openai_key',
                'nerdy_seo_ai_openai_model',
                'nerdy_seo_ai_gemini_key',
                'nerdy_seo_ai_gemini_model',
            );

            // Handle global schema separately (it's a textarea)
            if (isset($_POST['nerdy_seo_global_schema'])) {
                update_option('nerdy_seo_global_schema', wp_kses_post($_POST['nerdy_seo_global_schema']));
            }

            // Handle content type settings
            $post_types = get_post_types(array('public' => true), 'names');
            foreach ($post_types as $post_type) {
                // Title format
                if (isset($_POST["nerdy_seo_pt_{$post_type}_title"])) {
                    update_option("nerdy_seo_pt_{$post_type}_title", sanitize_text_field($_POST["nerdy_seo_pt_{$post_type}_title"]));
                }
                // Description format
                if (isset($_POST["nerdy_seo_pt_{$post_type}_description"])) {
                    update_option("nerdy_seo_pt_{$post_type}_description", sanitize_textarea_field($_POST["nerdy_seo_pt_{$post_type}_description"]));
                }
                // Noindex
                if (isset($_POST["nerdy_seo_pt_{$post_type}_noindex"])) {
                    update_option("nerdy_seo_pt_{$post_type}_noindex", false); // Checked means show (not noindex)
                } else {
                    update_option("nerdy_seo_pt_{$post_type}_noindex", true); // Unchecked means hide (noindex)
                }
            }

            foreach ($settings as $setting) {
                if (isset($_POST[$setting])) {
                    update_option($setting, sanitize_text_field($_POST[$setting]));
                } else {
                    // Handle checkboxes that aren't checked
                    if (strpos($setting, '_enabled') !== false) {
                        update_option($setting, false);
                    }
                }
            }

            // Handle sitemap exclusions (arrays)
            if (isset($_POST['nerdy_seo_sitemap_exclude_post_types'])) {
                $excluded_types = array_map('sanitize_text_field', $_POST['nerdy_seo_sitemap_exclude_post_types']);
                update_option('nerdy_seo_sitemap_exclude_post_types', $excluded_types);
            } else {
                update_option('nerdy_seo_sitemap_exclude_post_types', array());
            }

            if (isset($_POST['nerdy_seo_sitemap_exclude_taxonomies'])) {
                $excluded_taxonomies = array_map('sanitize_text_field', $_POST['nerdy_seo_sitemap_exclude_taxonomies']);
                update_option('nerdy_seo_sitemap_exclude_taxonomies', $excluded_taxonomies);
            } else {
                update_option('nerdy_seo_sitemap_exclude_taxonomies', array());
            }

            echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'nerdy-seo') . '</p></div>';
        }

        // Get current values
        $home_title = get_option('nerdy_seo_home_title', get_bloginfo('name'));
        $home_description = get_option('nerdy_seo_home_description', get_bloginfo('description'));
        $default_title_format = get_option('nerdy_seo_default_title_format', '%title% | %sitename%');
        $separator = get_option('nerdy_seo_separator', '|');
        $og_enabled = get_option('nerdy_seo_og_enabled', true);
        $twitter_enabled = get_option('nerdy_seo_twitter_enabled', true);
        $twitter_site = get_option('nerdy_seo_twitter_site', '');
        $default_og_image = get_option('nerdy_seo_default_og_image', '');
        $schema_enabled = get_option('nerdy_seo_schema_enabled', true);
        $global_schema = get_option('nerdy_seo_global_schema', '');

        // AI settings
        $ai_provider = get_option('nerdy_seo_ai_provider', 'openai');
        $ai_openai_key = get_option('nerdy_seo_ai_openai_key', '');
        $ai_openai_model = get_option('nerdy_seo_ai_openai_model', 'gpt-4o');
        $ai_gemini_key = get_option('nerdy_seo_ai_gemini_key', '');
        $ai_gemini_model = get_option('nerdy_seo_ai_gemini_model', 'gemini-2.0-flash-exp');

        ?>
        <div class="wrap nerdy-seo-settings-wrap">
            <h1 class="nerdy-seo-page-title">
                <span class="dashicons dashicons-search"></span>
                <?php _e('Nerdy SEO Settings', 'nerdy-seo'); ?>
            </h1>

            <div class="nerdy-seo-tabs-wrapper">
                <!-- Tab Navigation -->
                <div class="nerdy-seo-tabs-nav">
                    <button type="button" class="nerdy-seo-tab-btn active" data-tab="general">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <?php _e('General', 'nerdy-seo'); ?>
                    </button>
                    <button type="button" class="nerdy-seo-tab-btn" data-tab="content-types">
                        <span class="dashicons dashicons-admin-post"></span>
                        <?php _e('Content Types', 'nerdy-seo'); ?>
                    </button>
                    <button type="button" class="nerdy-seo-tab-btn" data-tab="social">
                        <span class="dashicons dashicons-share"></span>
                        <?php _e('Social Media', 'nerdy-seo'); ?>
                    </button>
                    <button type="button" class="nerdy-seo-tab-btn" data-tab="schema">
                        <span class="dashicons dashicons-editor-code"></span>
                        <?php _e('Schema', 'nerdy-seo'); ?>
                    </button>
                    <button type="button" class="nerdy-seo-tab-btn" data-tab="ai">
                        <span class="dashicons dashicons-superhero"></span>
                        <?php _e('AI', 'nerdy-seo'); ?>
                    </button>
                    <button type="button" class="nerdy-seo-tab-btn" data-tab="sitemap">
                        <span class="dashicons dashicons-admin-site-alt3"></span>
                        <?php _e('Sitemap', 'nerdy-seo'); ?>
                    </button>
                    <button type="button" class="nerdy-seo-tab-btn" data-tab="advanced">
                        <span class="dashicons dashicons-admin-tools"></span>
                        <?php _e('Advanced', 'nerdy-seo'); ?>
                    </button>
                </div>

                <!-- Form -->
                <form method="post" action="">
                    <?php wp_nonce_field('nerdy_seo_settings_nonce'); ?>

                    <!-- General Tab -->
                    <div class="nerdy-seo-tab-content active" data-tab="general">
                        <div class="nerdy-seo-settings-card nerdy-seo-content-type-card">
                            <h2><?php _e('Homepage SEO', 'nerdy-seo'); ?></h2>
                            <p class="description"><?php _e('Configure the SEO settings for your homepage.', 'nerdy-seo'); ?></p>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_home_title"><?php _e('Homepage Title', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <div class="nerdy-seo-variable-field-wrapper">
                                            <input
                                                type="text"
                                                id="nerdy_seo_home_title"
                                                name="nerdy_seo_home_title"
                                                value="<?php echo esc_attr($home_title); ?>"
                                                class="large-text nerdy-seo-variable-input"
                                                data-field-type="title"
                                                placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>"
                                            />
                                            <div class="nerdy-seo-variable-buttons">
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%sitename%">
                                                    <span class="dashicons dashicons-plus-alt2"></span> <?php _e('Site Title', 'nerdy-seo'); ?>
                                                </button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%sitedesc%">
                                                    <span class="dashicons dashicons-plus-alt2"></span> <?php _e('Site Description', 'nerdy-seo'); ?>
                                                </button>
                                                <a href="#" class="nerdy-seo-show-all-vars"><?php _e('View all tags →', 'nerdy-seo'); ?></a>
                                            </div>
                                            <div class="nerdy-seo-all-variables" style="display: none;">
                                                <p><strong><?php _e('Click to insert:', 'nerdy-seo'); ?></strong></p>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%sitename%">%sitename%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%sitedesc%">%sitedesc%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%separator%">%separator%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%year%">%year%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%month%">%month%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%day%">%day%</button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_home_description"><?php _e('Homepage Description', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <div class="nerdy-seo-variable-field-wrapper">
                                            <textarea
                                                id="nerdy_seo_home_description"
                                                name="nerdy_seo_home_description"
                                                rows="3"
                                                class="large-text nerdy-seo-variable-input"
                                                data-field-type="description"
                                                placeholder="<?php echo esc_attr(get_bloginfo('description')); ?>"
                                            ><?php echo esc_textarea($home_description); ?></textarea>
                                            <div class="nerdy-seo-variable-buttons">
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%sitename%">
                                                    <span class="dashicons dashicons-plus-alt2"></span> <?php _e('Site Title', 'nerdy-seo'); ?>
                                                </button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%sitedesc%">
                                                    <span class="dashicons dashicons-plus-alt2"></span> <?php _e('Site Description', 'nerdy-seo'); ?>
                                                </button>
                                                <a href="#" class="nerdy-seo-show-all-vars"><?php _e('View all tags →', 'nerdy-seo'); ?></a>
                                            </div>
                                            <div class="nerdy-seo-all-variables" style="display: none;">
                                                <p><strong><?php _e('Click to insert:', 'nerdy-seo'); ?></strong></p>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%sitename%">%sitename%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%sitedesc%">%sitedesc%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%separator%">%separator%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%year%">%year%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%month%">%month%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%day%">%day%</button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Google Search Preview for Homepage -->
                            <div class="nerdy-seo-search-preview">
                                <h4><?php _e('Preview', 'nerdy-seo'); ?></h4>
                                <div class="nerdy-seo-serp-preview">
                                    <div class="nerdy-seo-serp-favicon">
                                        <?php if (has_site_icon()): ?>
                                            <img src="<?php echo esc_url(get_site_icon_url(32)); ?>" alt="" style="width: 26px; height: 26px; border-radius: 50%;" />
                                        <?php else: ?>
                                            <span class="dashicons dashicons-admin-home"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="nerdy-seo-serp-content">
                                        <div class="nerdy-seo-serp-site"><?php echo esc_html(parse_url(home_url(), PHP_URL_HOST)); ?></div>
                                        <div class="nerdy-seo-serp-title" data-template="<?php echo esc_attr($home_title); ?>">
                                            <?php
                                            $preview_title = str_replace(
                                                array('%sitename%', '%sitedesc%', '%separator%', '%year%', '%month%', '%day%'),
                                                array(get_bloginfo('name'), get_bloginfo('description'), '|', date('Y'), date('F'), date('j')),
                                                $home_title
                                            );
                                            echo esc_html($preview_title);
                                            ?>
                                        </div>
                                        <div class="nerdy-seo-serp-description" data-template="<?php echo esc_attr($home_description); ?>">
                                            <?php
                                            $preview_desc = str_replace(
                                                array('%sitename%', '%sitedesc%', '%separator%', '%year%', '%month%', '%day%'),
                                                array(get_bloginfo('name'), get_bloginfo('description'), '|', date('Y'), date('F'), date('j')),
                                                $home_description
                                            );
                                            echo esc_html($preview_desc);
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="nerdy-seo-settings-card">
                            <h2><?php _e('Title Settings', 'nerdy-seo'); ?></h2>
                            <p class="description"><?php _e('Customize how page titles are formatted across your site.', 'nerdy-seo'); ?></p>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_separator"><?php _e('Title Separator', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <input
                                            type="text"
                                            id="nerdy_seo_separator"
                                            name="nerdy_seo_separator"
                                            value="<?php echo esc_attr($separator); ?>"
                                            class="small-text"
                                            placeholder="|"
                                            style="max-width: 80px;"
                                        />
                                        <p class="description">
                                            <?php _e('The character used to separate parts of your page titles. Common choices: | - • · ›', 'nerdy-seo'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_default_title_format"><?php _e('Default Title Format', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <div class="nerdy-seo-variable-field-wrapper">
                                            <input
                                                type="text"
                                                id="nerdy_seo_default_title_format"
                                                name="nerdy_seo_default_title_format"
                                                value="<?php echo esc_attr($default_title_format); ?>"
                                                class="large-text nerdy-seo-variable-input"
                                                data-field-type="title"
                                                placeholder="%title% %separator% %sitename%"
                                            />
                                            <div class="nerdy-seo-variable-buttons">
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%title%">
                                                    <span class="dashicons dashicons-plus-alt2"></span> <?php _e('Post Title', 'nerdy-seo'); ?>
                                                </button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%separator%">
                                                    <span class="dashicons dashicons-plus-alt2"></span> <?php _e('Separator', 'nerdy-seo'); ?>
                                                </button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%sitename%">
                                                    <span class="dashicons dashicons-plus-alt2"></span> <?php _e('Site Title', 'nerdy-seo'); ?>
                                                </button>
                                                <a href="#" class="nerdy-seo-show-all-vars"><?php _e('View all tags →', 'nerdy-seo'); ?></a>
                                            </div>
                                            <div class="nerdy-seo-all-variables" style="display: none;">
                                                <p><strong><?php _e('Click to insert:', 'nerdy-seo'); ?></strong></p>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%title%">%title%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%sitename%">%sitename%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%sitedesc%">%sitedesc%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%separator%">%separator%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%excerpt%">%excerpt%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%author%">%author%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%date%">%date%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%year%">%year%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%month%">%month%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%day%">%day%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%categories%">%categories%</button>
                                                <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%tags%">%tags%</button>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            <!-- Google Search Preview for Default Title -->
                            <div class="nerdy-seo-search-preview">
                                <h4><?php _e('Preview', 'nerdy-seo'); ?></h4>
                                <div class="nerdy-seo-serp-preview">
                                    <div class="nerdy-seo-serp-favicon">
                                        <?php if (has_site_icon()): ?>
                                            <img src="<?php echo esc_url(get_site_icon_url(32)); ?>" alt="" style="width: 26px; height: 26px; border-radius: 50%;" />
                                        <?php else: ?>
                                            <span class="dashicons dashicons-admin-home"></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="nerdy-seo-serp-content">
                                        <div class="nerdy-seo-serp-site"><?php echo esc_html(parse_url(home_url(), PHP_URL_HOST)); ?></div>
                                        <div class="nerdy-seo-serp-title" data-template="<?php echo esc_attr($default_title_format); ?>">
                                            <?php
                                            $preview_default_title = str_replace(
                                                array('%title%', '%sitename%', '%sitedesc%', '%separator%', '%excerpt%', '%author%', '%date%', '%year%', '%month%', '%day%', '%categories%', '%tags%'),
                                                array('Sample Page Title', get_bloginfo('name'), get_bloginfo('description'), $separator, 'Sample excerpt', 'Author Name', date('F j, Y'), date('Y'), date('F'), date('j'), 'Category 1, Category 2', 'Tag 1, Tag 2'),
                                                $default_title_format
                                            );
                                            echo esc_html($preview_default_title);
                                            ?>
                                        </div>
                                        <div class="nerdy-seo-serp-description" data-template="">
                                            <?php _e('This is how your page titles will appear in search results when using the default format.', 'nerdy-seo'); ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="nerdy-seo-settings-card" style="display: none;">
                            <table class="form-table">
                            </table>
                        </div>
                    </div>

                    <!-- Content Types Tab -->
                    <div class="nerdy-seo-tab-content" data-tab="content-types">
                        <?php
                        // Get all public post types
                        $post_types = get_post_types(array('public' => true), 'objects');

                        foreach ($post_types as $post_type) {
                            $slug = $post_type->name;
                            $noindex = get_option("nerdy_seo_pt_{$slug}_noindex", false);
                            $title_format = get_option("nerdy_seo_pt_{$slug}_title", '%title% %separator% %sitename%');
                            $desc_format = get_option("nerdy_seo_pt_{$slug}_description", '%excerpt%');
                            ?>
                            <div class="nerdy-seo-settings-card nerdy-seo-content-type-card">
                                <div class="nerdy-seo-content-type-header">
                                    <h2>
                                        <?php
                                        $icon = $post_type->menu_icon ?: 'dashicons-admin-post';
                                        // If icon already has dashicons- prefix, use it as is, otherwise add the class
                                        if (strpos($icon, 'dashicons-') === 0) {
                                            echo '<span class="dashicons ' . esc_attr($icon) . '"></span>';
                                        } else {
                                            echo '<span class="dashicons dashicons-admin-post"></span>';
                                        }
                                        ?>
                                        <?php echo esc_html($post_type->labels->name); ?>
                                        <span class="nerdy-seo-post-type-slug">(<?php echo esc_html($slug); ?>)</span>
                                    </h2>
                                    <label class="nerdy-seo-toggle" title="<?php _e('Show in Search Results', 'nerdy-seo'); ?>">
                                        <input
                                            type="checkbox"
                                            name="nerdy_seo_pt_<?php echo esc_attr($slug); ?>_noindex"
                                            value="1"
                                            <?php checked($noindex, false); ?>
                                        />
                                        <span class="nerdy-seo-toggle-slider"></span>
                                    </label>
                                </div>

                                <table class="form-table">
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Title Template', 'nerdy-seo'); ?></label>
                                        </th>
                                        <td>
                                            <div class="nerdy-seo-variable-field-wrapper">
                                                <input
                                                    type="text"
                                                    name="nerdy_seo_pt_<?php echo esc_attr($slug); ?>_title"
                                                    value="<?php echo esc_attr($title_format); ?>"
                                                    class="large-text nerdy-seo-variable-input"
                                                    data-field-type="title"
                                                />
                                                <div class="nerdy-seo-variable-buttons">
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%title%">
                                                        <span class="dashicons dashicons-plus-alt2"></span> <?php _e('Post Title', 'nerdy-seo'); ?>
                                                    </button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%separator%">
                                                        <span class="dashicons dashicons-plus-alt2"></span> <?php _e('Separator', 'nerdy-seo'); ?>
                                                    </button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%sitename%">
                                                        <span class="dashicons dashicons-plus-alt2"></span> <?php _e('Site Title', 'nerdy-seo'); ?>
                                                    </button>
                                                    <a href="#" class="nerdy-seo-show-all-vars"><?php _e('View all tags →', 'nerdy-seo'); ?></a>
                                                </div>
                                                <div class="nerdy-seo-all-variables" style="display: none;">
                                                    <p><strong><?php _e('Click to insert:', 'nerdy-seo'); ?></strong></p>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%title%">%title%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%sitename%">%sitename%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%sitedesc%">%sitedesc%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%separator%">%separator%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%excerpt%">%excerpt%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%author%">%author%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%date%">%date%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%year%">%year%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%month%">%month%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%day%">%day%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%categories%">%categories%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%tags%">%tags%</button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">
                                            <label><?php _e('Meta Description Template', 'nerdy-seo'); ?></label>
                                        </th>
                                        <td>
                                            <div class="nerdy-seo-variable-field-wrapper">
                                                <textarea
                                                    name="nerdy_seo_pt_<?php echo esc_attr($slug); ?>_description"
                                                    rows="3"
                                                    class="large-text nerdy-seo-variable-input"
                                                    data-field-type="description"
                                                ><?php echo esc_textarea($desc_format); ?></textarea>
                                                <div class="nerdy-seo-variable-buttons">
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%excerpt%">
                                                        <span class="dashicons dashicons-plus-alt2"></span> <?php _e('Post Excerpt', 'nerdy-seo'); ?>
                                                    </button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%title%">
                                                        <span class="dashicons dashicons-plus-alt2"></span> <?php _e('Post Title', 'nerdy-seo'); ?>
                                                    </button>
                                                    <a href="#" class="nerdy-seo-show-all-vars"><?php _e('View all tags →', 'nerdy-seo'); ?></a>
                                                </div>
                                                <div class="nerdy-seo-all-variables" style="display: none;">
                                                    <p><strong><?php _e('Click to insert:', 'nerdy-seo'); ?></strong></p>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%title%">%title%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%sitename%">%sitename%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%sitedesc%">%sitedesc%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%separator%">%separator%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%excerpt%">%excerpt%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%author%">%author%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%date%">%date%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%year%">%year%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%month%">%month%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%day%">%day%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%categories%">%categories%</button>
                                                    <button type="button" class="button button-small nerdy-seo-insert-var" data-var="%tags%">%tags%</button>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </table>

                                <!-- Google Search Preview -->
                                <div class="nerdy-seo-search-preview">
                                    <h4><?php _e('Preview', 'nerdy-seo'); ?></h4>
                                    <div class="nerdy-seo-serp-preview">
                                        <div class="nerdy-seo-serp-favicon">
                                            <?php if (has_site_icon()): ?>
                                                <img src="<?php echo esc_url(get_site_icon_url(32)); ?>" alt="" style="width: 26px; height: 26px; border-radius: 50%;" />
                                            <?php else: ?>
                                                <span class="dashicons dashicons-admin-home"></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="nerdy-seo-serp-content">
                                            <div class="nerdy-seo-serp-site"><?php echo esc_html(parse_url(home_url(), PHP_URL_HOST)); ?></div>
                                            <div class="nerdy-seo-serp-title" data-template="<?php echo esc_attr($title_format); ?>">
                                                <?php echo esc_html($this->preview_template($title_format, $post_type)); ?>
                                            </div>
                                            <div class="nerdy-seo-serp-description" data-template="<?php echo esc_attr($desc_format); ?>">
                                                <?php echo esc_html($this->preview_template($desc_format, $post_type)); ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>

                    <!-- Social Tab -->
                    <div class="nerdy-seo-tab-content" data-tab="social">
                        <div class="nerdy-seo-settings-card">
                            <h2><?php _e('Open Graph (Facebook)', 'nerdy-seo'); ?></h2>
                            <p class="description"><?php _e('Control how your content appears when shared on Facebook, LinkedIn, and other platforms.', 'nerdy-seo'); ?></p>

                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Enable Open Graph', 'nerdy-seo'); ?></th>
                                    <td>
                                        <label class="nerdy-seo-toggle">
                                            <input
                                                type="checkbox"
                                                name="nerdy_seo_og_enabled"
                                                value="1"
                                                <?php checked($og_enabled, true); ?>
                                            />
                                            <span class="nerdy-seo-toggle-slider"></span>
                                        </label>
                                        <p class="description"><?php _e('Add Open Graph meta tags to your pages.', 'nerdy-seo'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="nerdy-seo-settings-card">
                            <h2><?php _e('Twitter Cards', 'nerdy-seo'); ?></h2>
                            <p class="description"><?php _e('Optimize how your content appears when shared on Twitter.', 'nerdy-seo'); ?></p>

                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Enable Twitter Cards', 'nerdy-seo'); ?></th>
                                    <td>
                                        <label class="nerdy-seo-toggle">
                                            <input
                                                type="checkbox"
                                                name="nerdy_seo_twitter_enabled"
                                                value="1"
                                                <?php checked($twitter_enabled, true); ?>
                                            />
                                            <span class="nerdy-seo-toggle-slider"></span>
                                        </label>
                                        <p class="description"><?php _e('Add Twitter Card meta tags to your pages.', 'nerdy-seo'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_twitter_site"><?php _e('Twitter Username', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <input
                                            type="text"
                                            id="nerdy_seo_twitter_site"
                                            name="nerdy_seo_twitter_site"
                                            value="<?php echo esc_attr($twitter_site); ?>"
                                            class="regular-text"
                                            placeholder="@yourusername"
                                        />
                                        <p class="description"><?php _e('Your Twitter username (include the @ symbol).', 'nerdy-seo'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="nerdy-seo-settings-card">
                            <h2><?php _e('Default Social Image', 'nerdy-seo'); ?></h2>
                            <p class="description"><?php _e('Fallback image for social sharing when no featured image is set.', 'nerdy-seo'); ?></p>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_default_og_image"><?php _e('Image URL', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <div class="nerdy-seo-image-upload">
                                            <input
                                                type="text"
                                                id="nerdy_seo_default_og_image"
                                                name="nerdy_seo_default_og_image"
                                                value="<?php echo esc_url($default_og_image); ?>"
                                                class="large-text"
                                            />
                                            <button
                                                type="button"
                                                class="button nerdy-seo-upload-btn"
                                                data-target="nerdy_seo_default_og_image"
                                            >
                                                <?php _e('Choose Image', 'nerdy-seo'); ?>
                                            </button>
                                        </div>
                                        <?php if ($default_og_image): ?>
                                            <div class="nerdy-seo-image-preview">
                                                <img src="<?php echo esc_url($default_og_image); ?>" alt="" />
                                            </div>
                                        <?php endif; ?>
                                        <p class="description"><?php _e('Recommended size: 1200x630 pixels. This will be used when no featured image is available.', 'nerdy-seo'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Schema Tab -->
                    <div class="nerdy-seo-tab-content" data-tab="schema">
                        <div class="nerdy-seo-settings-card">
                            <h2><?php _e('Schema Markup (Structured Data)', 'nerdy-seo'); ?></h2>
                            <p class="description"><?php _e('Schema markup helps search engines understand your content better, leading to rich snippets in search results.', 'nerdy-seo'); ?></p>

                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Enable Schema Markup', 'nerdy-seo'); ?></th>
                                    <td>
                                        <label class="nerdy-seo-toggle">
                                            <input
                                                type="checkbox"
                                                name="nerdy_seo_schema_enabled"
                                                value="1"
                                                <?php checked($schema_enabled, true); ?>
                                            />
                                            <span class="nerdy-seo-toggle-slider"></span>
                                        </label>
                                        <p class="description"><?php _e('Automatically add JSON-LD structured data to your pages.', 'nerdy-seo'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="nerdy-seo-settings-card">
                            <h2><?php _e('Global Schema Templates', 'nerdy-seo'); ?></h2>
                            <p class="description"><?php _e('Add schema markup that appears on every page of your site. Enter valid JSON-LD format.', 'nerdy-seo'); ?></p>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_global_schema"><?php _e('Global Schema JSON', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <textarea
                                            id="nerdy_seo_global_schema"
                                            name="nerdy_seo_global_schema"
                                            rows="15"
                                            class="large-text code"
                                            placeholder='{
  "@type": "Product",
  "name": "Internet Marketing Services",
  "image": "",
  "description": "Your company description here.",
  "aggregateRating": {
    "@type": "AggregateRating",
    "ratingValue": "5.0",
    "bestRating": "5",
    "worstRating": "1",
    "ratingCount": "43"
  }
}'
                                            style="font-family: 'Courier New', monospace; font-size: 13px;"
                                        ><?php echo esc_textarea($global_schema); ?></textarea>
                                        <p class="description">
                                            <strong><?php _e('Important:', 'nerdy-seo'); ?></strong> <?php _e('This schema will be added to EVERY page on your site. Make sure it\'s valid JSON format. Do not include the outer @context or @graph wrapper - just the object itself.', 'nerdy-seo'); ?>
                                            <br><br>
                                            <strong><?php _e('Example:', 'nerdy-seo'); ?></strong> Product schema, Organization schema, or any other schema type you want globally.
                                            <br><br>
                                            <a href="https://schema.org/" target="_blank"><?php _e('View all schema types at schema.org', 'nerdy-seo'); ?></a> |
                                            <a href="https://validator.schema.org/" target="_blank"><?php _e('Validate your schema', 'nerdy-seo'); ?></a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="nerdy-seo-info-box">
                            <h3><?php _e('Available Per-Page Schema Types', 'nerdy-seo'); ?></h3>
                            <p><?php _e('You can also add custom schema to individual pages via the post editor:', 'nerdy-seo'); ?></p>
                            <ul class="nerdy-seo-feature-list">
                                <li><span class="dashicons dashicons-yes-alt"></span> <?php _e('Article (automatic for blog posts)', 'nerdy-seo'); ?></li>
                                <li><span class="dashicons dashicons-yes-alt"></span> <?php _e('FAQ Schema (add via post editor)', 'nerdy-seo'); ?></li>
                                <li><span class="dashicons dashicons-yes-alt"></span> <?php _e('Review Schema (add via post editor)', 'nerdy-seo'); ?></li>
                                <li><span class="dashicons dashicons-yes-alt"></span> <?php _e('Product Schema (WooCommerce integration)', 'nerdy-seo'); ?></li>
                                <li><span class="dashicons dashicons-yes-alt"></span> <?php _e('Local Business Schema', 'nerdy-seo'); ?></li>
                                <li><span class="dashicons dashicons-yes-alt"></span> <?php _e('Breadcrumb Schema', 'nerdy-seo'); ?></li>
                            </ul>
                        </div>
                    </div>

                    <!-- AI Tab -->
                    <div class="nerdy-seo-tab-content" data-tab="ai">
                        <div class="nerdy-seo-settings-card">
                            <h2>
                                <span class="dashicons dashicons-superhero"></span>
                                <?php _e('AI Configuration', 'nerdy-seo'); ?>
                            </h2>
                            <p class="description"><?php _e('Configure AI providers to help generate SEO-optimized content like titles and meta descriptions.', 'nerdy-seo'); ?></p>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_ai_provider"><?php _e('AI Provider', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <select
                                            id="nerdy_seo_ai_provider"
                                            name="nerdy_seo_ai_provider"
                                            class="regular-text"
                                        >
                                            <option value="openai" <?php selected($ai_provider, 'openai'); ?>><?php _e('OpenAI', 'nerdy-seo'); ?></option>
                                            <option value="gemini" <?php selected($ai_provider, 'gemini'); ?>><?php _e('Google Gemini', 'nerdy-seo'); ?></option>
                                        </select>
                                        <p class="description">
                                            <?php _e('Choose which AI provider to use for content generation.', 'nerdy-seo'); ?>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- OpenAI Settings -->
                        <div class="nerdy-seo-settings-card nerdy-seo-ai-provider-settings" data-provider="openai" style="<?php echo $ai_provider === 'openai' ? '' : 'display:none;'; ?>">
                            <h2>
                                <span class="dashicons dashicons-admin-settings"></span>
                                <?php _e('OpenAI Settings', 'nerdy-seo'); ?>
                            </h2>
                            <p class="description">
                                <?php _e('Configure your OpenAI API settings.', 'nerdy-seo'); ?>
                                <a href="https://platform.openai.com/api-keys" target="_blank"><?php _e('Get your API key', 'nerdy-seo'); ?></a>
                            </p>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_ai_openai_key"><?php _e('API Key', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <input
                                            type="password"
                                            id="nerdy_seo_ai_openai_key"
                                            name="nerdy_seo_ai_openai_key"
                                            value="<?php echo esc_attr($ai_openai_key); ?>"
                                            class="large-text"
                                            placeholder="sk-..."
                                            autocomplete="off"
                                        />
                                        <p class="description">
                                            <?php _e('Your OpenAI API key. Keep this secure and never share it publicly.', 'nerdy-seo'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_ai_openai_model"><?php _e('Model', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <select
                                            id="nerdy_seo_ai_openai_model"
                                            name="nerdy_seo_ai_openai_model"
                                            class="regular-text"
                                        >
                                            <option value="gpt-4o" <?php selected($ai_openai_model, 'gpt-4o'); ?>>GPT-4o</option>
                                            <option value="gpt-4o-mini" <?php selected($ai_openai_model, 'gpt-4o-mini'); ?>>GPT-4o Mini</option>
                                            <option value="gpt-4-turbo" <?php selected($ai_openai_model, 'gpt-4-turbo'); ?>>GPT-4 Turbo</option>
                                            <option value="gpt-4" <?php selected($ai_openai_model, 'gpt-4'); ?>>GPT-4</option>
                                            <option value="gpt-3.5-turbo" <?php selected($ai_openai_model, 'gpt-3.5-turbo'); ?>>GPT-3.5 Turbo</option>
                                        </select>
                                        <p class="description">
                                            <?php _e('Choose the OpenAI model to use. GPT-4o is recommended for best results.', 'nerdy-seo'); ?>
                                            <br>
                                            <strong><?php _e('Cost per 1M tokens (input/output):', 'nerdy-seo'); ?></strong>
                                            GPT-4o: $2.50/$10.00 | GPT-4o Mini: $0.15/$0.60 | GPT-4 Turbo: $10/$30
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Gemini Settings -->
                        <div class="nerdy-seo-settings-card nerdy-seo-ai-provider-settings" data-provider="gemini" style="<?php echo $ai_provider === 'gemini' ? '' : 'display:none;'; ?>">
                            <h2>
                                <span class="dashicons dashicons-admin-settings"></span>
                                <?php _e('Google Gemini Settings', 'nerdy-seo'); ?>
                            </h2>
                            <p class="description">
                                <?php _e('Configure your Google Gemini API settings.', 'nerdy-seo'); ?>
                                <a href="https://makersuite.google.com/app/apikey" target="_blank"><?php _e('Get your API key', 'nerdy-seo'); ?></a>
                            </p>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_ai_gemini_key"><?php _e('API Key', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <input
                                            type="password"
                                            id="nerdy_seo_ai_gemini_key"
                                            name="nerdy_seo_ai_gemini_key"
                                            value="<?php echo esc_attr($ai_gemini_key); ?>"
                                            class="large-text"
                                            placeholder="AIza..."
                                            autocomplete="off"
                                        />
                                        <p class="description">
                                            <?php _e('Your Google Gemini API key. Keep this secure and never share it publicly.', 'nerdy-seo'); ?>
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_ai_gemini_model"><?php _e('Model', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <select
                                            id="nerdy_seo_ai_gemini_model"
                                            name="nerdy_seo_ai_gemini_model"
                                            class="regular-text"
                                        >
                                            <option value="gemini-2.0-flash-exp" <?php selected($ai_gemini_model, 'gemini-2.0-flash-exp'); ?>>Gemini 2.0 Flash (Experimental)</option>
                                            <option value="gemini-1.5-pro" <?php selected($ai_gemini_model, 'gemini-1.5-pro'); ?>>Gemini 1.5 Pro</option>
                                            <option value="gemini-1.5-flash" <?php selected($ai_gemini_model, 'gemini-1.5-flash'); ?>>Gemini 1.5 Flash</option>
                                            <option value="gemini-1.5-flash-8b" <?php selected($ai_gemini_model, 'gemini-1.5-flash-8b'); ?>>Gemini 1.5 Flash-8B</option>
                                        </select>
                                        <p class="description">
                                            <?php _e('Choose the Gemini model to use. Gemini 2.0 Flash is recommended for best results.', 'nerdy-seo'); ?>
                                            <br>
                                            <strong><?php _e('Cost per 1M tokens (input/output):', 'nerdy-seo'); ?></strong>
                                            2.0 Flash: Free during experimental | 1.5 Pro: $1.25/$5.00 | 1.5 Flash: $0.075/$0.30 | 1.5 Flash-8B: $0.0375/$0.15
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="nerdy-seo-info-box">
                            <h3><?php _e('AI Features', 'nerdy-seo'); ?></h3>
                            <p><?php _e('Once configured, AI will be available for:', 'nerdy-seo'); ?></p>
                            <ul class="nerdy-seo-feature-list">
                                <li><span class="dashicons dashicons-yes-alt"></span> <?php _e('Generate SEO-optimized titles', 'nerdy-seo'); ?></li>
                                <li><span class="dashicons dashicons-yes-alt"></span> <?php _e('Generate meta descriptions', 'nerdy-seo'); ?></li>
                                <li><span class="dashicons dashicons-yes-alt"></span> <?php _e('Suggest content improvements', 'nerdy-seo'); ?></li>
                                <li><span class="dashicons dashicons-yes-alt"></span> <?php _e('Keyword recommendations', 'nerdy-seo'); ?></li>
                            </ul>
                        </div>
                    </div>

                    <!-- Sitemap Tab -->
                    <div class="nerdy-seo-tab-content" data-tab="sitemap">
                        <div class="nerdy-seo-settings-card">
                            <h2><?php _e('XML Sitemap', 'nerdy-seo'); ?></h2>
                            <?php
                            $sitemap_url = home_url('/sitemap.xml');
                            $last_generated = get_option('nerdy_seo_sitemap_last_generated');
                            $sitemap_enabled = get_option('nerdy_seo_sitemap_enabled', true);
                            ?>
                            <p class="description"><?php _e('Your XML sitemap helps search engines discover and index all your content. It is automatically regenerated daily and when content changes.', 'nerdy-seo'); ?></p>

                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Enable XML Sitemaps', 'nerdy-seo'); ?></th>
                                    <td>
                                        <label class="nerdy-seo-toggle">
                                            <input
                                                type="checkbox"
                                                name="nerdy_seo_sitemap_enabled"
                                                value="1"
                                                <?php checked($sitemap_enabled, true); ?>
                                            />
                                            <span class="nerdy-seo-toggle-slider"></span>
                                        </label>
                                        <p class="description"><?php _e('Enable or disable XML sitemap generation.', 'nerdy-seo'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Sitemap URL', 'nerdy-seo'); ?></th>
                                    <td>
                                        <a href="<?php echo esc_url($sitemap_url); ?>" target="_blank" class="button">
                                            <span class="dashicons dashicons-external" style="margin-top: 3px;"></span>
                                            <?php echo esc_html($sitemap_url); ?>
                                        </a>
                                        <?php if ($last_generated): ?>
                                            <p class="description"><?php printf(__('Last generated: %s', 'nerdy-seo'), $last_generated); ?></p>
                                        <?php else: ?>
                                            <p class="description"><?php _e('Sitemap has not been generated yet. Click "Generate Now" below.', 'nerdy-seo'); ?></p>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Manual Generation', 'nerdy-seo'); ?></th>
                                    <td>
                                        <button type="button" class="button button-primary" id="nerdy-seo-generate-sitemap">
                                            <span class="dashicons dashicons-update" style="margin-top: 3px;"></span>
                                            <?php _e('Generate Now', 'nerdy-seo'); ?>
                                        </button>
                                        <span id="nerdy-seo-sitemap-status" style="margin-left: 10px;"></span>
                                        <p class="description"><?php _e('Manually regenerate all sitemap files. This happens automatically daily and when content changes.', 'nerdy-seo'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="nerdy-seo-settings-card">
                            <h2><?php _e('Content Exclusions', 'nerdy-seo'); ?></h2>
                            <p class="description"><?php _e('Choose which post types and taxonomies to exclude from your sitemap.', 'nerdy-seo'); ?></p>

                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Exclude Post Types', 'nerdy-seo'); ?></th>
                                    <td>
                                        <?php
                                        $excluded_types = get_option('nerdy_seo_sitemap_exclude_post_types', array());
                                        $post_types = get_post_types(array('public' => true), 'objects');

                                        echo '<fieldset>';
                                        foreach ($post_types as $post_type) {
                                            $checked = is_array($excluded_types) && in_array($post_type->name, $excluded_types);
                                            ?>
                                            <label style="display: block; margin-bottom: 8px;">
                                                <input
                                                    type="checkbox"
                                                    name="nerdy_seo_sitemap_exclude_post_types[]"
                                                    value="<?php echo esc_attr($post_type->name); ?>"
                                                    <?php checked($checked); ?>
                                                />
                                                <?php echo esc_html($post_type->label); ?> <code>(<?php echo esc_html($post_type->name); ?>)</code>
                                            </label>
                                            <?php
                                        }
                                        echo '</fieldset>';
                                        ?>
                                        <p class="description"><?php _e('Select post types to exclude from the sitemap.', 'nerdy-seo'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Exclude Taxonomies', 'nerdy-seo'); ?></th>
                                    <td>
                                        <?php
                                        $excluded_taxonomies = get_option('nerdy_seo_sitemap_exclude_taxonomies', array());
                                        $taxonomies = get_taxonomies(array('public' => true), 'objects');

                                        echo '<fieldset>';
                                        foreach ($taxonomies as $taxonomy) {
                                            $checked = is_array($excluded_taxonomies) && in_array($taxonomy->name, $excluded_taxonomies);
                                            ?>
                                            <label style="display: block; margin-bottom: 8px;">
                                                <input
                                                    type="checkbox"
                                                    name="nerdy_seo_sitemap_exclude_taxonomies[]"
                                                    value="<?php echo esc_attr($taxonomy->name); ?>"
                                                    <?php checked($checked); ?>
                                                />
                                                <?php echo esc_html($taxonomy->label); ?> <code>(<?php echo esc_html($taxonomy->name); ?>)</code>
                                            </label>
                                            <?php
                                        }
                                        echo '</fieldset>';
                                        ?>
                                        <p class="description"><?php _e('Select taxonomies to exclude from the sitemap.', 'nerdy-seo'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="nerdy-seo-info-box">
                            <h3><?php _e('Per-Post Sitemap Settings', 'nerdy-seo'); ?></h3>
                            <p><?php _e('You can also control sitemap settings for individual posts and pages:', 'nerdy-seo'); ?></p>
                            <ul class="nerdy-seo-feature-list">
                                <li><span class="dashicons dashicons-yes-alt"></span> <?php _e('Exclude specific posts from sitemap', 'nerdy-seo'); ?></li>
                                <li><span class="dashicons dashicons-yes-alt"></span> <?php _e('Set custom priority (0.0 to 1.0)', 'nerdy-seo'); ?></li>
                                <li><span class="dashicons dashicons-yes-alt"></span> <?php _e('Set change frequency', 'nerdy-seo'); ?></li>
                            </ul>
                            <p class="description"><?php _e('Look for the "Sitemap Settings" meta box in the post editor sidebar.', 'nerdy-seo'); ?></p>
                        </div>

                        <script>
                        jQuery(document).ready(function($) {
                            $('#nerdy-seo-generate-sitemap').on('click', function() {
                                var $btn = $(this);
                                var $status = $('#nerdy-seo-sitemap-status');

                                $btn.prop('disabled', true);
                                $btn.find('.dashicons').addClass('nerdy-seo-spin');
                                $status.html('<span style="color: #666;">Generating...</span>');

                                $.post(ajaxurl, {
                                    action: 'nerdy_seo_generate_sitemap',
                                    nonce: '<?php echo wp_create_nonce('nerdy_seo_generate_sitemap'); ?>'
                                }, function(response) {
                                    $btn.prop('disabled', false);
                                    $btn.find('.dashicons').removeClass('nerdy-seo-spin');

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
                    </div>

                    <!-- Advanced Tab -->
                    <div class="nerdy-seo-tab-content" data-tab="advanced">
                        <div class="nerdy-seo-settings-card">
                            <h2><?php _e('Plugin Information', 'nerdy-seo'); ?></h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Version', 'nerdy-seo'); ?></th>
                                    <td><code><?php echo NERDY_SEO_VERSION; ?></code></td>
                                </tr>
                                <tr>
                                    <th scope="row"><?php _e('Plugin Directory', 'nerdy-seo'); ?></th>
                                    <td><code><?php echo NERDY_SEO_PLUGIN_DIR; ?></code></td>
                                </tr>
                            </table>
                        </div>

                        <div class="nerdy-seo-info-box info">
                            <h3><?php _e('Need Help?', 'nerdy-seo'); ?></h3>
                            <p><?php _e('Check out the other admin pages for more features:', 'nerdy-seo'); ?></p>
                            <ul>
                                <li><a href="<?php echo admin_url('admin.php?page=nerdy-seo-redirects'); ?>"><?php _e('Manage 301/302 Redirects', 'nerdy-seo'); ?></a></li>
                                <li><a href="<?php echo admin_url('admin.php?page=nerdy-seo-404s'); ?>"><?php _e('View 404 Error Logs', 'nerdy-seo'); ?></a></li>
                                <li><a href="<?php echo admin_url('admin.php?page=nerdy-seo-images'); ?>"><?php _e('Bulk Edit Image Alt Text', 'nerdy-seo'); ?></a></li>
                                <li><a href="<?php echo admin_url('admin.php?page=nerdy-seo-migration'); ?>"><?php _e('Migrate from AIOSEO', 'nerdy-seo'); ?></a></li>
                            </ul>
                        </div>
                    </div>

                    <div class="nerdy-seo-save-bar">
                        <?php submit_button(__('Save Settings', 'nerdy-seo'), 'primary large', 'nerdy_seo_save_settings', false); ?>
                    </div>
                </form>
            </div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Tab switching
            $('.nerdy-seo-tab-btn').on('click', function() {
                var tab = $(this).data('tab');

                $('.nerdy-seo-tab-btn').removeClass('active');
                $(this).addClass('active');

                $('.nerdy-seo-tab-content').removeClass('active');
                $('.nerdy-seo-tab-content[data-tab="' + tab + '"]').addClass('active');
            });

            // Image upload
            var mediaUploader;
            $('.nerdy-seo-upload-btn').on('click', function(e) {
                e.preventDefault();
                var button = $(this);
                var targetId = button.data('target');

                if (mediaUploader) {
                    mediaUploader.open();
                    return;
                }

                mediaUploader = wp.media({
                    title: '<?php _e('Choose Default Social Image', 'nerdy-seo'); ?>',
                    button: {
                        text: '<?php _e('Use this image', 'nerdy-seo'); ?>'
                    },
                    multiple: false
                });

                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#' + targetId).val(attachment.url);

                    // Show preview
                    if ($('.nerdy-seo-image-preview').length) {
                        $('.nerdy-seo-image-preview img').attr('src', attachment.url);
                    } else {
                        button.parent().after('<div class="nerdy-seo-image-preview"><img src="' + attachment.url + '" alt="" /></div>');
                    }
                });

                mediaUploader.open();
            });

            // Variable insertion
            $(document).on('click', '.nerdy-seo-insert-var', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var variable = $btn.data('var');
                var $wrapper = $btn.closest('.nerdy-seo-variable-field-wrapper');
                var $input = $wrapper.find('.nerdy-seo-variable-input');

                // Insert at cursor position
                var input = $input[0];
                var startPos = input.selectionStart;
                var endPos = input.selectionEnd;
                var currentVal = $input.val();

                var newVal = currentVal.substring(0, startPos) + variable + currentVal.substring(endPos);
                $input.val(newVal);

                // Update cursor position
                var newPos = startPos + variable.length;
                input.setSelectionRange(newPos, newPos);
                input.focus();

                // Update preview
                updatePreview($wrapper);
            });

            // Show/hide all variables
            $(document).on('click', '.nerdy-seo-show-all-vars', function(e) {
                e.preventDefault();
                var $allVars = $(this).closest('.nerdy-seo-variable-field-wrapper').find('.nerdy-seo-all-variables');
                $allVars.slideToggle();
                $(this).text($allVars.is(':visible') ? '<?php _e('Hide tags', 'nerdy-seo'); ?>' : '<?php _e('View all tags →', 'nerdy-seo'); ?>');
            });

            // Update preview on input
            $(document).on('input', '.nerdy-seo-variable-input', function() {
                var $wrapper = $(this).closest('.nerdy-seo-variable-field-wrapper');
                updatePreview($wrapper);
            });

            // AI Provider switching
            $('#nerdy_seo_ai_provider').on('change', function() {
                var provider = $(this).val();

                // Hide all provider settings
                $('.nerdy-seo-ai-provider-settings').hide();

                // Show selected provider settings
                $('.nerdy-seo-ai-provider-settings[data-provider="' + provider + '"]').show();
            });

            function updatePreview($wrapper) {
                var $input = $wrapper.find('.nerdy-seo-variable-input');
                var template = $input.val();
                var fieldType = $input.data('field-type');

                // Find preview - could be in content-type-card or settings-card
                var $card = $wrapper.closest('.nerdy-seo-content-type-card, .nerdy-seo-settings-card');
                var $preview = $card.find('.nerdy-seo-serp-preview');

                // If no preview found in card, try finding it in the same tab
                if (!$preview.length) {
                    $preview = $wrapper.closest('.nerdy-seo-tab-content').find('.nerdy-seo-serp-preview').first();
                }

                if (!$preview.length) {
                    return; // No preview to update
                }

                // Simple preview replacement
                var preview = template
                    .replace(/%title%/g, 'Sample Post Title')
                    .replace(/%sitename%/g, '<?php echo esc_js(get_bloginfo('name')); ?>')
                    .replace(/%sitedesc%/g, '<?php echo esc_js(get_bloginfo('description')); ?>')
                    .replace(/%separator%/g, '<?php echo esc_js(get_option('nerdy_seo_separator', '|')); ?>')
                    .replace(/%excerpt%/g, 'This is a sample excerpt from the post that gives a brief overview of the content.')
                    .replace(/%author%/g, 'Author Name')
                    .replace(/%date%/g, '<?php echo date('F j, Y'); ?>')
                    .replace(/%year%/g, '<?php echo date('Y'); ?>')
                    .replace(/%month%/g, '<?php echo date('F'); ?>')
                    .replace(/%day%/g, '<?php echo date('j'); ?>')
                    .replace(/%categories%/g, 'Category 1, Category 2')
                    .replace(/%tags%/g, 'Tag 1, Tag 2');

                if (fieldType === 'title') {
                    $preview.find('.nerdy-seo-serp-title').text(preview);
                } else if (fieldType === 'description') {
                    $preview.find('.nerdy-seo-serp-description').text(preview);
                }
            }
        });
        </script>
        <?php
    }

    /**
     * Preview template with sample data
     */
    private function preview_template($template, $post_type) {
        $replacements = array(
            '%title%' => 'Sample ' . $post_type->labels->singular_name . ' Title',
            '%sitename%' => get_bloginfo('name'),
            '%sitedesc%' => get_bloginfo('description'),
            '%separator%' => '|',
            '%excerpt%' => 'This is a sample excerpt from the post that gives a brief overview of the content.',
            '%author%' => 'Author Name',
            '%date%' => date('F j, Y'),
            '%year%' => date('Y'),
            '%month%' => date('F'),
            '%day%' => date('j'),
            '%categories%' => 'Category 1, Category 2',
            '%tags%' => 'Tag 1, Tag 2',
        );

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }
}

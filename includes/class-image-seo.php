<?php
/**
 * Image SEO functionality
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Image SEO class
 */
class Nerdy_SEO_Image_SEO {

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

        // AJAX handlers
        add_action('wp_ajax_nerdy_seo_save_image_alt', array($this, 'ajax_save_alt_text'));
        add_action('wp_ajax_nerdy_seo_auto_generate_alt', array($this, 'ajax_auto_generate_alt'));
        add_action('wp_ajax_nerdy_seo_bulk_generate_alt', array($this, 'ajax_bulk_generate_alt'));

        // Auto-generate on upload (optional)
        add_action('add_attachment', array($this, 'auto_generate_on_upload'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'nerdy-seo',
            __('Image SEO', 'nerdy-seo'),
            __('Image SEO', 'nerdy-seo'),
            'manage_options',
            'nerdy-seo-images',
            array($this, 'render_images_page')
        );
    }

    /**
     * Render images page
     */
    public function render_images_page() {
        // Get filter
        $filter = isset($_GET['filter']) ? sanitize_text_field($_GET['filter']) : 'all';

        // Get images
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'posts_per_page' => 50,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        // Apply filter
        if ($filter === 'missing') {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => '_wp_attachment_image_alt',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => '_wp_attachment_image_alt',
                    'value' => '',
                    'compare' => '=',
                ),
            );
        }

        $images = get_posts($args);

        // Count stats
        $total_images = wp_count_posts('attachment')->inherit;
        $missing_alt = $this->count_missing_alt();

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Image SEO Manager', 'nerdy-seo'); ?></h1>

            <div class="nerdy-seo-image-stats" style="background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #2271b1;">
                <h3><?php esc_html_e('Statistics', 'nerdy-seo'); ?></h3>
                <p>
                    <strong><?php esc_html_e('Total Images:', 'nerdy-seo'); ?></strong> <?php echo number_format($total_images); ?><br>
                    <strong><?php esc_html_e('Missing Alt Text:', 'nerdy-seo'); ?></strong>
                    <span style="color: <?php echo $missing_alt > 0 ? '#dc3232' : '#46b450'; ?>;">
                        <?php echo number_format($missing_alt); ?>
                        (<?php echo $total_images > 0 ? round(($missing_alt / $total_images) * 100, 1) : 0; ?>%)
                    </span>
                </p>
            </div>

            <div class="nerdy-seo-image-actions" style="margin: 20px 0;">
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <select id="nerdy-seo-image-filter">
                            <option value="all" <?php selected($filter, 'all'); ?>><?php esc_html_e('All Images', 'nerdy-seo'); ?></option>
                            <option value="missing" <?php selected($filter, 'missing'); ?>><?php esc_html_e('Missing Alt Text', 'nerdy-seo'); ?></option>
                        </select>
                        <button type="button" class="button" id="nerdy-seo-apply-filter">
                            <?php esc_html_e('Filter', 'nerdy-seo'); ?>
                        </button>
                    </div>
                    <div class="alignleft actions">
                        <button type="button" class="button button-primary" id="nerdy-seo-bulk-generate">
                            <?php esc_html_e('Auto-Generate All Missing Alt Text', 'nerdy-seo'); ?>
                        </button>
                    </div>
                </div>
            </div>

            <?php if (empty($images)): ?>
                <div class="notice notice-info">
                    <p><?php esc_html_e('No images found matching your filter.', 'nerdy-seo'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th style="width: 80px;"><?php esc_html_e('Image', 'nerdy-seo'); ?></th>
                            <th style="width: 25%;"><?php esc_html_e('Filename', 'nerdy-seo'); ?></th>
                            <th style="width: 35%;"><?php esc_html_e('Alt Text', 'nerdy-seo'); ?></th>
                            <th style="width: 20%;"><?php esc_html_e('Title', 'nerdy-seo'); ?></th>
                            <th style="width: 15%;"><?php esc_html_e('Actions', 'nerdy-seo'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($images as $image): ?>
                            <?php
                            $alt_text = get_post_meta($image->ID, '_wp_attachment_image_alt', true);
                            $thumbnail = wp_get_attachment_image_src($image->ID, 'thumbnail');
                            $has_alt = !empty($alt_text);
                            ?>
                            <tr data-id="<?php echo esc_attr($image->ID); ?>">
                                <td>
                                    <?php if ($thumbnail): ?>
                                        <img src="<?php echo esc_url($thumbnail[0]); ?>" alt="" style="max-width: 60px; height: auto;" />
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html(basename($image->guid)); ?></strong>
                                    <br>
                                    <small style="color: #666;">
                                        <?php echo esc_html(gmdate('Y-m-d', strtotime($image->post_date))); ?>
                                    </small>
                                </td>
                                <td>
                                    <input
                                        type="text"
                                        class="nerdy-seo-alt-input regular-text"
                                        data-id="<?php echo esc_attr($image->ID); ?>"
                                        value="<?php echo esc_attr($alt_text); ?>"
                                        placeholder="<?php esc_html_e('Enter alt text...', 'nerdy-seo'); ?>"
                                    />
                                    <?php if (!$has_alt): ?>
                                        <span class="nerdy-seo-missing-indicator" style="color: #dc3232; font-size: 12px;">
                                            ⚠ <?php esc_html_e('Missing', 'nerdy-seo'); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small><?php echo esc_html($image->post_title); ?></small>
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="button button-small nerdy-seo-save-alt"
                                        data-id="<?php echo esc_attr($image->ID); ?>"
                                    >
                                        <?php esc_html_e('Save', 'nerdy-seo'); ?>
                                    </button>
                                    <button
                                        type="button"
                                        class="button button-small nerdy-seo-auto-generate"
                                        data-id="<?php echo esc_attr($image->ID); ?>"
                                        data-filename="<?php echo esc_attr(basename($image->guid)); ?>"
                                        title="<?php esc_html_e('Auto-generate from filename', 'nerdy-seo'); ?>"
                                    >
                                        <?php esc_html_e('Auto', 'nerdy-seo'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if (count($images) >= 50): ?>
                    <p style="margin-top: 20px;">
                        <em><?php esc_html_e('Showing first 50 images. Use filters to find specific images.', 'nerdy-seo'); ?></em>
                    </p>
                <?php endif; ?>
            <?php endif; ?>
        </div>

        <style>
            .nerdy-seo-alt-input {
                width: 100%;
            }
            .nerdy-seo-missing-indicator {
                display: block;
                margin-top: 3px;
            }
            .nerdy-seo-save-status {
                margin-left: 5px;
                font-size: 12px;
            }
            .nerdy-seo-save-status.success {
                color: #46b450;
            }
            .nerdy-seo-save-status.error {
                color: #dc3232;
            }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Filter images
            $('#nerdy-seo-apply-filter').on('click', function() {
                var filter = $('#nerdy-seo-image-filter').val();
                window.location.href = '<?php echo admin_url('admin.php?page=nerdy-seo-images'); ?>&filter=' + filter;
            });

            // Save alt text
            $('.nerdy-seo-save-alt').on('click', function() {
                var $btn = $(this);
                var id = $btn.data('id');
                var $input = $('.nerdy-seo-alt-input[data-id="' + id + '"]');
                var alt = $input.val();
                var $row = $btn.closest('tr');

                $btn.prop('disabled', true).text('<?php esc_html_e('Saving...', 'nerdy-seo'); ?>');

                $.post(ajaxurl, {
                    action: 'nerdy_seo_save_image_alt',
                    nonce: '<?php echo wp_create_nonce('nerdy_seo_image_seo'); ?>',
                    id: id,
                    alt: alt
                }, function(response) {
                    if (response.success) {
                        $btn.after('<span class="nerdy-seo-save-status success">✓</span>');
                        $row.find('.nerdy-seo-missing-indicator').remove();
                        setTimeout(function() {
                            $('.nerdy-seo-save-status').fadeOut(function() { $(this).remove(); });
                        }, 2000);
                    } else {
                        $btn.after('<span class="nerdy-seo-save-status error">✗</span>');
                    }
                    $btn.prop('disabled', false).text('<?php esc_html_e('Save', 'nerdy-seo'); ?>');
                });
            });

            // Auto-generate alt text
            $('.nerdy-seo-auto-generate').on('click', function() {
                var $btn = $(this);
                var id = $btn.data('id');
                var filename = $btn.data('filename');
                var $input = $('.nerdy-seo-alt-input[data-id="' + id + '"]');

                $btn.prop('disabled', true).text('...');

                $.post(ajaxurl, {
                    action: 'nerdy_seo_auto_generate_alt',
                    nonce: '<?php echo wp_create_nonce('nerdy_seo_image_seo'); ?>',
                    id: id,
                    filename: filename
                }, function(response) {
                    if (response.success) {
                        $input.val(response.data.alt);
                        $btn.closest('tr').find('.nerdy-seo-missing-indicator').remove();
                    }
                    $btn.prop('disabled', false).text('<?php esc_html_e('Auto', 'nerdy-seo'); ?>');
                });
            });

            // Bulk auto-generate
            $('#nerdy-seo-bulk-generate').on('click', function() {
                if (!confirm('<?php esc_html_e('This will auto-generate alt text for all images missing it. Continue?', 'nerdy-seo'); ?>')) {
                    return;
                }

                var $btn = $(this);
                $btn.prop('disabled', true).text('<?php esc_html_e('Processing...', 'nerdy-seo'); ?>');

                $.post(ajaxurl, {
                    action: 'nerdy_seo_bulk_generate_alt',
                    nonce: '<?php echo wp_create_nonce('nerdy_seo_image_seo'); ?>'
                }, function(response) {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('<?php esc_html_e('Error processing images', 'nerdy-seo'); ?>');
                        $btn.prop('disabled', false).text('<?php esc_html_e('Auto-Generate All Missing Alt Text', 'nerdy-seo'); ?>');
                    }
                });
            });

            // Save on Enter key
            $('.nerdy-seo-alt-input').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    var id = $(this).data('id');
                    $('.nerdy-seo-save-alt[data-id="' + id + '"]').click();
                }
            });
        });
        </script>
        <?php
    }

    /**
     * Count images missing alt text
     */
    private function count_missing_alt() {
        global $wpdb;

        $query = "
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
            WHERE p.post_type = 'attachment'
            AND p.post_mime_type LIKE 'image/%'
            AND (pm.meta_value IS NULL OR pm.meta_value = '')
        ";

        return (int) $wpdb->get_var($query);
    }

    /**
     * AJAX: Save alt text
     */
    public function ajax_save_alt_text() {
        check_ajax_referer('nerdy_seo_image_seo', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error(array('message' => __('Permission denied', 'nerdy-seo')));
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $alt = isset($_POST['alt']) ? sanitize_text_field($_POST['alt']) : '';

        if (!$id) {
            wp_send_json_error(array('message' => __('Invalid image ID', 'nerdy-seo')));
        }

        update_post_meta($id, '_wp_attachment_image_alt', $alt);

        wp_send_json_success(array('message' => __('Alt text saved', 'nerdy-seo')));
    }

    /**
     * AJAX: Auto-generate alt text
     */
    public function ajax_auto_generate_alt() {
        check_ajax_referer('nerdy_seo_image_seo', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error();
        }

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $filename = isset($_POST['filename']) ? sanitize_text_field($_POST['filename']) : '';

        if (!$id || !$filename) {
            wp_send_json_error();
        }

        $alt = $this->generate_alt_from_filename($filename);

        update_post_meta($id, '_wp_attachment_image_alt', $alt);

        wp_send_json_success(array('alt' => $alt));
    }

    /**
     * AJAX: Bulk auto-generate alt text
     */
    public function ajax_bulk_generate_alt() {
        check_ajax_referer('nerdy_seo_image_seo', 'nonce');

        if (!current_user_can('upload_files')) {
            wp_send_json_error();
        }

        // Get all images without alt text
        $args = array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_wp_attachment_image_alt',
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key' => '_wp_attachment_image_alt',
                    'value' => '',
                    'compare' => '=',
                ),
            ),
        );

        $images = get_posts($args);
        $processed = 0;

        foreach ($images as $image) {
            $filename = basename($image->guid);
            $alt = $this->generate_alt_from_filename($filename);

            update_post_meta($image->ID, '_wp_attachment_image_alt', $alt);
            $processed++;
        }

        wp_send_json_success(array(
            'message' => sprintf(
                __('Processed %d images', 'nerdy-seo'),
                $processed
            ),
        ));
    }

    /**
     * Generate alt text from filename
     */
    private function generate_alt_from_filename($filename) {
        // Remove extension
        $name = pathinfo($filename, PATHINFO_FILENAME);

        // Replace hyphens and underscores with spaces
        $name = str_replace(array('-', '_'), ' ', $name);

        // Remove common suffixes
        $name = preg_replace('/\d+x\d+$/', '', $name); // Remove dimensions like 800x600
        $name = preg_replace('/-scaled$/', '', $name); // Remove -scaled
        $name = preg_replace('/-\d+$/', '', $name); // Remove trailing numbers

        // Capitalize words
        $name = ucwords(trim($name));

        // Apply filter for customization
        return apply_filters('nerdy_seo_generated_alt_text', $name, $filename);
    }

    /**
     * Auto-generate on upload (if enabled)
     */
    public function auto_generate_on_upload($attachment_id) {
        if (!wp_attachment_is_image($attachment_id)) {
            return;
        }

        if (!get_option('nerdy_seo_auto_generate_alt', false)) {
            return;
        }

        // Check if alt text already exists
        $existing_alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        if (!empty($existing_alt)) {
            return;
        }

        // Generate alt text
        $filename = basename(get_attached_file($attachment_id));
        $alt = $this->generate_alt_from_filename($filename);

        update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt);
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('nerdy_seo_settings', 'nerdy_seo_auto_generate_alt');

        add_settings_field(
            'nerdy_seo_auto_generate_alt',
            __('Auto-Generate Alt Text', 'nerdy-seo'),
            array($this, 'render_auto_generate_field'),
            'nerdy-seo',
            'nerdy_seo_general_section'
        );
    }

    /**
     * Render auto-generate field
     */
    public function render_auto_generate_field() {
        $enabled = get_option('nerdy_seo_auto_generate_alt', false);
        ?>
        <label>
            <input type="checkbox" name="nerdy_seo_auto_generate_alt" value="1" <?php checked($enabled, true); ?> />
            <?php esc_html_e('Automatically generate alt text from filename when uploading images', 'nerdy-seo'); ?>
        </label>
        <?php
    }
}

<?php
/**
 * Redirect Manager functionality
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Redirects class
 */
class Nerdy_SEO_Redirects {

    /**
     * Single instance
     */
    private static $instance = null;

    /**
     * Redirects table name
     */
    private $redirects_table;

    /**
     * 404 logs table name
     */
    private $logs_table;

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
        global $wpdb;
        $this->redirects_table = $wpdb->prefix . 'nerdy_seo_redirects';
        $this->logs_table = $wpdb->prefix . 'nerdy_seo_404_logs';

        // Process redirects
        add_action('template_redirect', array($this, 'process_redirect'), 1);

        // Log 404s
        add_action('wp', array($this, 'log_404'));

        // Admin menu (priority 20 to load after main menu)
        add_action('admin_menu', array($this, 'add_admin_menu'), 20);

        // AJAX handlers
        add_action('wp_ajax_nerdy_seo_add_redirect', array($this, 'ajax_add_redirect'));
        add_action('wp_ajax_nerdy_seo_delete_redirect', array($this, 'ajax_delete_redirect'));
        add_action('wp_ajax_nerdy_seo_toggle_redirect', array($this, 'ajax_toggle_redirect'));
        add_action('wp_ajax_nerdy_seo_clear_404_logs', array($this, 'ajax_clear_404_logs'));
        add_action('wp_ajax_nerdy_seo_delete_404_logs', array($this, 'ajax_delete_404_logs'));
        add_action('wp_ajax_nerdy_seo_export_redirects', array($this, 'ajax_export_redirects'));
        add_action('wp_ajax_nerdy_seo_import_redirects', array($this, 'ajax_import_redirects'));
    }

    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $redirects_table = $wpdb->prefix . 'nerdy_seo_redirects';
        $logs_table = $wpdb->prefix . 'nerdy_seo_404_logs';

        // Redirects table
        $sql1 = "CREATE TABLE IF NOT EXISTS $redirects_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            source_url varchar(255) NOT NULL,
            target_url varchar(255) NOT NULL,
            redirect_type varchar(10) NOT NULL DEFAULT '301',
            hits bigint(20) DEFAULT 0,
            enabled tinyint(1) DEFAULT 1,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY source_url (source_url),
            KEY enabled (enabled)
        ) $charset_collate;";

        // 404 logs table
        $sql2 = "CREATE TABLE IF NOT EXISTS $logs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            url varchar(255) NOT NULL,
            referer text,
            user_agent text,
            ip_address varchar(45),
            hits bigint(20) DEFAULT 1,
            first_seen datetime DEFAULT CURRENT_TIMESTAMP,
            last_seen datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY url (url),
            KEY last_seen (last_seen)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql1);
        dbDelta($sql2);
    }

    /**
     * Process redirect
     */
    public function process_redirect() {
        if (is_admin() || is_robots() || is_feed() || is_trackback()) {
            return;
        }

        global $wpdb;

        $current_url = $_SERVER['REQUEST_URI'];
        $current_url = strtok($current_url, '?'); // Remove query string

        // Get redirect
        $redirect = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->redirects_table} WHERE source_url = %s AND enabled = 1 LIMIT 1",
            $current_url
        ));

        if ($redirect) {
            // Increment hit counter
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->redirects_table} SET hits = hits + 1 WHERE id = %d",
                $redirect->id
            ));

            // Perform redirect
            $redirect_code = $redirect->redirect_type === '302' ? 302 : 301;
            wp_redirect($redirect->target_url, $redirect_code);
            exit;
        }
    }

    /**
     * Log 404 errors
     */
    public function log_404() {
        if (!is_404() || is_admin()) {
            return;
        }

        if (!get_option('nerdy_seo_track_404s', true)) {
            return;
        }

        global $wpdb;

        $url = $_SERVER['REQUEST_URI'];
        $referer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
        $ip_address = $this->get_client_ip();

        // Check if URL already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, hits FROM {$this->logs_table} WHERE url = %s",
            $url
        ));

        if ($existing) {
            // Update existing record
            $wpdb->query($wpdb->prepare(
                "UPDATE {$this->logs_table} SET hits = hits + 1, last_seen = NOW() WHERE id = %d",
                $existing->id
            ));
        } else {
            // Insert new record
            $wpdb->insert(
                $this->logs_table,
                array(
                    'url' => $url,
                    'referer' => $referer,
                    'user_agent' => $user_agent,
                    'ip_address' => $ip_address,
                    'hits' => 1,
                ),
                array('%s', '%s', '%s', '%s', '%d')
            );
        }
    }

    /**
     * Get client IP address
     */
    private function get_client_ip() {
        $ip = '';

        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'nerdy-seo',
            __('Redirects', 'nerdy-seo'),
            __('Redirects', 'nerdy-seo'),
            'manage_options',
            'nerdy-seo-redirects',
            array($this, 'render_redirects_page')
        );

        add_submenu_page(
            'nerdy-seo',
            __('404 Errors', 'nerdy-seo'),
            __('404 Errors', 'nerdy-seo'),
            'manage_options',
            'nerdy-seo-404s',
            array($this, 'render_404_page')
        );
    }

    /**
     * Render redirects page
     */
    public function render_redirects_page() {
        global $wpdb;

        // Get all redirects
        $redirects = $wpdb->get_results("SELECT * FROM {$this->redirects_table} ORDER BY id DESC");

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Redirect Manager', 'nerdy-seo'); ?></h1>

            <div class="nerdy-seo-redirect-actions" style="margin: 20px 0;">
                <button type="button" class="button button-primary" id="nerdy-seo-add-redirect-btn">
                    <?php esc_html_e('Add New Redirect', 'nerdy-seo'); ?>
                </button>
                <button type="button" class="button" id="nerdy-seo-import-redirects-btn">
                    <?php esc_html_e('Import Redirects', 'nerdy-seo'); ?>
                </button>
                <button type="button" class="button" id="nerdy-seo-export-redirects-btn">
                    <?php esc_html_e('Export Redirects', 'nerdy-seo'); ?>
                </button>
            </div>

            <!-- Add/Edit Redirect Form -->
            <div id="nerdy-seo-redirect-form" style="display: none; background: white; padding: 20px; border: 1px solid #ccc; margin-bottom: 20px;">
                <h2><?php esc_html_e('Add Redirect', 'nerdy-seo'); ?></h2>

                <table class="form-table">
                    <tr>
                        <th><label for="redirect-source"><?php esc_html_e('Source URL', 'nerdy-seo'); ?></label></th>
                        <td>
                            <input type="text" id="redirect-source" class="regular-text" placeholder="/old-page" />
                            <p class="description"><?php esc_html_e('The URL you want to redirect FROM (relative path, e.g., /old-page)', 'nerdy-seo'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="redirect-target"><?php esc_html_e('Target URL', 'nerdy-seo'); ?></label></th>
                        <td>
                            <input type="text" id="redirect-target" class="regular-text" placeholder="/new-page or https://example.com/page" />
                            <p class="description"><?php esc_html_e('The URL you want to redirect TO (can be relative or absolute)', 'nerdy-seo'); ?></p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="redirect-type"><?php esc_html_e('Redirect Type', 'nerdy-seo'); ?></label></th>
                        <td>
                            <select id="redirect-type">
                                <option value="301"><?php esc_html_e('301 (Permanent)', 'nerdy-seo'); ?></option>
                                <option value="302"><?php esc_html_e('302 (Temporary)', 'nerdy-seo'); ?></option>
                            </select>
                            <p class="description"><?php esc_html_e('Use 301 for permanent redirects (most common). Use 302 for temporary redirects.', 'nerdy-seo'); ?></p>
                        </td>
                    </tr>
                </table>

                <p>
                    <button type="button" class="button button-primary" id="nerdy-seo-save-redirect">
                        <?php esc_html_e('Save Redirect', 'nerdy-seo'); ?>
                    </button>
                    <button type="button" class="button" id="nerdy-seo-cancel-redirect">
                        <?php esc_html_e('Cancel', 'nerdy-seo'); ?>
                    </button>
                </p>
            </div>

            <!-- Import Form -->
            <div id="nerdy-seo-import-form" style="display: none; background: white; padding: 20px; border: 1px solid #ccc; margin-bottom: 20px;">
                <h2><?php esc_html_e('Import Redirects', 'nerdy-seo'); ?></h2>
                <p><?php esc_html_e('Upload a CSV file with columns: source_url, target_url, redirect_type (optional)', 'nerdy-seo'); ?></p>

                <input type="file" id="nerdy-seo-import-file" accept=".csv" />

                <p>
                    <button type="button" class="button button-primary" id="nerdy-seo-do-import">
                        <?php esc_html_e('Import', 'nerdy-seo'); ?>
                    </button>
                    <button type="button" class="button" id="nerdy-seo-cancel-import">
                        <?php esc_html_e('Cancel', 'nerdy-seo'); ?>
                    </button>
                </p>
            </div>

            <!-- Redirects Table -->
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th style="width: 30%;"><?php esc_html_e('Source URL', 'nerdy-seo'); ?></th>
                        <th style="width: 30%;"><?php esc_html_e('Target URL', 'nerdy-seo'); ?></th>
                        <th style="width: 10%;"><?php esc_html_e('Type', 'nerdy-seo'); ?></th>
                        <th style="width: 10%;"><?php esc_html_e('Hits', 'nerdy-seo'); ?></th>
                        <th style="width: 10%;"><?php esc_html_e('Status', 'nerdy-seo'); ?></th>
                        <th style="width: 10%;"><?php esc_html_e('Actions', 'nerdy-seo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($redirects)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px;">
                                <?php esc_html_e('No redirects found. Click "Add New Redirect" to create one.', 'nerdy-seo'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($redirects as $redirect): ?>
                            <tr data-id="<?php echo esc_attr($redirect->id); ?>">
                                <td><code><?php echo esc_html($redirect->source_url); ?></code></td>
                                <td><code><?php echo esc_html($redirect->target_url); ?></code></td>
                                <td><span class="redirect-type-<?php echo esc_attr($redirect->redirect_type); ?>"><?php echo esc_html($redirect->redirect_type); ?></span></td>
                                <td><?php echo esc_html($redirect->hits); ?></td>
                                <td>
                                    <span class="redirect-status-<?php echo $redirect->enabled ? 'enabled' : 'disabled'; ?>">
                                        <?php echo $redirect->enabled ? __('Enabled', 'nerdy-seo') : __('Disabled', 'nerdy-seo'); ?>
                                    </span>
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="button button-small nerdy-seo-toggle-redirect"
                                        data-id="<?php echo esc_attr($redirect->id); ?>"
                                    >
                                        <?php echo $redirect->enabled ? __('Disable', 'nerdy-seo') : __('Enable', 'nerdy-seo'); ?>
                                    </button>
                                    <button
                                        type="button"
                                        class="button button-small nerdy-seo-delete-redirect"
                                        data-id="<?php echo esc_attr($redirect->id); ?>"
                                        style="color: #dc3232;"
                                    >
                                        <?php esc_html_e('Delete', 'nerdy-seo'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <style>
            .redirect-type-301 { color: #46b450; font-weight: 600; }
            .redirect-type-302 { color: #ffb900; font-weight: 600; }
            .redirect-status-enabled { color: #46b450; }
            .redirect-status-disabled { color: #999; }
        </style>

        <script>
        jQuery(document).ready(function($) {
            // Show add form
            $('#nerdy-seo-add-redirect-btn').on('click', function() {
                $('#nerdy-seo-redirect-form').slideDown();
                $('#redirect-source').focus();
            });

            // Cancel form
            $('#nerdy-seo-cancel-redirect').on('click', function() {
                $('#nerdy-seo-redirect-form').slideUp();
                $('#redirect-source, #redirect-target').val('');
            });

            // Save redirect
            $('#nerdy-seo-save-redirect').on('click', function() {
                var source = $('#redirect-source').val();
                var target = $('#redirect-target').val();
                var type = $('#redirect-type').val();

                if (!source || !target) {
                    alert('<?php esc_html_e('Please fill in both source and target URLs', 'nerdy-seo'); ?>');
                    return;
                }

                $.post(ajaxurl, {
                    action: 'nerdy_seo_add_redirect',
                    nonce: '<?php echo wp_create_nonce('nerdy_seo_redirects'); ?>',
                    source: source,
                    target: target,
                    type: type
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || '<?php esc_html_e('Error adding redirect', 'nerdy-seo'); ?>');
                    }
                });
            });

            // Delete redirect
            $('.nerdy-seo-delete-redirect').on('click', function() {
                if (!confirm('<?php esc_html_e('Are you sure you want to delete this redirect?', 'nerdy-seo'); ?>')) {
                    return;
                }

                var id = $(this).data('id');
                var $row = $(this).closest('tr');

                $.post(ajaxurl, {
                    action: 'nerdy_seo_delete_redirect',
                    nonce: '<?php echo wp_create_nonce('nerdy_seo_redirects'); ?>',
                    id: id
                }, function(response) {
                    if (response.success) {
                        $row.fadeOut(function() { $(this).remove(); });
                    }
                });
            });

            // Toggle redirect
            $('.nerdy-seo-toggle-redirect').on('click', function() {
                var id = $(this).data('id');
                var $btn = $(this);

                $.post(ajaxurl, {
                    action: 'nerdy_seo_toggle_redirect',
                    nonce: '<?php echo wp_create_nonce('nerdy_seo_redirects'); ?>',
                    id: id
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                });
            });

            // Export redirects
            $('#nerdy-seo-export-redirects-btn').on('click', function() {
                window.location.href = ajaxurl + '?action=nerdy_seo_export_redirects&nonce=<?php echo wp_create_nonce('nerdy_seo_redirects'); ?>';
            });

            // Import form
            $('#nerdy-seo-import-redirects-btn').on('click', function() {
                $('#nerdy-seo-import-form').slideDown();
            });

            $('#nerdy-seo-cancel-import').on('click', function() {
                $('#nerdy-seo-import-form').slideUp();
            });

            // Do import
            $('#nerdy-seo-do-import').on('click', function() {
                var file = $('#nerdy-seo-import-file')[0].files[0];

                if (!file) {
                    alert('<?php esc_html_e('Please select a CSV file', 'nerdy-seo'); ?>');
                    return;
                }

                var reader = new FileReader();
                reader.onload = function(e) {
                    var csv = e.target.result;

                    $.post(ajaxurl, {
                        action: 'nerdy_seo_import_redirects',
                        nonce: '<?php echo wp_create_nonce('nerdy_seo_redirects'); ?>',
                        csv: csv
                    }, function(response) {
                        if (response.success) {
                            alert(response.data.message);
                            location.reload();
                        } else {
                            alert(response.data.message || '<?php esc_html_e('Error importing redirects', 'nerdy-seo'); ?>');
                        }
                    });
                };
                reader.readAsText(file);
            });
        });
        </script>
        <?php
    }

    /**
     * Render 404 page
     */
    public function render_404_page() {
        global $wpdb;

        // Get 404 logs
        $logs = $wpdb->get_results("SELECT * FROM {$this->logs_table} ORDER BY hits DESC, last_seen DESC LIMIT 100");

        ?>
        <div class="wrap">
            <h1><?php esc_html_e('404 Error Tracking', 'nerdy-seo'); ?></h1>

            <div class="nerdy-seo-404-actions" style="margin: 20px 0;">
                <button type="button" class="button" id="nerdy-seo-delete-selected-404s" disabled>
                    <?php esc_html_e('Delete Selected', 'nerdy-seo'); ?>
                </button>
                <button type="button" class="button" id="nerdy-seo-clear-404s">
                    <?php esc_html_e('Clear All 404 Logs', 'nerdy-seo'); ?>
                </button>
            </div>

            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <td class="manage-column column-cb check-column">
                            <input type="checkbox" id="nerdy-seo-select-all-404s" />
                        </td>
                        <th style="width: 35%;"><?php esc_html_e('URL', 'nerdy-seo'); ?></th>
                        <th style="width: 8%;"><?php esc_html_e('Hits', 'nerdy-seo'); ?></th>
                        <th style="width: 18%;"><?php esc_html_e('Last Seen', 'nerdy-seo'); ?></th>
                        <th style="width: 18%;"><?php esc_html_e('Referer', 'nerdy-seo'); ?></th>
                        <th style="width: 21%;"><?php esc_html_e('Actions', 'nerdy-seo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($logs)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 20px;">
                                <?php esc_html_e('No 404 errors logged yet.', 'nerdy-seo'); ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($logs as $log): ?>
                            <tr data-id="<?php echo esc_attr($log->id); ?>">
                                <th scope="row" class="check-column">
                                    <input type="checkbox" class="nerdy-seo-404-checkbox" value="<?php echo esc_attr($log->id); ?>" />
                                </th>
                                <td><code><?php echo esc_html($log->url); ?></code></td>
                                <td><?php echo esc_html($log->hits); ?></td>
                                <td><?php echo esc_html(mysql2date('Y-m-d H:i:s', $log->last_seen)); ?></td>
                                <td>
                                    <?php if ($log->referer): ?>
                                        <small><?php echo esc_html(wp_trim_words($log->referer, 5)); ?></small>
                                    <?php else: ?>
                                        <span style="color: #999;">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <button
                                        type="button"
                                        class="button button-small nerdy-seo-create-redirect"
                                        data-url="<?php echo esc_attr($log->url); ?>"
                                    >
                                        <?php esc_html_e('Create Redirect', 'nerdy-seo'); ?>
                                    </button>
                                    <button
                                        type="button"
                                        class="button button-small nerdy-seo-delete-404"
                                        data-id="<?php echo esc_attr($log->id); ?>"
                                        style="color: #dc3232;"
                                    >
                                        <?php esc_html_e('Delete', 'nerdy-seo'); ?>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <script>
        jQuery(document).ready(function($) {
            // Select/deselect all checkboxes
            $('#nerdy-seo-select-all-404s').on('change', function() {
                $('.nerdy-seo-404-checkbox').prop('checked', $(this).prop('checked'));
                updateDeleteButton();
            });

            // Update delete button state when individual checkboxes change
            $('.nerdy-seo-404-checkbox').on('change', function() {
                updateDeleteButton();
            });

            function updateDeleteButton() {
                var checkedCount = $('.nerdy-seo-404-checkbox:checked').length;
                $('#nerdy-seo-delete-selected-404s').prop('disabled', checkedCount === 0);
            }

            // Delete selected 404s
            $('#nerdy-seo-delete-selected-404s').on('click', function() {
                var ids = [];
                $('.nerdy-seo-404-checkbox:checked').each(function() {
                    ids.push($(this).val());
                });

                if (ids.length === 0) return;

                if (!confirm('<?php esc_html_e('Are you sure you want to delete the selected 404 logs?', 'nerdy-seo'); ?>')) {
                    return;
                }

                $.post(ajaxurl, {
                    action: 'nerdy_seo_delete_404_logs',
                    nonce: '<?php echo wp_create_nonce('nerdy_seo_redirects'); ?>',
                    ids: ids
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                });
            });

            // Delete individual 404
            $('.nerdy-seo-delete-404').on('click', function() {
                if (!confirm('<?php esc_html_e('Are you sure you want to delete this 404 log?', 'nerdy-seo'); ?>')) {
                    return;
                }

                var id = $(this).data('id');
                var $row = $(this).closest('tr');

                $.post(ajaxurl, {
                    action: 'nerdy_seo_delete_404_logs',
                    nonce: '<?php echo wp_create_nonce('nerdy_seo_redirects'); ?>',
                    ids: [id]
                }, function(response) {
                    if (response.success) {
                        $row.fadeOut(function() {
                            $(this).remove();
                            // Check if table is now empty
                            if ($('table tbody tr').length === 0) {
                                location.reload();
                            }
                        });
                    }
                });
            });

            // Clear all 404s
            $('#nerdy-seo-clear-404s').on('click', function() {
                if (!confirm('<?php esc_html_e('Are you sure you want to clear all 404 logs?', 'nerdy-seo'); ?>')) {
                    return;
                }

                $.post(ajaxurl, {
                    action: 'nerdy_seo_clear_404_logs',
                    nonce: '<?php echo wp_create_nonce('nerdy_seo_redirects'); ?>'
                }, function(response) {
                    if (response.success) {
                        location.reload();
                    }
                });
            });

            // Create redirect from 404
            $('.nerdy-seo-create-redirect').on('click', function() {
                var url = $(this).data('url');
                var target = prompt('<?php esc_html_e('Enter target URL for redirect:', 'nerdy-seo'); ?>');

                if (!target) return;

                $.post(ajaxurl, {
                    action: 'nerdy_seo_add_redirect',
                    nonce: '<?php echo wp_create_nonce('nerdy_seo_redirects'); ?>',
                    source: url,
                    target: target,
                    type: '301'
                }, function(response) {
                    if (response.success) {
                        alert('<?php esc_html_e('Redirect created successfully!', 'nerdy-seo'); ?>');
                        window.location.href = '<?php echo admin_url('admin.php?page=nerdy-seo-redirects'); ?>';
                    }
                });
            });
        });
        </script>
        <?php
    }

    /**
     * AJAX: Add redirect
     */
    public function ajax_add_redirect() {
        check_ajax_referer('nerdy_seo_redirects', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'nerdy-seo')));
        }

        global $wpdb;

        $source = isset($_POST['source']) ? sanitize_text_field($_POST['source']) : '';
        $target = isset($_POST['target']) ? sanitize_text_field($_POST['target']) : '';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '301';

        if (empty($source) || empty($target)) {
            wp_send_json_error(array('message' => __('Source and target URLs are required', 'nerdy-seo')));
        }

        // Ensure source starts with /
        if (!str_starts_with($source, '/')) {
            $source = '/' . $source;
        }

        $result = $wpdb->insert(
            $this->redirects_table,
            array(
                'source_url' => $source,
                'target_url' => $target,
                'redirect_type' => $type,
                'enabled' => 1,
            ),
            array('%s', '%s', '%s', '%d')
        );

        if ($result) {
            wp_send_json_success(array('message' => __('Redirect added successfully', 'nerdy-seo')));
        } else {
            wp_send_json_error(array('message' => __('Error adding redirect', 'nerdy-seo')));
        }
    }

    /**
     * AJAX: Delete redirect
     */
    public function ajax_delete_redirect() {
        check_ajax_referer('nerdy_seo_redirects', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error();
        }

        global $wpdb;

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        $wpdb->delete($this->redirects_table, array('id' => $id), array('%d'));

        wp_send_json_success();
    }

    /**
     * AJAX: Toggle redirect
     */
    public function ajax_toggle_redirect() {
        check_ajax_referer('nerdy_seo_redirects', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error();
        }

        global $wpdb;

        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;

        $wpdb->query("UPDATE {$this->redirects_table} SET enabled = NOT enabled WHERE id = $id");

        wp_send_json_success();
    }

    /**
     * AJAX: Clear 404 logs
     */
    public function ajax_clear_404_logs() {
        check_ajax_referer('nerdy_seo_redirects', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error();
        }

        global $wpdb;

        $wpdb->query("TRUNCATE TABLE {$this->logs_table}");

        wp_send_json_success();
    }

    /**
     * AJAX: Delete specific 404 logs
     */
    public function ajax_delete_404_logs() {
        check_ajax_referer('nerdy_seo_redirects', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'nerdy-seo')));
        }

        global $wpdb;

        $ids = isset($_POST['ids']) ? array_map('intval', (array) $_POST['ids']) : array();

        if (empty($ids)) {
            wp_send_json_error(array('message' => __('No IDs provided', 'nerdy-seo')));
        }

        $placeholders = implode(',', array_fill(0, count($ids), '%d'));
        $wpdb->query($wpdb->prepare("DELETE FROM {$this->logs_table} WHERE id IN ($placeholders)", $ids));

        wp_send_json_success(array('message' => sprintf(__('Deleted %d log entries', 'nerdy-seo'), count($ids))));
    }

    /**
     * AJAX: Export redirects
     */
    public function ajax_export_redirects() {
        check_ajax_referer('nerdy_seo_redirects', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die(__('Permission denied', 'nerdy-seo'));
        }

        global $wpdb;

        $redirects = $wpdb->get_results("SELECT source_url, target_url, redirect_type FROM {$this->redirects_table}", ARRAY_A);

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="redirects-' . gmdate('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, array('source_url', 'target_url', 'redirect_type'));

        foreach ($redirects as $redirect) {
            fputcsv($output, $redirect);
        }

        fclose($output);
        exit;
    }

    /**
     * AJAX: Import redirects
     */
    public function ajax_import_redirects() {
        check_ajax_referer('nerdy_seo_redirects', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'nerdy-seo')));
        }

        global $wpdb;

        $csv = isset($_POST['csv']) ? $_POST['csv'] : '';

        if (empty($csv)) {
            wp_send_json_error(array('message' => __('No CSV data provided', 'nerdy-seo')));
        }

        $lines = explode("\n", $csv);
        $imported = 0;

        // Skip header row
        array_shift($lines);

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $data = str_getcsv($line);

            if (count($data) < 2) continue;

            $source = sanitize_text_field($data[0]);
            $target = sanitize_text_field($data[1]);
            $type = isset($data[2]) ? sanitize_text_field($data[2]) : '301';

            if (!str_starts_with($source, '/')) {
                $source = '/' . $source;
            }

            $wpdb->insert(
                $this->redirects_table,
                array(
                    'source_url' => $source,
                    'target_url' => $target,
                    'redirect_type' => $type,
                    'enabled' => 1,
                ),
                array('%s', '%s', '%s', '%d')
            );

            $imported++;
        }

        wp_send_json_success(array('message' => sprintf(__('Imported %d redirects', 'nerdy-seo'), $imported)));
    }
}

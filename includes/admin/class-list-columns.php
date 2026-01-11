<?php
/**
 * Post/Page List Table Columns
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * List Columns class
 */
class Nerdy_SEO_List_Columns {

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
        // Add columns to post types
        add_filter('manage_posts_columns', array($this, 'add_columns'));
        add_filter('manage_pages_columns', array($this, 'add_columns'));

        // Populate column content
        add_action('manage_posts_custom_column', array($this, 'populate_columns'), 10, 2);
        add_action('manage_pages_custom_column', array($this, 'populate_columns'), 10, 2);

        // Make columns sortable (optional)
        add_filter('manage_edit-post_sortable_columns', array($this, 'make_sortable'));
        add_filter('manage_edit-page_sortable_columns', array($this, 'make_sortable'));

        // Quick edit
        add_action('quick_edit_custom_box', array($this, 'add_quick_edit'), 10, 2);
        add_action('save_post', array($this, 'save_quick_edit'), 10, 2);

        // Bulk edit
        add_action('bulk_edit_custom_box', array($this, 'add_bulk_edit'), 10, 2);

        // Admin footer for quick edit script
        add_action('admin_footer', array($this, 'quick_edit_script'));

        // AJAX handler for inline edit
        add_action('wp_ajax_nerdy_seo_inline_save', array($this, 'ajax_inline_save'));
        add_action('wp_ajax_nerdy_seo_save_column', array($this, 'ajax_save_column'));
    }

    /**
     * Add SEO columns
     */
    public function add_columns($columns) {
        // Insert after title column
        $new_columns = array();
        foreach ($columns as $key => $value) {
            $new_columns[$key] = $value;
            if ($key === 'title') {
                $new_columns['nerdy_seo_title'] = __('SEO Title', 'nerdy-seo');
                $new_columns['nerdy_seo_description'] = __('Meta Description', 'nerdy-seo');
            }
        }
        return $new_columns;
    }

    /**
     * Populate column content
     */
    public function populate_columns($column, $post_id) {
        if ($column === 'nerdy_seo_title') {
            $seo_title = get_post_meta($post_id, '_nerdy_seo_title', true);

            // Hidden data for quick edit
            echo '<span class="nerdy_seo_title_data hidden">' . esc_html($seo_title) . '</span>';

            echo '<div class="nerdy-seo-inline-edit-wrapper" data-post-id="' . esc_attr($post_id) . '" data-field="title">';

            // Display mode
            echo '<div class="nerdy-seo-display-mode">';
            if ($seo_title) {
                $length = mb_strlen($seo_title);
                $color = $length >= 50 && $length <= 60 ? '#46b450' : ($length > 60 ? '#dc3232' : '#ffb900');
                echo '<span class="nerdy-seo-length" style="color: ' . esc_attr($color) . ';">(' . esc_html($length) . ')</span> ';
                echo '<span class="nerdy-seo-text">' . esc_html(wp_trim_words($seo_title, 8)) . '</span>';
            } else {
                echo '<span style="color: #999;">' . __('Not set', 'nerdy-seo') . '</span>';
            }
            echo ' <button type="button" class="button-link nerdy-seo-edit-btn" title="' . __('Edit', 'nerdy-seo') . '">';
            echo '<span class="dashicons dashicons-edit"></span>';
            echo '</button>';
            echo '</div>';

            // Edit mode
            echo '<div class="nerdy-seo-edit-mode" style="display: none;">';
            echo '<input type="text" class="nerdy-seo-inline-input" value="' . esc_attr($seo_title) . '" />';
            echo '<div class="nerdy-seo-inline-actions">';
            echo '<button type="button" class="button button-small nerdy-seo-save-btn">' . __('Save', 'nerdy-seo') . '</button> ';
            echo '<button type="button" class="button button-small nerdy-seo-cancel-btn">' . __('Cancel', 'nerdy-seo') . '</button> ';
            echo '<button type="button" class="button button-small button-primary nerdy-seo-ai-inline-btn" data-post-id="' . esc_attr($post_id) . '" title="' . __('Generate with AI', 'nerdy-seo') . '">';
            echo '<span class="dashicons dashicons-superhero" style="font-size: 13px; width: 13px; height: 13px; margin-top: 5px;"></span> ';
            echo __('AI', 'nerdy-seo');
            echo '</button>';
            echo '<span class="nerdy-seo-char-count" style="margin-left: 10px; font-size: 11px; color: #666;">0</span>';
            echo '</div>';
            echo '</div>';

            echo '</div>';
        }

        if ($column === 'nerdy_seo_description') {
            $seo_desc = get_post_meta($post_id, '_nerdy_seo_description', true);

            // Hidden data for quick edit
            echo '<span class="nerdy_seo_desc_data hidden">' . esc_html($seo_desc) . '</span>';

            echo '<div class="nerdy-seo-inline-edit-wrapper" data-post-id="' . esc_attr($post_id) . '" data-field="description">';

            // Display mode
            echo '<div class="nerdy-seo-display-mode">';
            if ($seo_desc) {
                $length = mb_strlen($seo_desc);
                $color = $length >= 150 && $length <= 160 ? '#46b450' : ($length > 160 ? '#dc3232' : '#ffb900');
                echo '<span class="nerdy-seo-length" style="color: ' . esc_attr($color) . ';">(' . esc_html($length) . ')</span> ';
                echo '<span class="nerdy-seo-text">' . esc_html(wp_trim_words($seo_desc, 12)) . '</span>';
            } else {
                echo '<span style="color: #999;">' . __('Not set', 'nerdy-seo') . '</span>';
            }
            echo ' <button type="button" class="button-link nerdy-seo-edit-btn" title="' . __('Edit', 'nerdy-seo') . '">';
            echo '<span class="dashicons dashicons-edit"></span>';
            echo '</button>';
            echo '</div>';

            // Edit mode
            echo '<div class="nerdy-seo-edit-mode" style="display: none;">';
            echo '<textarea class="nerdy-seo-inline-textarea" rows="3">' . esc_textarea($seo_desc) . '</textarea>';
            echo '<div class="nerdy-seo-inline-actions">';
            echo '<button type="button" class="button button-small nerdy-seo-save-btn">' . __('Save', 'nerdy-seo') . '</button> ';
            echo '<button type="button" class="button button-small nerdy-seo-cancel-btn">' . __('Cancel', 'nerdy-seo') . '</button> ';
            echo '<button type="button" class="button button-small button-primary nerdy-seo-ai-inline-btn" data-post-id="' . esc_attr($post_id) . '" title="' . __('Generate with AI', 'nerdy-seo') . '">';
            echo '<span class="dashicons dashicons-superhero" style="font-size: 13px; width: 13px; height: 13px; margin-top: 5px;"></span> ';
            echo __('AI', 'nerdy-seo');
            echo '</button>';
            echo '<span class="nerdy-seo-char-count" style="margin-left: 10px; font-size: 11px; color: #666;">0</span>';
            echo '</div>';
            echo '</div>';

            echo '</div>';
        }
    }

    /**
     * Make columns sortable
     */
    public function make_sortable($columns) {
        $columns['nerdy_seo_title'] = 'nerdy_seo_title';
        return $columns;
    }

    /**
     * Add quick edit fields
     */
    public function add_quick_edit($column_name, $post_type) {
        if (!in_array($column_name, array('nerdy_seo_title', 'nerdy_seo_description'))) {
            return;
        }

        ?>
        <fieldset class="inline-edit-col-right nerdy-seo-quick-edit">
            <div class="inline-edit-col">
                <label>
                    <span class="title"><?php esc_html_e('SEO Title', 'nerdy-seo'); ?></span>
                    <span class="input-text-wrap">
                        <input type="text" name="nerdy_seo_title" class="nerdy-seo-quick-title" value="" />
                        <span class="nerdy-seo-title-count" style="font-size: 11px; color: #666;">0 <?php esc_html_e('characters', 'nerdy-seo'); ?></span>
                    </span>
                </label>
            </div>
            <div class="inline-edit-col">
                <label>
                    <span class="title"><?php esc_html_e('Meta Description', 'nerdy-seo'); ?></span>
                    <span class="input-text-wrap">
                        <textarea name="nerdy_seo_description" class="nerdy-seo-quick-desc" rows="3"></textarea>
                        <span class="nerdy-seo-desc-count" style="font-size: 11px; color: #666;">0 <?php esc_html_e('characters', 'nerdy-seo'); ?></span>
                    </span>
                </label>
            </div>
        </fieldset>
        <?php
    }

    /**
     * Add bulk edit fields
     */
    public function add_bulk_edit($column_name, $post_type) {
        if (!in_array($column_name, array('nerdy_seo_title', 'nerdy_seo_description'))) {
            return;
        }

        ?>
        <fieldset class="inline-edit-col-right nerdy-seo-bulk-edit">
            <div class="inline-edit-col">
                <div class="inline-edit-group">
                    <p class="description"><?php esc_html_e('Note: Bulk editing SEO fields will overwrite existing values for all selected posts.', 'nerdy-seo'); ?></p>
                    <label>
                        <span class="title"><?php esc_html_e('SEO Title', 'nerdy-seo'); ?></span>
                        <span class="input-text-wrap">
                            <input type="text" name="nerdy_seo_title" value="" placeholder="<?php esc_html_e('Leave empty to keep existing values', 'nerdy-seo'); ?>" />
                        </span>
                    </label>
                    <label>
                        <span class="title"><?php esc_html_e('Meta Description', 'nerdy-seo'); ?></span>
                        <span class="input-text-wrap">
                            <textarea name="nerdy_seo_description" rows="3" placeholder="<?php esc_html_e('Leave empty to keep existing values', 'nerdy-seo'); ?>"></textarea>
                        </span>
                    </label>
                </div>
            </div>
        </fieldset>
        <?php
    }

    /**
     * Save quick edit
     */
    public function save_quick_edit($post_id, $post) {
        // Check if this is an autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check user permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save SEO title
        if (isset($_POST['nerdy_seo_title'])) {
            update_post_meta($post_id, '_nerdy_seo_title', sanitize_text_field($_POST['nerdy_seo_title']));
        }

        // Save SEO description
        if (isset($_POST['nerdy_seo_description'])) {
            update_post_meta($post_id, '_nerdy_seo_description', sanitize_textarea_field($_POST['nerdy_seo_description']));
        }
    }

    /**
     * Quick edit JavaScript
     */
    public function quick_edit_script() {
        global $current_screen;

        if (!in_array($current_screen->id, array('edit-post', 'edit-page'))) {
            return;
        }

        ?>
        <script type="text/javascript">
        (function($) {
            // Inline editing
            $(document).on('click', '.nerdy-seo-edit-btn', function(e) {
                e.preventDefault();
                var $wrapper = $(this).closest('.nerdy-seo-inline-edit-wrapper');
                var $display = $wrapper.find('.nerdy-seo-display-mode');
                var $edit = $wrapper.find('.nerdy-seo-edit-mode');
                var $input = $edit.find('.nerdy-seo-inline-input, .nerdy-seo-inline-textarea');

                // Hide display, show edit
                $display.hide();
                $edit.show();
                $input.focus();

                // Update character count
                updateInlineCharCount($wrapper);
            });

            // Cancel inline edit
            $(document).on('click', '.nerdy-seo-cancel-btn', function(e) {
                e.preventDefault();
                var $wrapper = $(this).closest('.nerdy-seo-inline-edit-wrapper');
                var $display = $wrapper.find('.nerdy-seo-display-mode');
                var $edit = $wrapper.find('.nerdy-seo-edit-mode');
                var $input = $edit.find('.nerdy-seo-inline-input, .nerdy-seo-inline-textarea');

                // Reset to original value
                var originalValue = $input.data('original-value') || $input.val();
                $input.val(originalValue);

                // Hide edit, show display
                $edit.hide();
                $display.show();
            });

            // Save inline edit
            $(document).on('click', '.nerdy-seo-save-btn', function(e) {
                e.preventDefault();
                var $btn = $(this);
                var $wrapper = $btn.closest('.nerdy-seo-inline-edit-wrapper');
                var $display = $wrapper.find('.nerdy-seo-display-mode');
                var $edit = $wrapper.find('.nerdy-seo-edit-mode');
                var $input = $edit.find('.nerdy-seo-inline-input, .nerdy-seo-inline-textarea');

                var postId = $wrapper.data('post-id');
                var field = $wrapper.data('field');
                var value = $input.val();

                // Disable button during save
                $btn.prop('disabled', true).text('<?php esc_html_e('Saving...', 'nerdy-seo'); ?>');

                // AJAX save
                $.post(ajaxurl, {
                    action: 'nerdy_seo_save_column',
                    nonce: '<?php echo wp_create_nonce('nerdy_seo_column_edit'); ?>',
                    post_id: postId,
                    field: field,
                    value: value
                }, function(response) {
                    if (response.success) {
                        // Update display text
                        var length = value.length;
                        var color = '#666';

                        if (field === 'title') {
                            if (length >= 50 && length <= 60) {
                                color = '#46b450';
                            } else if (length > 60) {
                                color = '#dc3232';
                            } else if (length > 0) {
                                color = '#ffb900';
                            }
                        } else {
                            if (length >= 150 && length <= 160) {
                                color = '#46b450';
                            } else if (length > 160) {
                                color = '#dc3232';
                            } else if (length > 0) {
                                color = '#ffb900';
                            }
                        }

                        var displayHTML = '';
                        if (value) {
                            var truncated = value.split(' ').slice(0, field === 'title' ? 8 : 12).join(' ');
                            if (value.split(' ').length > (field === 'title' ? 8 : 12)) {
                                truncated += '...';
                            }
                            displayHTML = '<span class="nerdy-seo-length" style="color: ' + color + ';">(' + length + ')</span> ';
                            displayHTML += '<span class="nerdy-seo-text">' + $('<div>').text(truncated).html() + '</span>';
                        } else {
                            displayHTML = '<span style="color: #999;"><?php esc_html_e('Not set', 'nerdy-seo'); ?></span>';
                        }

                        displayHTML += ' <button type="button" class="button-link nerdy-seo-edit-btn" title="<?php esc_html_e('Edit', 'nerdy-seo'); ?>">';
                        displayHTML += '<span class="dashicons dashicons-edit"></span>';
                        displayHTML += '</button>';

                        $display.html(displayHTML);

                        // Hide edit, show display
                        $edit.hide();
                        $display.show();

                        // Show success indicator briefly
                        $wrapper.css('background', '#d4edda');
                        setTimeout(function() {
                            $wrapper.css('background', '');
                        }, 1000);
                    } else {
                        alert(response.data.message || '<?php esc_html_e('Error saving', 'nerdy-seo'); ?>');
                    }

                    // Re-enable button
                    $btn.prop('disabled', false).text('<?php esc_html_e('Save', 'nerdy-seo'); ?>');
                });
            });

            // Character counter for inline edit
            function updateInlineCharCount($wrapper) {
                var $input = $wrapper.find('.nerdy-seo-inline-input, .nerdy-seo-inline-textarea');
                var $counter = $wrapper.find('.nerdy-seo-char-count');
                var field = $wrapper.data('field');
                var length = $input.val().length;
                var color = '#666';

                if (field === 'title') {
                    if (length >= 50 && length <= 60) {
                        color = '#46b450';
                    } else if (length > 60) {
                        color = '#dc3232';
                    } else if (length > 0) {
                        color = '#ffb900';
                    }
                } else {
                    if (length >= 150 && length <= 160) {
                        color = '#46b450';
                    } else if (length > 160) {
                        color = '#dc3232';
                    } else if (length > 0) {
                        color = '#ffb900';
                    }
                }

                $counter.text(length + ' <?php esc_html_e('characters', 'nerdy-seo'); ?>').css('color', color);
            }

            // Update count on input
            $(document).on('input', '.nerdy-seo-inline-input, .nerdy-seo-inline-textarea', function() {
                var $wrapper = $(this).closest('.nerdy-seo-inline-edit-wrapper');
                updateInlineCharCount($wrapper);
            });

            // Quick Edit (legacy support)
            var wp_inline_edit = inlineEditPost.edit;
            inlineEditPost.edit = function(id) {
                wp_inline_edit.apply(this, arguments);

                var post_id = 0;
                if (typeof(id) == 'object') {
                    post_id = parseInt(this.getId(id));
                }

                if (post_id > 0) {
                    var $row = $('#post-' + post_id);
                    var seo_title = $row.find('.nerdy_seo_title_data').text();
                    var seo_desc = $row.find('.nerdy_seo_desc_data').text();

                    $('.nerdy-seo-quick-title').val(seo_title);
                    $('.nerdy-seo-quick-desc').val(seo_desc);

                    updateCharCount($('.nerdy-seo-quick-title'), $('.nerdy-seo-title-count'));
                    updateCharCount($('.nerdy-seo-quick-desc'), $('.nerdy-seo-desc-count'));
                }
            };

            function updateCharCount($input, $counter) {
                var length = $input.val().length;
                var color = '#666';

                if ($input.hasClass('nerdy-seo-quick-title')) {
                    if (length >= 50 && length <= 60) {
                        color = '#46b450';
                    } else if (length > 60) {
                        color = '#dc3232';
                    } else {
                        color = '#ffb900';
                    }
                } else {
                    if (length >= 150 && length <= 160) {
                        color = '#46b450';
                    } else if (length > 160) {
                        color = '#dc3232';
                    } else {
                        color = '#ffb900';
                    }
                }

                $counter.text(length + ' <?php esc_html_e('characters', 'nerdy-seo'); ?>').css('color', color);
            }

            $(document).on('input', '.nerdy-seo-quick-title', function() {
                updateCharCount($(this), $('.nerdy-seo-title-count'));
            });

            $(document).on('input', '.nerdy-seo-quick-desc', function() {
                updateCharCount($(this), $('.nerdy-seo-desc-count'));
            });
        })(jQuery);
        </script>

        <style>
        .nerdy-seo-inline-edit-wrapper {
            position: relative;
            padding: 8px;
            margin: -8px;
            transition: background 0.3s ease;
        }
        .nerdy-seo-display-mode {
            font-size: 12px;
            line-height: 1.4;
        }
        .nerdy-seo-length {
            font-weight: 600;
            font-size: 11px;
        }
        .nerdy-seo-text {
            color: #646970;
        }
        .nerdy-seo-edit-btn {
            color: #2271b1;
            text-decoration: none;
            padding: 0;
            border: none;
            background: none;
            cursor: pointer;
            vertical-align: middle;
        }
        .nerdy-seo-edit-btn:hover {
            color: #135e96;
        }
        .nerdy-seo-edit-btn .dashicons {
            font-size: 16px;
            width: 16px;
            height: 16px;
            vertical-align: middle;
        }
        .nerdy-seo-edit-mode {
            padding: 5px 0;
        }
        .nerdy-seo-inline-input,
        .nerdy-seo-inline-textarea {
            width: 100%;
            max-width: 400px;
            margin-bottom: 8px;
            font-size: 13px;
        }
        .nerdy-seo-inline-textarea {
            resize: vertical;
        }
        .nerdy-seo-inline-actions {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .nerdy-seo-char-count {
            font-weight: 600;
        }
        .nerdy-seo-quick-edit .inline-edit-col {
            margin-bottom: 15px;
        }
        .nerdy-seo-quick-title,
        .nerdy-seo-quick-desc {
            width: 100%;
        }
        .nerdy-seo-title-count,
        .nerdy-seo-desc-count {
            display: block;
            margin-top: 4px;
        }
        /* Hide the data in the table */
        .nerdy_seo_title_data,
        .nerdy_seo_desc_data {
            display: none !important;
        }
        </style>
        <?php
    }

    /**
     * AJAX: Inline save
     */
    public function ajax_inline_save() {
        check_ajax_referer('inlineeditnonce', '_inline_edit');

        if (!current_user_can('edit_posts')) {
            wp_die(__('You do not have permission to edit posts.', 'nerdy-seo'));
        }

        $post_id = isset($_POST['post_ID']) ? intval($_POST['post_ID']) : 0;

        if (!$post_id) {
            wp_die(__('Invalid post ID.', 'nerdy-seo'));
        }

        // Save SEO data
        if (isset($_POST['nerdy_seo_title'])) {
            update_post_meta($post_id, '_nerdy_seo_title', sanitize_text_field($_POST['nerdy_seo_title']));
        }

        if (isset($_POST['nerdy_seo_description'])) {
            update_post_meta($post_id, '_nerdy_seo_description', sanitize_textarea_field($_POST['nerdy_seo_description']));
        }

        wp_die();
    }

    /**
     * AJAX: Save column inline edit
     */
    public function ajax_save_column() {
        check_ajax_referer('nerdy_seo_column_edit', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied', 'nerdy-seo')));
        }

        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        $field = isset($_POST['field']) ? sanitize_text_field($_POST['field']) : '';
        $value = isset($_POST['value']) ? $_POST['value'] : '';

        if (!$post_id || !in_array($field, array('title', 'description'))) {
            wp_send_json_error(array('message' => __('Invalid request', 'nerdy-seo')));
        }

        // Check if user can edit this specific post
        if (!current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => __('You do not have permission to edit this post', 'nerdy-seo')));
        }

        $meta_key = $field === 'title' ? '_nerdy_seo_title' : '_nerdy_seo_description';
        $sanitized_value = $field === 'title' ? sanitize_text_field($value) : sanitize_textarea_field($value);

        update_post_meta($post_id, $meta_key, $sanitized_value);

        wp_send_json_success(array('message' => __('Saved successfully', 'nerdy-seo')));
    }
}

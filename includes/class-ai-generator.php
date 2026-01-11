<?php
/**
 * AI Content Generator
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI Generator class
 */
class Nerdy_SEO_AI_Generator {

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
        // Enqueue scripts for post list
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));

        // AJAX handler for generating meta descriptions
        add_action('wp_ajax_nerdy_seo_generate_meta_description', array($this, 'ajax_generate_meta_description'));

        // Admin footer for modal
        add_action('admin_footer', array($this, 'render_modal'));
    }

    /**
     * Enqueue scripts
     */
    public function enqueue_scripts($hook) {
        // Only on post list pages and post editor pages
        if (!in_array($hook, array('edit.php', 'post.php', 'post-new.php'))) {
            return;
        }

        // Always enqueue - we'll handle missing API key in the modal
        wp_enqueue_style(
            'nerdy-seo-ai-generator',
            NERDY_SEO_PLUGIN_URL . 'assets/css/ai-generator.css',
            array(),
            filemtime(NERDY_SEO_PLUGIN_DIR . 'assets/css/ai-generator.css')
        );

        wp_enqueue_script(
            'nerdy-seo-ai-generator',
            NERDY_SEO_PLUGIN_URL . 'assets/js/ai-generator.js',
            array('jquery', 'inline-edit-post'),
            filemtime(NERDY_SEO_PLUGIN_DIR . 'assets/js/ai-generator.js'),
            true
        );

        wp_localize_script('nerdy-seo-ai-generator', 'nerdySeoAI', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('nerdy_seo_ai_generate'),
        ));
    }

    /**
     * AJAX handler for generating meta descriptions
     */
    public function ajax_generate_meta_description() {
        check_ajax_referer('nerdy_seo_ai_generate', 'nonce');

        if (!current_user_can('edit_posts')) {
            wp_send_json_error(array('message' => __('Permission denied', 'nerdy-seo')));
        }

        $post_id = intval($_POST['post_id']);
        $field = sanitize_text_field($_POST['field']); // 'title' or 'description'
        $tone = sanitize_text_field($_POST['tone']);
        $focus_keywords = sanitize_text_field($_POST['focus_keywords']);

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(array('message' => __('Post not found', 'nerdy-seo')));
        }

        // Get AI provider settings
        $ai_provider = get_option('nerdy_seo_ai_provider', 'openai');

        try {
            if ($ai_provider === 'openai') {
                $suggestions = $this->generate_with_openai($post, $field, $tone, $focus_keywords);
            } else {
                $suggestions = $this->generate_with_gemini($post, $field, $tone, $focus_keywords);
            }

            wp_send_json_success(array('suggestions' => $suggestions));
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Generate meta descriptions with OpenAI
     */
    private function generate_with_openai($post, $field, $tone, $focus_keywords) {
        $api_key = get_option('nerdy_seo_ai_openai_key', '');
        $model = get_option('nerdy_seo_ai_openai_model', 'gpt-4o');

        if (empty($api_key)) {
            throw new Exception(__('OpenAI API key not configured', 'nerdy-seo'));
        }

        // Prepare content
        $content = wp_strip_all_tags($post->post_content);
        $content = substr($content, 0, 3000); // Limit content length

        // Build prompt
        $prompt = $this->build_prompt($post->post_title, $content, $field, $tone, $focus_keywords);

        // System message based on field type
        $system_message = $field === 'title'
            ? 'You are an expert SEO copywriter. Generate SEO titles that are compelling, concise (50-60 characters), and optimized for search engines.'
            : 'You are an expert SEO copywriter. Generate meta descriptions that are compelling, concise (150-160 characters), and optimized for search engines.';

        // Make API request
        $response = wp_remote_post('https://api.openai.com/v1/chat/completions', array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode(array(
                'model' => $model,
                'messages' => array(
                    array(
                        'role' => 'system',
                        'content' => $system_message
                    ),
                    array(
                        'role' => 'user',
                        'content' => $prompt
                    )
                ),
                'temperature' => 0.8,
                'max_tokens' => 500,
            )),
        ));

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            throw new Exception($body['error']['message']);
        }

        if (!isset($body['choices'][0]['message']['content'])) {
            throw new Exception(__('Invalid response from OpenAI', 'nerdy-seo'));
        }

        return $this->parse_suggestions($body['choices'][0]['message']['content']);
    }

    /**
     * Generate meta descriptions with Gemini
     */
    private function generate_with_gemini($post, $field, $tone, $focus_keywords) {
        $api_key = get_option('nerdy_seo_ai_gemini_key', '');
        $model = get_option('nerdy_seo_ai_gemini_model', 'gemini-2.0-flash-exp');

        if (empty($api_key)) {
            throw new Exception(__('Gemini API key not configured', 'nerdy-seo'));
        }

        // Prepare content
        $content = wp_strip_all_tags($post->post_content);
        $content = substr($content, 0, 3000); // Limit content length

        // Build prompt
        $prompt = $this->build_prompt($post->post_title, $content, $field, $tone, $focus_keywords);

        // System message based on field type
        $system_message = $field === 'title'
            ? "You are an expert SEO copywriter. Generate SEO titles that are compelling, concise (50-60 characters), and optimized for search engines.\n\n"
            : "You are an expert SEO copywriter. Generate meta descriptions that are compelling, concise (150-160 characters), and optimized for search engines.\n\n";

        $full_prompt = $system_message . $prompt;

        // Make API request
        $response = wp_remote_post(
            'https://generativelanguage.googleapis.com/v1beta/models/' . $model . ':generateContent?key=' . $api_key,
            array(
                'timeout' => 30,
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
                'body' => json_encode(array(
                    'contents' => array(
                        array(
                            'parts' => array(
                                array('text' => $full_prompt)
                            )
                        )
                    ),
                    'generationConfig' => array(
                        'temperature' => 0.8,
                        'maxOutputTokens' => 500,
                    )
                )),
            )
        );

        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['error'])) {
            throw new Exception($body['error']['message']);
        }

        if (!isset($body['candidates'][0]['content']['parts'][0]['text'])) {
            throw new Exception(__('Invalid response from Gemini', 'nerdy-seo'));
        }

        return $this->parse_suggestions($body['candidates'][0]['content']['parts'][0]['text']);
    }

    /**
     * Build prompt for AI
     */
    private function build_prompt($title, $content, $field, $tone, $focus_keywords) {
        if ($field === 'title') {
            $prompt = "Generate 5 different SEO titles for the following content.\n\n";
            $prompt .= "Current Title: " . $title . "\n\n";
            $prompt .= "Content: " . $content . "\n\n";

            if (!empty($focus_keywords)) {
                $prompt .= "Focus Keywords: " . $focus_keywords . "\n\n";
            }

            $prompt .= "Tone: " . $tone . "\n\n";
            $prompt .= "Requirements:\n";
            $prompt .= "- Each SEO title should be 50-60 characters\n";
            $prompt .= "- Be compelling and encourage clicks\n";
            $prompt .= "- Naturally incorporate the focus keywords if provided\n";
            $prompt .= "- Match the specified tone\n";
            $prompt .= "- Include the main topic clearly\n\n";
            $prompt .= "Format: Return exactly 5 SEO titles, one per line, numbered 1-5.";
        } else {
            $prompt = "Generate 5 different meta descriptions for the following content.\n\n";
            $prompt .= "Title: " . $title . "\n\n";
            $prompt .= "Content: " . $content . "\n\n";

            if (!empty($focus_keywords)) {
                $prompt .= "Focus Keywords: " . $focus_keywords . "\n\n";
            }

            $prompt .= "Tone: " . $tone . "\n\n";
            $prompt .= "Requirements:\n";
            $prompt .= "- Each meta description should be 150-160 characters\n";
            $prompt .= "- Include a clear call-to-action\n";
            $prompt .= "- Be compelling and encourage clicks\n";
            $prompt .= "- Naturally incorporate the focus keywords if provided\n";
            $prompt .= "- Match the specified tone\n\n";
            $prompt .= "Format: Return exactly 5 meta descriptions, one per line, numbered 1-5.";
        }

        return $prompt;
    }

    /**
     * Parse AI suggestions
     */
    private function parse_suggestions($content) {
        $lines = explode("\n", trim($content));
        $suggestions = array();

        foreach ($lines as $line) {
            $line = trim($line);

            // Remove numbering (1., 2., etc.) and quotes
            $line = preg_replace('/^[\d]+[\.\)\:]?\s*/', '', $line);
            $line = trim($line, '"\'');

            if (!empty($line) && strlen($line) > 50) {
                $suggestions[] = $line;
            }

            if (count($suggestions) >= 5) {
                break;
            }
        }

        // If we don't have 5, pad with message
        while (count($suggestions) < 5) {
            $suggestions[] = __('No suggestion available', 'nerdy-seo');
        }

        return $suggestions;
    }

    /**
     * Render modal HTML
     */
    public function render_modal() {
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->base, array('edit', 'post'))) {
            return;
        }

        ?>
        <div id="nerdy-seo-ai-modal" class="nerdy-seo-modal" style="display: none;">
            <div class="nerdy-seo-modal-overlay"></div>
            <div class="nerdy-seo-modal-content">
                <div class="nerdy-seo-modal-header">
                    <h2>
                        <span class="dashicons dashicons-superhero"></span>
                        <?php esc_html_e('Generate Meta Description with AI', 'nerdy-seo'); ?>
                    </h2>
                    <button class="nerdy-seo-modal-close">&times;</button>
                </div>

                <div class="nerdy-seo-modal-body">
                    <div class="nerdy-seo-ai-form">
                        <div class="nerdy-seo-form-field">
                            <label for="nerdy-seo-ai-tone"><?php esc_html_e('Tone', 'nerdy-seo'); ?></label>
                            <select id="nerdy-seo-ai-tone" class="widefat">
                                <option value="professional"><?php esc_html_e('Professional', 'nerdy-seo'); ?></option>
                                <option value="conversational"><?php esc_html_e('Conversational', 'nerdy-seo'); ?></option>
                                <option value="friendly"><?php esc_html_e('Friendly', 'nerdy-seo'); ?></option>
                                <option value="authoritative"><?php esc_html_e('Authoritative', 'nerdy-seo'); ?></option>
                                <option value="persuasive"><?php esc_html_e('Persuasive', 'nerdy-seo'); ?></option>
                                <option value="educational"><?php esc_html_e('Educational', 'nerdy-seo'); ?></option>
                            </select>
                        </div>

                        <div class="nerdy-seo-form-field">
                            <label for="nerdy-seo-ai-keywords"><?php esc_html_e('Focus Keywords (Optional)', 'nerdy-seo'); ?></label>
                            <input type="text" id="nerdy-seo-ai-keywords" class="widefat" placeholder="<?php esc_html_e('Enter keywords separated by commas', 'nerdy-seo'); ?>" />
                        </div>

                        <div class="nerdy-seo-form-actions">
                            <button type="button" class="button button-primary button-large" id="nerdy-seo-ai-generate-btn">
                                <span class="dashicons dashicons-superhero"></span>
                                <?php esc_html_e('Generate Suggestions', 'nerdy-seo'); ?>
                            </button>
                        </div>
                    </div>

                    <div class="nerdy-seo-ai-loading" style="display: none;">
                        <div class="nerdy-seo-spinner"></div>
                        <p><?php esc_html_e('Generating meta descriptions...', 'nerdy-seo'); ?></p>
                    </div>

                    <div class="nerdy-seo-ai-results" style="display: none;">
                        <h3><?php esc_html_e('Select a Meta Description', 'nerdy-seo'); ?></h3>
                        <div class="nerdy-seo-suggestions-list"></div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

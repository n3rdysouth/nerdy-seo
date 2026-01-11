<?php
/**
 * Schema Markup functionality
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Schema class
 */
class Nerdy_SEO_Schema {

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
        add_action('wp_head', array($this, 'output_schema'), 20);
        add_action('add_meta_boxes', array($this, 'add_schema_meta_box'));
        add_action('save_post', array($this, 'save_schema_meta_box'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
    }

    /**
     * Add schema meta box
     */
    public function add_schema_meta_box() {
        $post_types = apply_filters('nerdy_seo_post_types', get_post_types(array('public' => true), 'names'));

        foreach ($post_types as $post_type) {
            add_meta_box(
                'nerdy_seo_schema_meta_box',
                __('Schema Markup', 'nerdy-seo'),
                array($this, 'render_schema_meta_box'),
                $post_type,
                'normal',
                'default'
            );
        }
    }

    /**
     * Render schema meta box
     */
    public function render_schema_meta_box($post) {
        wp_nonce_field('nerdy_seo_schema_meta_box', 'nerdy_seo_schema_meta_box_nonce');

        $schema_type = get_post_meta($post->ID, '_nerdy_seo_schema_type', true);
        $schema_faqs = get_post_meta($post->ID, '_nerdy_seo_schema_faqs', true);
        $schema_review_item = get_post_meta($post->ID, '_nerdy_seo_schema_review_item', true);
        $schema_review_rating = get_post_meta($post->ID, '_nerdy_seo_schema_review_rating', true);
        $schema_review_author = get_post_meta($post->ID, '_nerdy_seo_schema_review_author', true);
        $schema_review_text = get_post_meta($post->ID, '_nerdy_seo_schema_review_text', true);

        // Recipe fields
        $recipe_name = get_post_meta($post->ID, '_nerdy_seo_recipe_name', true);
        $recipe_description = get_post_meta($post->ID, '_nerdy_seo_recipe_description', true);
        $recipe_prep_time = get_post_meta($post->ID, '_nerdy_seo_recipe_prep_time', true);
        $recipe_cook_time = get_post_meta($post->ID, '_nerdy_seo_recipe_cook_time', true);
        $recipe_yield = get_post_meta($post->ID, '_nerdy_seo_recipe_yield', true);
        $recipe_calories = get_post_meta($post->ID, '_nerdy_seo_recipe_calories', true);
        $recipe_ingredients = get_post_meta($post->ID, '_nerdy_seo_recipe_ingredients', true);
        $recipe_instructions = get_post_meta($post->ID, '_nerdy_seo_recipe_instructions', true);

        // Video fields
        $video_name = get_post_meta($post->ID, '_nerdy_seo_video_name', true);
        $video_description = get_post_meta($post->ID, '_nerdy_seo_video_description', true);
        $video_url = get_post_meta($post->ID, '_nerdy_seo_video_url', true);
        $video_thumbnail = get_post_meta($post->ID, '_nerdy_seo_video_thumbnail', true);
        $video_duration = get_post_meta($post->ID, '_nerdy_seo_video_duration', true);
        $video_upload_date = get_post_meta($post->ID, '_nerdy_seo_video_upload_date', true);

        // Event fields
        $event_name = get_post_meta($post->ID, '_nerdy_seo_event_name', true);
        $event_description = get_post_meta($post->ID, '_nerdy_seo_event_description', true);
        $event_start_date = get_post_meta($post->ID, '_nerdy_seo_event_start_date', true);
        $event_end_date = get_post_meta($post->ID, '_nerdy_seo_event_end_date', true);
        $event_location = get_post_meta($post->ID, '_nerdy_seo_event_location', true);
        $event_online = get_post_meta($post->ID, '_nerdy_seo_event_online', true);
        $event_url = get_post_meta($post->ID, '_nerdy_seo_event_url', true);
        $event_price = get_post_meta($post->ID, '_nerdy_seo_event_price', true);
        $event_currency = get_post_meta($post->ID, '_nerdy_seo_event_currency', true);

        // Course fields
        $course_name = get_post_meta($post->ID, '_nerdy_seo_course_name', true);
        $course_description = get_post_meta($post->ID, '_nerdy_seo_course_description', true);
        $course_provider = get_post_meta($post->ID, '_nerdy_seo_course_provider', true);
        $course_price = get_post_meta($post->ID, '_nerdy_seo_course_price', true);
        $course_currency = get_post_meta($post->ID, '_nerdy_seo_course_currency', true);

        // HowTo fields
        $howto_name = get_post_meta($post->ID, '_nerdy_seo_howto_name', true);
        $howto_description = get_post_meta($post->ID, '_nerdy_seo_howto_description', true);
        $howto_total_time = get_post_meta($post->ID, '_nerdy_seo_howto_total_time', true);
        $howto_steps = get_post_meta($post->ID, '_nerdy_seo_howto_steps', true);

        // JobPosting fields
        $job_title = get_post_meta($post->ID, '_nerdy_seo_job_title', true);
        $job_description = get_post_meta($post->ID, '_nerdy_seo_job_description', true);
        $job_company = get_post_meta($post->ID, '_nerdy_seo_job_company', true);
        $job_location = get_post_meta($post->ID, '_nerdy_seo_job_location', true);
        $job_salary = get_post_meta($post->ID, '_nerdy_seo_job_salary', true);
        $job_salary_currency = get_post_meta($post->ID, '_nerdy_seo_job_salary_currency', true);
        $job_employment_type = get_post_meta($post->ID, '_nerdy_seo_job_employment_type', true);
        $job_date_posted = get_post_meta($post->ID, '_nerdy_seo_job_date_posted', true);
        $job_valid_through = get_post_meta($post->ID, '_nerdy_seo_job_valid_through', true);

        ?>
        <style>
            .nerdy-seo-schema-field {
                margin-bottom: 20px;
            }
            .nerdy-seo-schema-field label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
                font-size: 13px;
            }
            .nerdy-seo-schema-field select,
            .nerdy-seo-schema-field input[type="text"],
            .nerdy-seo-schema-field input[type="number"],
            .nerdy-seo-schema-field textarea {
                width: 100%;
                max-width: 500px;
                padding: 8px;
                border: 1px solid #ddd;
                border-radius: 3px;
            }
            .nerdy-seo-schema-conditional {
                display: none;
                padding: 15px;
                background: #f9f9f9;
                border-left: 3px solid #2271b1;
                margin-top: 10px;
            }
            .nerdy-seo-schema-conditional.active {
                display: block;
            }
            .nerdy-seo-faq-item {
                background: white;
                border: 1px solid #ddd;
                padding: 15px;
                margin-bottom: 10px;
                border-radius: 3px;
            }
            .nerdy-seo-faq-item input,
            .nerdy-seo-faq-item textarea {
                width: 100%;
                margin-bottom: 10px;
            }
            .nerdy-seo-faq-item textarea {
                min-height: 60px;
            }
            .nerdy-seo-faq-remove {
                color: #dc3232;
                text-decoration: none;
                font-size: 12px;
            }
            .nerdy-seo-faq-remove:hover {
                color: #a00;
            }
            .nerdy-seo-schema-hint {
                font-size: 12px;
                color: #666;
                font-style: italic;
                margin-top: 5px;
            }
        </style>

        <div class="nerdy-seo-schema-field">
            <label for="nerdy_seo_schema_type">
                <?php esc_html_e('Schema Type', 'nerdy-seo'); ?>
            </label>
            <select id="nerdy_seo_schema_type" name="nerdy_seo_schema_type">
                <option value=""><?php esc_html_e('Default (Auto)', 'nerdy-seo'); ?></option>
                <option value="Article" <?php selected($schema_type, 'Article'); ?>><?php esc_html_e('Article', 'nerdy-seo'); ?></option>
                <option value="WebPage" <?php selected($schema_type, 'WebPage'); ?>><?php esc_html_e('Web Page', 'nerdy-seo'); ?></option>
                <option value="FAQ" <?php selected($schema_type, 'FAQ'); ?>><?php esc_html_e('FAQ Page', 'nerdy-seo'); ?></option>
                <option value="Review" <?php selected($schema_type, 'Review'); ?>><?php esc_html_e('Review', 'nerdy-seo'); ?></option>
                <option value="Product" <?php selected($schema_type, 'Product'); ?>><?php esc_html_e('Product', 'nerdy-seo'); ?></option>
                <option value="Service" <?php selected($schema_type, 'Service'); ?>><?php esc_html_e('Service', 'nerdy-seo'); ?></option>
                <option value="LocalBusiness" <?php selected($schema_type, 'LocalBusiness'); ?>><?php esc_html_e('Local Business', 'nerdy-seo'); ?></option>
                <option value="Recipe" <?php selected($schema_type, 'Recipe'); ?>><?php esc_html_e('Recipe', 'nerdy-seo'); ?></option>
                <option value="Video" <?php selected($schema_type, 'Video'); ?>><?php esc_html_e('Video', 'nerdy-seo'); ?></option>
                <option value="Event" <?php selected($schema_type, 'Event'); ?>><?php esc_html_e('Event', 'nerdy-seo'); ?></option>
                <option value="Course" <?php selected($schema_type, 'Course'); ?>><?php esc_html_e('Course', 'nerdy-seo'); ?></option>
                <option value="HowTo" <?php selected($schema_type, 'HowTo'); ?>><?php esc_html_e('HowTo Guide', 'nerdy-seo'); ?></option>
                <option value="JobPosting" <?php selected($schema_type, 'JobPosting'); ?>><?php esc_html_e('Job Posting', 'nerdy-seo'); ?></option>
            </select>
            <p class="nerdy-seo-schema-hint">
                <?php esc_html_e('Default (Auto): Blog posts = Article schema, Pages = WebPage schema. Override by selecting a specific type.', 'nerdy-seo'); ?>
            </p>
        </div>

        <!-- FAQ Schema -->
        <div id="schema-faq" class="nerdy-seo-schema-conditional <?php echo $schema_type === 'FAQ' ? 'active' : ''; ?>">
            <h4><?php esc_html_e('FAQ Items', 'nerdy-seo'); ?></h4>
            <div id="nerdy-seo-faq-items">
                <?php
                if (is_array($schema_faqs) && !empty($schema_faqs)) {
                    foreach ($schema_faqs as $index => $faq) {
                        ?>
                        <div class="nerdy-seo-faq-item" data-index="<?php echo $index; ?>">
                            <input
                                type="text"
                                name="nerdy_seo_schema_faqs[<?php echo $index; ?>][question]"
                                placeholder="<?php esc_html_e('Question', 'nerdy-seo'); ?>"
                                value="<?php echo esc_attr($faq['question'] ?? ''); ?>"
                            />
                            <textarea
                                name="nerdy_seo_schema_faqs[<?php echo $index; ?>][answer]"
                                placeholder="<?php esc_html_e('Answer', 'nerdy-seo'); ?>"
                            ><?php echo esc_textarea($faq['answer'] ?? ''); ?></textarea>
                            <a href="#" class="nerdy-seo-faq-remove"><?php esc_html_e('Remove', 'nerdy-seo'); ?></a>
                        </div>
                        <?php
                    }
                }
                ?>
            </div>
            <button type="button" class="button" id="nerdy-seo-add-faq">
                <?php esc_html_e('Add FAQ Item', 'nerdy-seo'); ?>
            </button>
        </div>

        <!-- Review Schema -->
        <div id="schema-review" class="nerdy-seo-schema-conditional <?php echo $schema_type === 'Review' ? 'active' : ''; ?>">
            <h4><?php esc_html_e('Review Details', 'nerdy-seo'); ?></h4>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_schema_review_item">
                    <?php esc_html_e('Item Being Reviewed', 'nerdy-seo'); ?>
                </label>
                <input
                    type="text"
                    id="nerdy_seo_schema_review_item"
                    name="nerdy_seo_schema_review_item"
                    value="<?php echo esc_attr($schema_review_item); ?>"
                    placeholder="<?php esc_html_e('e.g., Product Name, Service Name', 'nerdy-seo'); ?>"
                />
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_schema_review_rating">
                    <?php esc_html_e('Rating (1-5)', 'nerdy-seo'); ?>
                </label>
                <input
                    type="number"
                    id="nerdy_seo_schema_review_rating"
                    name="nerdy_seo_schema_review_rating"
                    value="<?php echo esc_attr($schema_review_rating); ?>"
                    min="1"
                    max="5"
                    step="0.1"
                />
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_schema_review_author">
                    <?php esc_html_e('Review Author', 'nerdy-seo'); ?>
                </label>
                <input
                    type="text"
                    id="nerdy_seo_schema_review_author"
                    name="nerdy_seo_schema_review_author"
                    value="<?php echo esc_attr($schema_review_author); ?>"
                    placeholder="<?php echo esc_attr(get_the_author_meta('display_name', $post->post_author)); ?>"
                />
                <p class="nerdy-seo-schema-hint">
                    <?php esc_html_e('Leave blank to use post author', 'nerdy-seo'); ?>
                </p>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_schema_review_text">
                    <?php esc_html_e('Review Text', 'nerdy-seo'); ?>
                </label>
                <textarea
                    id="nerdy_seo_schema_review_text"
                    name="nerdy_seo_schema_review_text"
                    rows="4"
                    placeholder="<?php esc_html_e('Brief summary of the review...', 'nerdy-seo'); ?>"
                ><?php echo esc_textarea($schema_review_text); ?></textarea>
            </div>
        </div>

        <!-- Recipe Schema -->
        <div id="schema-recipe" class="nerdy-seo-schema-conditional <?php echo $schema_type === 'Recipe' ? 'active' : ''; ?>">
            <h4><?php esc_html_e('Recipe Details', 'nerdy-seo'); ?></h4>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_recipe_name"><?php esc_html_e('Recipe Name', 'nerdy-seo'); ?></label>
                <input type="text" id="nerdy_seo_recipe_name" name="nerdy_seo_recipe_name" value="<?php echo esc_attr($recipe_name); ?>" placeholder="<?php echo esc_attr($post->post_title); ?>" />
                <p class="nerdy-seo-schema-hint"><?php esc_html_e('Leave blank to use post title', 'nerdy-seo'); ?></p>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_recipe_description"><?php esc_html_e('Recipe Description', 'nerdy-seo'); ?></label>
                <textarea id="nerdy_seo_recipe_description" name="nerdy_seo_recipe_description" rows="3" placeholder="<?php esc_html_e('Brief description of this recipe...', 'nerdy-seo'); ?>"><?php echo esc_textarea($recipe_description); ?></textarea>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_recipe_prep_time"><?php esc_html_e('Prep Time (minutes)', 'nerdy-seo'); ?></label>
                <input type="number" id="nerdy_seo_recipe_prep_time" name="nerdy_seo_recipe_prep_time" value="<?php echo esc_attr($recipe_prep_time); ?>" min="0" placeholder="15" />
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_recipe_cook_time"><?php esc_html_e('Cook Time (minutes)', 'nerdy-seo'); ?></label>
                <input type="number" id="nerdy_seo_recipe_cook_time" name="nerdy_seo_recipe_cook_time" value="<?php echo esc_attr($recipe_cook_time); ?>" min="0" placeholder="30" />
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_recipe_yield"><?php esc_html_e('Yield (servings)', 'nerdy-seo'); ?></label>
                <input type="text" id="nerdy_seo_recipe_yield" name="nerdy_seo_recipe_yield" value="<?php echo esc_attr($recipe_yield); ?>" placeholder="4 servings" />
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_recipe_calories"><?php esc_html_e('Calories (per serving)', 'nerdy-seo'); ?></label>
                <input type="number" id="nerdy_seo_recipe_calories" name="nerdy_seo_recipe_calories" value="<?php echo esc_attr($recipe_calories); ?>" min="0" placeholder="350" />
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_recipe_ingredients"><?php esc_html_e('Ingredients (one per line)', 'nerdy-seo'); ?></label>
                <textarea id="nerdy_seo_recipe_ingredients" name="nerdy_seo_recipe_ingredients" rows="6" placeholder="<?php esc_html_e('2 cups flour&#10;1 cup sugar&#10;3 eggs', 'nerdy-seo'); ?>"><?php echo esc_textarea($recipe_ingredients); ?></textarea>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_recipe_instructions"><?php esc_html_e('Instructions (one step per line)', 'nerdy-seo'); ?></label>
                <textarea id="nerdy_seo_recipe_instructions" name="nerdy_seo_recipe_instructions" rows="6" placeholder="<?php esc_html_e('Preheat oven to 350°F&#10;Mix dry ingredients&#10;Add wet ingredients', 'nerdy-seo'); ?>"><?php echo esc_textarea($recipe_instructions); ?></textarea>
            </div>
        </div>

        <!-- Video Schema -->
        <div id="schema-video" class="nerdy-seo-schema-conditional <?php echo $schema_type === 'Video' ? 'active' : ''; ?>">
            <h4><?php esc_html_e('Video Details', 'nerdy-seo'); ?></h4>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_video_name"><?php esc_html_e('Video Name', 'nerdy-seo'); ?></label>
                <input type="text" id="nerdy_seo_video_name" name="nerdy_seo_video_name" value="<?php echo esc_attr($video_name); ?>" placeholder="<?php echo esc_attr($post->post_title); ?>" />
                <p class="nerdy-seo-schema-hint"><?php esc_html_e('Leave blank to use post title', 'nerdy-seo'); ?></p>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_video_description"><?php esc_html_e('Video Description', 'nerdy-seo'); ?></label>
                <textarea id="nerdy_seo_video_description" name="nerdy_seo_video_description" rows="3" placeholder="<?php esc_html_e('Brief description of this video...', 'nerdy-seo'); ?>"><?php echo esc_textarea($video_description); ?></textarea>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_video_url"><?php esc_html_e('Video URL', 'nerdy-seo'); ?></label>
                <input type="url" id="nerdy_seo_video_url" name="nerdy_seo_video_url" value="<?php echo esc_attr($video_url); ?>" placeholder="https://www.youtube.com/watch?v=..." />
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_video_thumbnail"><?php esc_html_e('Thumbnail URL', 'nerdy-seo'); ?></label>
                <input type="url" id="nerdy_seo_video_thumbnail" name="nerdy_seo_video_thumbnail" value="<?php echo esc_attr($video_thumbnail); ?>" placeholder="https://example.com/thumbnail.jpg" />
                <p class="nerdy-seo-schema-hint"><?php esc_html_e('Leave blank to use featured image', 'nerdy-seo'); ?></p>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_video_duration"><?php esc_html_e('Duration (ISO 8601 format)', 'nerdy-seo'); ?></label>
                <input type="text" id="nerdy_seo_video_duration" name="nerdy_seo_video_duration" value="<?php echo esc_attr($video_duration); ?>" placeholder="PT10M30S" />
                <p class="nerdy-seo-schema-hint"><?php esc_html_e('Format: PT#M#S (e.g., PT10M30S = 10 minutes 30 seconds)', 'nerdy-seo'); ?></p>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_video_upload_date"><?php esc_html_e('Upload Date', 'nerdy-seo'); ?></label>
                <input type="date" id="nerdy_seo_video_upload_date" name="nerdy_seo_video_upload_date" value="<?php echo esc_attr($video_upload_date); ?>" />
                <p class="nerdy-seo-schema-hint"><?php esc_html_e('Leave blank to use post publish date', 'nerdy-seo'); ?></p>
            </div>
        </div>

        <!-- Event Schema -->
        <div id="schema-event" class="nerdy-seo-schema-conditional <?php echo $schema_type === 'Event' ? 'active' : ''; ?>">
            <h4><?php esc_html_e('Event Details', 'nerdy-seo'); ?></h4>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_event_name"><?php esc_html_e('Event Name', 'nerdy-seo'); ?></label>
                <input type="text" id="nerdy_seo_event_name" name="nerdy_seo_event_name" value="<?php echo esc_attr($event_name); ?>" placeholder="<?php echo esc_attr($post->post_title); ?>" />
                <p class="nerdy-seo-schema-hint"><?php esc_html_e('Leave blank to use post title', 'nerdy-seo'); ?></p>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_event_description"><?php esc_html_e('Event Description', 'nerdy-seo'); ?></label>
                <textarea id="nerdy_seo_event_description" name="nerdy_seo_event_description" rows="3" placeholder="<?php esc_html_e('Brief description of this event...', 'nerdy-seo'); ?>"><?php echo esc_textarea($event_description); ?></textarea>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_event_start_date"><?php esc_html_e('Start Date & Time', 'nerdy-seo'); ?></label>
                <input type="datetime-local" id="nerdy_seo_event_start_date" name="nerdy_seo_event_start_date" value="<?php echo esc_attr($event_start_date); ?>" />
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_event_end_date"><?php esc_html_e('End Date & Time', 'nerdy-seo'); ?></label>
                <input type="datetime-local" id="nerdy_seo_event_end_date" name="nerdy_seo_event_end_date" value="<?php echo esc_attr($event_end_date); ?>" />
            </div>

            <div class="nerdy-seo-schema-field">
                <label>
                    <input type="checkbox" name="nerdy_seo_event_online" value="1" <?php checked($event_online, '1'); ?> />
                    <?php esc_html_e('Online Event', 'nerdy-seo'); ?>
                </label>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_event_location"><?php esc_html_e('Location', 'nerdy-seo'); ?></label>
                <input type="text" id="nerdy_seo_event_location" name="nerdy_seo_event_location" value="<?php echo esc_attr($event_location); ?>" placeholder="123 Main St, City, State 12345" />
                <p class="nerdy-seo-schema-hint"><?php esc_html_e('For online events, enter the website URL', 'nerdy-seo'); ?></p>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_event_url"><?php esc_html_e('Event URL', 'nerdy-seo'); ?></label>
                <input type="url" id="nerdy_seo_event_url" name="nerdy_seo_event_url" value="<?php echo esc_attr($event_url); ?>" placeholder="https://example.com/event" />
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_event_price"><?php esc_html_e('Price', 'nerdy-seo'); ?></label>
                <input type="number" id="nerdy_seo_event_price" name="nerdy_seo_event_price" value="<?php echo esc_attr($event_price); ?>" min="0" step="0.01" placeholder="0" />
                <p class="nerdy-seo-schema-hint"><?php esc_html_e('Enter 0 for free events', 'nerdy-seo'); ?></p>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_event_currency"><?php esc_html_e('Currency', 'nerdy-seo'); ?></label>
                <input type="text" id="nerdy_seo_event_currency" name="nerdy_seo_event_currency" value="<?php echo esc_attr($event_currency ?: 'USD'); ?>" placeholder="USD" maxlength="3" />
            </div>
        </div>

        <!-- Course Schema -->
        <div id="schema-course" class="nerdy-seo-schema-conditional <?php echo $schema_type === 'Course' ? 'active' : ''; ?>">
            <h4><?php esc_html_e('Course Details', 'nerdy-seo'); ?></h4>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_course_name"><?php esc_html_e('Course Name', 'nerdy-seo'); ?></label>
                <input type="text" id="nerdy_seo_course_name" name="nerdy_seo_course_name" value="<?php echo esc_attr($course_name); ?>" placeholder="<?php echo esc_attr($post->post_title); ?>" />
                <p class="nerdy-seo-schema-hint"><?php esc_html_e('Leave blank to use post title', 'nerdy-seo'); ?></p>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_course_description"><?php esc_html_e('Course Description', 'nerdy-seo'); ?></label>
                <textarea id="nerdy_seo_course_description" name="nerdy_seo_course_description" rows="3" placeholder="<?php esc_html_e('Brief description of this course...', 'nerdy-seo'); ?>"><?php echo esc_textarea($course_description); ?></textarea>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_course_provider"><?php esc_html_e('Provider / Institution', 'nerdy-seo'); ?></label>
                <input type="text" id="nerdy_seo_course_provider" name="nerdy_seo_course_provider" value="<?php echo esc_attr($course_provider); ?>" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>" />
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_course_price"><?php esc_html_e('Price', 'nerdy-seo'); ?></label>
                <input type="number" id="nerdy_seo_course_price" name="nerdy_seo_course_price" value="<?php echo esc_attr($course_price); ?>" min="0" step="0.01" placeholder="0" />
                <p class="nerdy-seo-schema-hint"><?php esc_html_e('Enter 0 for free courses', 'nerdy-seo'); ?></p>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_course_currency"><?php esc_html_e('Currency', 'nerdy-seo'); ?></label>
                <input type="text" id="nerdy_seo_course_currency" name="nerdy_seo_course_currency" value="<?php echo esc_attr($course_currency ?: 'USD'); ?>" placeholder="USD" maxlength="3" />
            </div>
        </div>

        <!-- HowTo Schema -->
        <div id="schema-howto" class="nerdy-seo-schema-conditional <?php echo $schema_type === 'HowTo' ? 'active' : ''; ?>">
            <h4><?php esc_html_e('HowTo Guide Details', 'nerdy-seo'); ?></h4>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_howto_name"><?php esc_html_e('Guide Name', 'nerdy-seo'); ?></label>
                <input type="text" id="nerdy_seo_howto_name" name="nerdy_seo_howto_name" value="<?php echo esc_attr($howto_name); ?>" placeholder="<?php echo esc_attr($post->post_title); ?>" />
                <p class="nerdy-seo-schema-hint"><?php esc_html_e('Leave blank to use post title', 'nerdy-seo'); ?></p>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_howto_description"><?php esc_html_e('Description', 'nerdy-seo'); ?></label>
                <textarea id="nerdy_seo_howto_description" name="nerdy_seo_howto_description" rows="3" placeholder="<?php esc_html_e('Brief description of what this guide teaches...', 'nerdy-seo'); ?>"><?php echo esc_textarea($howto_description); ?></textarea>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_howto_total_time"><?php esc_html_e('Total Time (minutes)', 'nerdy-seo'); ?></label>
                <input type="number" id="nerdy_seo_howto_total_time" name="nerdy_seo_howto_total_time" value="<?php echo esc_attr($howto_total_time); ?>" min="0" placeholder="30" />
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_howto_steps"><?php esc_html_e('Steps (one per line)', 'nerdy-seo'); ?></label>
                <textarea id="nerdy_seo_howto_steps" name="nerdy_seo_howto_steps" rows="6" placeholder="<?php esc_html_e('Step 1: Do this&#10;Step 2: Then do that&#10;Step 3: Finally do this', 'nerdy-seo'); ?>"><?php echo esc_textarea($howto_steps); ?></textarea>
            </div>
        </div>

        <!-- JobPosting Schema -->
        <div id="schema-jobposting" class="nerdy-seo-schema-conditional <?php echo $schema_type === 'JobPosting' ? 'active' : ''; ?>">
            <h4><?php esc_html_e('Job Posting Details', 'nerdy-seo'); ?></h4>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_job_title"><?php esc_html_e('Job Title', 'nerdy-seo'); ?></label>
                <input type="text" id="nerdy_seo_job_title" name="nerdy_seo_job_title" value="<?php echo esc_attr($job_title); ?>" placeholder="<?php echo esc_attr($post->post_title); ?>" />
                <p class="nerdy-seo-schema-hint"><?php esc_html_e('Leave blank to use post title', 'nerdy-seo'); ?></p>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_job_description"><?php esc_html_e('Job Description', 'nerdy-seo'); ?></label>
                <textarea id="nerdy_seo_job_description" name="nerdy_seo_job_description" rows="4" placeholder="<?php esc_html_e('Full job description...', 'nerdy-seo'); ?>"><?php echo esc_textarea($job_description); ?></textarea>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_job_company"><?php esc_html_e('Company Name', 'nerdy-seo'); ?></label>
                <input type="text" id="nerdy_seo_job_company" name="nerdy_seo_job_company" value="<?php echo esc_attr($job_company); ?>" placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>" />
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_job_location"><?php esc_html_e('Job Location', 'nerdy-seo'); ?></label>
                <input type="text" id="nerdy_seo_job_location" name="nerdy_seo_job_location" value="<?php echo esc_attr($job_location); ?>" placeholder="New York, NY" />
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_job_employment_type"><?php esc_html_e('Employment Type', 'nerdy-seo'); ?></label>
                <select id="nerdy_seo_job_employment_type" name="nerdy_seo_job_employment_type">
                    <option value=""><?php esc_html_e('Select Type', 'nerdy-seo'); ?></option>
                    <option value="FULL_TIME" <?php selected($job_employment_type, 'FULL_TIME'); ?>><?php esc_html_e('Full Time', 'nerdy-seo'); ?></option>
                    <option value="PART_TIME" <?php selected($job_employment_type, 'PART_TIME'); ?>><?php esc_html_e('Part Time', 'nerdy-seo'); ?></option>
                    <option value="CONTRACT" <?php selected($job_employment_type, 'CONTRACT'); ?>><?php esc_html_e('Contract', 'nerdy-seo'); ?></option>
                    <option value="TEMPORARY" <?php selected($job_employment_type, 'TEMPORARY'); ?>><?php esc_html_e('Temporary', 'nerdy-seo'); ?></option>
                    <option value="INTERN" <?php selected($job_employment_type, 'INTERN'); ?>><?php esc_html_e('Intern', 'nerdy-seo'); ?></option>
                </select>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_job_salary"><?php esc_html_e('Salary (annual)', 'nerdy-seo'); ?></label>
                <input type="number" id="nerdy_seo_job_salary" name="nerdy_seo_job_salary" value="<?php echo esc_attr($job_salary); ?>" min="0" step="1000" placeholder="50000" />
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_job_salary_currency"><?php esc_html_e('Currency', 'nerdy-seo'); ?></label>
                <input type="text" id="nerdy_seo_job_salary_currency" name="nerdy_seo_job_salary_currency" value="<?php echo esc_attr($job_salary_currency ?: 'USD'); ?>" placeholder="USD" maxlength="3" />
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_job_date_posted"><?php esc_html_e('Date Posted', 'nerdy-seo'); ?></label>
                <input type="date" id="nerdy_seo_job_date_posted" name="nerdy_seo_job_date_posted" value="<?php echo esc_attr($job_date_posted); ?>" />
                <p class="nerdy-seo-schema-hint"><?php esc_html_e('Leave blank to use post publish date', 'nerdy-seo'); ?></p>
            </div>

            <div class="nerdy-seo-schema-field">
                <label for="nerdy_seo_job_valid_through"><?php esc_html_e('Valid Through Date', 'nerdy-seo'); ?></label>
                <input type="date" id="nerdy_seo_job_valid_through" name="nerdy_seo_job_valid_through" value="<?php echo esc_attr($job_valid_through); ?>" />
                <p class="nerdy-seo-schema-hint"><?php esc_html_e('When this job posting expires', 'nerdy-seo'); ?></p>
            </div>
        </div>

        <script type="text/template" id="nerdy-seo-faq-template">
            <div class="nerdy-seo-faq-item" data-index="{{INDEX}}">
                <input
                    type="text"
                    name="nerdy_seo_schema_faqs[{{INDEX}}][question]"
                    placeholder="<?php esc_html_e('Question', 'nerdy-seo'); ?>"
                />
                <textarea
                    name="nerdy_seo_schema_faqs[{{INDEX}}][answer]"
                    placeholder="<?php esc_html_e('Answer', 'nerdy-seo'); ?>"
                ></textarea>
                <a href="#" class="nerdy-seo-faq-remove"><?php esc_html_e('Remove', 'nerdy-seo'); ?></a>
            </div>
        </script>

        <script>
        (function($) {
            $(document).ready(function() {
                // Schema type change
                $('#nerdy_seo_schema_type').on('change', function() {
                    var type = $(this).val();

                    $('.nerdy-seo-schema-conditional').removeClass('active');

                    if (type === 'FAQ') {
                        $('#schema-faq').addClass('active');
                    } else if (type === 'Review') {
                        $('#schema-review').addClass('active');
                    } else if (type === 'Recipe') {
                        $('#schema-recipe').addClass('active');
                    } else if (type === 'Video') {
                        $('#schema-video').addClass('active');
                    } else if (type === 'Event') {
                        $('#schema-event').addClass('active');
                    } else if (type === 'Course') {
                        $('#schema-course').addClass('active');
                    } else if (type === 'HowTo') {
                        $('#schema-howto').addClass('active');
                    } else if (type === 'JobPosting') {
                        $('#schema-jobposting').addClass('active');
                    }
                });

                // Add FAQ item
                $('#nerdy-seo-add-faq').on('click', function(e) {
                    e.preventDefault();
                    var template = $('#nerdy-seo-faq-template').html();
                    var index = $('#nerdy-seo-faq-items .nerdy-seo-faq-item').length;
                    var html = template.replace(/{{INDEX}}/g, index);
                    $('#nerdy-seo-faq-items').append(html);
                });

                // Remove FAQ item
                $(document).on('click', '.nerdy-seo-faq-remove', function(e) {
                    e.preventDefault();
                    $(this).closest('.nerdy-seo-faq-item').remove();
                });
            });
        })(jQuery);
        </script>
        <?php
    }

    /**
     * Save schema meta box
     */
    public function save_schema_meta_box($post_id, $post) {
        if (!isset($_POST['nerdy_seo_schema_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['nerdy_seo_schema_meta_box_nonce'], 'nerdy_seo_schema_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save schema type
        if (isset($_POST['nerdy_seo_schema_type'])) {
            update_post_meta($post_id, '_nerdy_seo_schema_type', sanitize_text_field($_POST['nerdy_seo_schema_type']));
        }

        // Save FAQ schema
        if (isset($_POST['nerdy_seo_schema_faqs']) && is_array($_POST['nerdy_seo_schema_faqs'])) {
            $faqs = array();
            foreach ($_POST['nerdy_seo_schema_faqs'] as $faq) {
                if (!empty($faq['question']) && !empty($faq['answer'])) {
                    $faqs[] = array(
                        'question' => sanitize_text_field($faq['question']),
                        'answer' => sanitize_textarea_field($faq['answer']),
                    );
                }
            }
            update_post_meta($post_id, '_nerdy_seo_schema_faqs', $faqs);
        } else {
            delete_post_meta($post_id, '_nerdy_seo_schema_faqs');
        }

        // Save review schema
        if (isset($_POST['nerdy_seo_schema_review_item'])) {
            update_post_meta($post_id, '_nerdy_seo_schema_review_item', sanitize_text_field($_POST['nerdy_seo_schema_review_item']));
        }

        if (isset($_POST['nerdy_seo_schema_review_rating'])) {
            $rating = floatval($_POST['nerdy_seo_schema_review_rating']);
            $rating = max(1, min(5, $rating)); // Clamp between 1 and 5
            update_post_meta($post_id, '_nerdy_seo_schema_review_rating', $rating);
        }

        if (isset($_POST['nerdy_seo_schema_review_author'])) {
            update_post_meta($post_id, '_nerdy_seo_schema_review_author', sanitize_text_field($_POST['nerdy_seo_schema_review_author']));
        }

        if (isset($_POST['nerdy_seo_schema_review_text'])) {
            update_post_meta($post_id, '_nerdy_seo_schema_review_text', sanitize_textarea_field($_POST['nerdy_seo_schema_review_text']));
        }

        // Save Recipe schema fields
        $recipe_fields = array(
            'nerdy_seo_recipe_name' => '_nerdy_seo_recipe_name',
            'nerdy_seo_recipe_description' => '_nerdy_seo_recipe_description',
            'nerdy_seo_recipe_prep_time' => '_nerdy_seo_recipe_prep_time',
            'nerdy_seo_recipe_cook_time' => '_nerdy_seo_recipe_cook_time',
            'nerdy_seo_recipe_yield' => '_nerdy_seo_recipe_yield',
            'nerdy_seo_recipe_calories' => '_nerdy_seo_recipe_calories',
            'nerdy_seo_recipe_ingredients' => '_nerdy_seo_recipe_ingredients',
            'nerdy_seo_recipe_instructions' => '_nerdy_seo_recipe_instructions',
        );
        foreach ($recipe_fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $meta_key, sanitize_textarea_field($_POST[$field]));
            }
        }

        // Save Video schema fields
        $video_fields = array(
            'nerdy_seo_video_name' => '_nerdy_seo_video_name',
            'nerdy_seo_video_description' => '_nerdy_seo_video_description',
            'nerdy_seo_video_url' => '_nerdy_seo_video_url',
            'nerdy_seo_video_thumbnail' => '_nerdy_seo_video_thumbnail',
            'nerdy_seo_video_duration' => '_nerdy_seo_video_duration',
            'nerdy_seo_video_upload_date' => '_nerdy_seo_video_upload_date',
        );
        foreach ($video_fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                if (in_array($field, array('nerdy_seo_video_url', 'nerdy_seo_video_thumbnail'))) {
                    update_post_meta($post_id, $meta_key, esc_url_raw($_POST[$field]));
                } else {
                    update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
                }
            }
        }

        // Save Event schema fields
        if (isset($_POST['nerdy_seo_event_name'])) {
            update_post_meta($post_id, '_nerdy_seo_event_name', sanitize_text_field($_POST['nerdy_seo_event_name']));
        }
        if (isset($_POST['nerdy_seo_event_description'])) {
            update_post_meta($post_id, '_nerdy_seo_event_description', sanitize_textarea_field($_POST['nerdy_seo_event_description']));
        }
        if (isset($_POST['nerdy_seo_event_start_date'])) {
            update_post_meta($post_id, '_nerdy_seo_event_start_date', sanitize_text_field($_POST['nerdy_seo_event_start_date']));
        }
        if (isset($_POST['nerdy_seo_event_end_date'])) {
            update_post_meta($post_id, '_nerdy_seo_event_end_date', sanitize_text_field($_POST['nerdy_seo_event_end_date']));
        }
        if (isset($_POST['nerdy_seo_event_location'])) {
            update_post_meta($post_id, '_nerdy_seo_event_location', sanitize_text_field($_POST['nerdy_seo_event_location']));
        }
        update_post_meta($post_id, '_nerdy_seo_event_online', isset($_POST['nerdy_seo_event_online']) ? '1' : '0');
        if (isset($_POST['nerdy_seo_event_url'])) {
            update_post_meta($post_id, '_nerdy_seo_event_url', esc_url_raw($_POST['nerdy_seo_event_url']));
        }
        if (isset($_POST['nerdy_seo_event_price'])) {
            update_post_meta($post_id, '_nerdy_seo_event_price', floatval($_POST['nerdy_seo_event_price']));
        }
        if (isset($_POST['nerdy_seo_event_currency'])) {
            update_post_meta($post_id, '_nerdy_seo_event_currency', sanitize_text_field($_POST['nerdy_seo_event_currency']));
        }

        // Save Course schema fields
        $course_fields = array(
            'nerdy_seo_course_name' => '_nerdy_seo_course_name',
            'nerdy_seo_course_description' => '_nerdy_seo_course_description',
            'nerdy_seo_course_provider' => '_nerdy_seo_course_provider',
            'nerdy_seo_course_price' => '_nerdy_seo_course_price',
            'nerdy_seo_course_currency' => '_nerdy_seo_course_currency',
        );
        foreach ($course_fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                if ($field === 'nerdy_seo_course_price') {
                    update_post_meta($post_id, $meta_key, floatval($_POST[$field]));
                } else if ($field === 'nerdy_seo_course_description') {
                    update_post_meta($post_id, $meta_key, sanitize_textarea_field($_POST[$field]));
                } else {
                    update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
                }
            }
        }

        // Save HowTo schema fields
        if (isset($_POST['nerdy_seo_howto_name'])) {
            update_post_meta($post_id, '_nerdy_seo_howto_name', sanitize_text_field($_POST['nerdy_seo_howto_name']));
        }
        if (isset($_POST['nerdy_seo_howto_description'])) {
            update_post_meta($post_id, '_nerdy_seo_howto_description', sanitize_textarea_field($_POST['nerdy_seo_howto_description']));
        }
        if (isset($_POST['nerdy_seo_howto_total_time'])) {
            update_post_meta($post_id, '_nerdy_seo_howto_total_time', intval($_POST['nerdy_seo_howto_total_time']));
        }
        if (isset($_POST['nerdy_seo_howto_steps'])) {
            update_post_meta($post_id, '_nerdy_seo_howto_steps', sanitize_textarea_field($_POST['nerdy_seo_howto_steps']));
        }

        // Save JobPosting schema fields
        $job_fields = array(
            'nerdy_seo_job_title' => '_nerdy_seo_job_title',
            'nerdy_seo_job_description' => '_nerdy_seo_job_description',
            'nerdy_seo_job_company' => '_nerdy_seo_job_company',
            'nerdy_seo_job_location' => '_nerdy_seo_job_location',
            'nerdy_seo_job_salary' => '_nerdy_seo_job_salary',
            'nerdy_seo_job_salary_currency' => '_nerdy_seo_job_salary_currency',
            'nerdy_seo_job_employment_type' => '_nerdy_seo_job_employment_type',
            'nerdy_seo_job_date_posted' => '_nerdy_seo_job_date_posted',
            'nerdy_seo_job_valid_through' => '_nerdy_seo_job_valid_through',
        );
        foreach ($job_fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                if ($field === 'nerdy_seo_job_salary') {
                    update_post_meta($post_id, $meta_key, floatval($_POST[$field]));
                } else if ($field === 'nerdy_seo_job_description') {
                    update_post_meta($post_id, $meta_key, sanitize_textarea_field($_POST[$field]));
                } else {
                    update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
                }
            }
        }
    }

    /**
     * Output schema markup
     */
    public function output_schema() {
        if (!get_option('nerdy_seo_schema_enabled', true)) {
            return;
        }

        $schema = $this->get_schema();

        if (empty($schema)) {
            return;
        }

        echo "<!-- Schema.org Markup -->\n";
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n" . '</script>' . "\n";
        echo "<!-- / Schema.org Markup -->\n\n";
    }

    /**
     * Get schema data
     */
    private function get_schema() {
        $schema_array = array();

        // Get page-specific schema
        $page_schema = null;

        if (is_front_page()) {
            $page_schema = $this->get_website_schema();
        } elseif (is_singular()) {
            global $post;
            $schema_type = get_post_meta($post->ID, '_nerdy_seo_schema_type', true);

            switch ($schema_type) {
                case 'FAQ':
                    $page_schema = $this->get_faq_schema($post);
                    break;
                case 'Review':
                    $page_schema = $this->get_review_schema($post);
                    break;
                case 'Article':
                    $page_schema = $this->get_article_schema($post);
                    break;
                case 'WebPage':
                    $page_schema = $this->get_webpage_schema($post);
                    break;
                case 'Recipe':
                    $page_schema = $this->get_recipe_schema($post);
                    break;
                case 'Video':
                    $page_schema = $this->get_video_schema($post);
                    break;
                case 'Event':
                    $page_schema = $this->get_event_schema($post);
                    break;
                case 'Course':
                    $page_schema = $this->get_course_schema($post);
                    break;
                case 'HowTo':
                    $page_schema = $this->get_howto_schema($post);
                    break;
                case 'JobPosting':
                    $page_schema = $this->get_jobposting_schema($post);
                    break;
                default:
                    // Default schemas based on post type
                    if ($post->post_type === 'post') {
                        $page_schema = $this->get_article_schema($post);
                    } elseif ($post->post_type === 'page') {
                        $page_schema = $this->get_webpage_schema($post);
                    }
                    break;
            }
        }

        // Add page schema to array
        if (!empty($page_schema)) {
            $schema_array[] = $page_schema;
        }

        // Get global schema from settings
        $global_schema = get_option('nerdy_seo_global_schema', '');
        if (!empty($global_schema)) {
            $decoded_schema = json_decode($global_schema, true);
            if (json_last_error() === JSON_ERROR_NONE && !empty($decoded_schema)) {
                // Add @context if not present
                if (!isset($decoded_schema['@context'])) {
                    $decoded_schema['@context'] = 'https://schema.org';
                }
                $schema_array[] = $decoded_schema;
            }
        }

        // If we have multiple schemas, wrap in @graph
        if (count($schema_array) > 1) {
            return array(
                '@context' => 'https://schema.org',
                '@graph' => $schema_array,
            );
        } elseif (count($schema_array) === 1) {
            return $schema_array[0];
        }

        return array();
    }

    /**
     * Get website schema
     */
    private function get_website_schema() {
        return array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url' => home_url('/'),
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => home_url('/?s={search_term_string}'),
                'query-input' => 'required name=search_term_string',
            ),
        );
    }

    /**
     * Get FAQ schema
     */
    private function get_faq_schema($post) {
        $faqs = get_post_meta($post->ID, '_nerdy_seo_schema_faqs', true);

        if (empty($faqs) || !is_array($faqs)) {
            return array();
        }

        $main_entity = array();
        foreach ($faqs as $faq) {
            $main_entity[] = array(
                '@type' => 'Question',
                'name' => $faq['question'],
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text' => $faq['answer'],
                ),
            );
        }

        return array(
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $main_entity,
        );
    }

    /**
     * Get review schema
     */
    private function get_review_schema($post) {
        $item_name = get_post_meta($post->ID, '_nerdy_seo_schema_review_item', true);
        $rating = get_post_meta($post->ID, '_nerdy_seo_schema_review_rating', true);
        $author = get_post_meta($post->ID, '_nerdy_seo_schema_review_author', true);
        $review_text = get_post_meta($post->ID, '_nerdy_seo_schema_review_text', true);

        if (empty($item_name) || empty($rating)) {
            return array();
        }

        if (empty($author)) {
            $author = get_the_author_meta('display_name', $post->post_author);
        }

        if (empty($review_text)) {
            $review_text = wp_trim_words(strip_shortcodes($post->post_content), 50);
        }

        return array(
            '@context' => 'https://schema.org',
            '@type' => 'Review',
            'itemReviewed' => array(
                '@type' => 'Thing',
                'name' => $item_name,
            ),
            'reviewRating' => array(
                '@type' => 'Rating',
                'ratingValue' => $rating,
                'bestRating' => '5',
                'worstRating' => '1',
            ),
            'author' => array(
                '@type' => 'Person',
                'name' => $author,
            ),
            'reviewBody' => $review_text,
            'datePublished' => get_the_date('c', $post->ID),
        );
    }

    /**
     * Get article schema
     */
    private function get_article_schema($post) {
        $image_url = '';

        if (has_post_thumbnail($post->ID)) {
            $image_url = get_the_post_thumbnail_url($post->ID, 'full');
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title($post->ID),
            'description' => get_post_meta($post->ID, '_nerdy_seo_description', true) ?: wp_trim_words(strip_shortcodes($post->post_content), 20),
            'datePublished' => get_the_date('c', $post->ID),
            'dateModified' => get_the_modified_date('c', $post->ID),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', $post->post_author),
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url(),
                ),
            ),
        );

        if ($image_url) {
            $schema['image'] = $image_url;
        }

        return $schema;
    }

    /**
     * Get webpage schema
     */
    private function get_webpage_schema($post) {
        $image_url = '';

        if (has_post_thumbnail($post->ID)) {
            $image_url = get_the_post_thumbnail_url($post->ID, 'full');
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebPage',
            'name' => get_the_title($post->ID),
            'description' => get_post_meta($post->ID, '_nerdy_seo_description', true) ?: wp_trim_words(strip_shortcodes($post->post_content), 20),
            'url' => get_permalink($post->ID),
            'datePublished' => get_the_date('c', $post->ID),
            'dateModified' => get_the_modified_date('c', $post->ID),
        );

        if ($image_url) {
            $schema['image'] = $image_url;
        }

        // Add breadcrumb if available
        $schema['breadcrumb'] = array(
            '@type' => 'BreadcrumbList',
            'itemListElement' => array(
                array(
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => home_url('/'),
                ),
                array(
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => get_the_title($post->ID),
                    'item' => get_permalink($post->ID),
                ),
            ),
        );

        return $schema;
    }

    /**
     * Get recipe schema
     */
    private function get_recipe_schema($post) {
        $name = get_post_meta($post->ID, '_nerdy_seo_recipe_name', true) ?: get_the_title($post->ID);
        $description = get_post_meta($post->ID, '_nerdy_seo_recipe_description', true);
        $prep_time = get_post_meta($post->ID, '_nerdy_seo_recipe_prep_time', true);
        $cook_time = get_post_meta($post->ID, '_nerdy_seo_recipe_cook_time', true);
        $yield = get_post_meta($post->ID, '_nerdy_seo_recipe_yield', true);
        $calories = get_post_meta($post->ID, '_nerdy_seo_recipe_calories', true);
        $ingredients = get_post_meta($post->ID, '_nerdy_seo_recipe_ingredients', true);
        $instructions = get_post_meta($post->ID, '_nerdy_seo_recipe_instructions', true);

        if (empty($name)) {
            return array();
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Recipe',
            'name' => $name,
            'datePublished' => get_the_date('c', $post->ID),
        );

        if ($description) {
            $schema['description'] = $description;
        }

        if (has_post_thumbnail($post->ID)) {
            $schema['image'] = get_the_post_thumbnail_url($post->ID, 'full');
        }

        $schema['author'] = array(
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name', $post->post_author),
        );

        if ($prep_time) {
            $schema['prepTime'] = 'PT' . intval($prep_time) . 'M';
        }

        if ($cook_time) {
            $schema['cookTime'] = 'PT' . intval($cook_time) . 'M';
        }

        if ($prep_time && $cook_time) {
            $schema['totalTime'] = 'PT' . (intval($prep_time) + intval($cook_time)) . 'M';
        }

        if ($yield) {
            $schema['recipeYield'] = $yield;
        }

        if ($calories) {
            $schema['nutrition'] = array(
                '@type' => 'NutritionInformation',
                'calories' => $calories . ' calories',
            );
        }

        if ($ingredients) {
            $schema['recipeIngredient'] = array_filter(array_map('trim', explode("\n", $ingredients)));
        }

        if ($instructions) {
            $steps = array_filter(array_map('trim', explode("\n", $instructions)));
            $schema['recipeInstructions'] = array();
            foreach ($steps as $index => $step) {
                $schema['recipeInstructions'][] = array(
                    '@type' => 'HowToStep',
                    'position' => $index + 1,
                    'text' => $step,
                );
            }
        }

        return $schema;
    }

    /**
     * Get video schema
     */
    private function get_video_schema($post) {
        $name = get_post_meta($post->ID, '_nerdy_seo_video_name', true) ?: get_the_title($post->ID);
        $description = get_post_meta($post->ID, '_nerdy_seo_video_description', true);
        $url = get_post_meta($post->ID, '_nerdy_seo_video_url', true);
        $thumbnail = get_post_meta($post->ID, '_nerdy_seo_video_thumbnail', true);
        $duration = get_post_meta($post->ID, '_nerdy_seo_video_duration', true);
        $upload_date = get_post_meta($post->ID, '_nerdy_seo_video_upload_date', true);

        if (empty($name) || empty($url)) {
            return array();
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'VideoObject',
            'name' => $name,
            'contentUrl' => $url,
        );

        if ($description) {
            $schema['description'] = $description;
        } else {
            $schema['description'] = wp_trim_words(strip_shortcodes($post->post_content), 20);
        }

        if ($thumbnail) {
            $schema['thumbnailUrl'] = $thumbnail;
        } else if (has_post_thumbnail($post->ID)) {
            $schema['thumbnailUrl'] = get_the_post_thumbnail_url($post->ID, 'full');
        }

        if ($duration) {
            $schema['duration'] = $duration;
        }

        if ($upload_date) {
            $schema['uploadDate'] = gmdate('c', strtotime($upload_date));
        } else {
            $schema['uploadDate'] = get_the_date('c', $post->ID);
        }

        return $schema;
    }

    /**
     * Get event schema
     */
    private function get_event_schema($post) {
        $name = get_post_meta($post->ID, '_nerdy_seo_event_name', true) ?: get_the_title($post->ID);
        $description = get_post_meta($post->ID, '_nerdy_seo_event_description', true);
        $start_date = get_post_meta($post->ID, '_nerdy_seo_event_start_date', true);
        $end_date = get_post_meta($post->ID, '_nerdy_seo_event_end_date', true);
        $location = get_post_meta($post->ID, '_nerdy_seo_event_location', true);
        $online = get_post_meta($post->ID, '_nerdy_seo_event_online', true);
        $url = get_post_meta($post->ID, '_nerdy_seo_event_url', true);
        $price = get_post_meta($post->ID, '_nerdy_seo_event_price', true);
        $currency = get_post_meta($post->ID, '_nerdy_seo_event_currency', true) ?: 'USD';

        if (empty($name) || empty($start_date)) {
            return array();
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Event',
            'name' => $name,
            'startDate' => gmdate('c', strtotime($start_date)),
        );

        if ($description) {
            $schema['description'] = $description;
        }

        if ($end_date) {
            $schema['endDate'] = gmdate('c', strtotime($end_date));
        }

        if (has_post_thumbnail($post->ID)) {
            $schema['image'] = get_the_post_thumbnail_url($post->ID, 'full');
        }

        if ($location) {
            if ($online === '1') {
                $schema['location'] = array(
                    '@type' => 'VirtualLocation',
                    'url' => $location,
                );
                $schema['eventAttendanceMode'] = 'https://schema.org/OnlineEventAttendanceMode';
            } else {
                $schema['location'] = array(
                    '@type' => 'Place',
                    'name' => $location,
                    'address' => $location,
                );
                $schema['eventAttendanceMode'] = 'https://schema.org/OfflineEventAttendanceMode';
            }
        }

        if ($url) {
            $schema['url'] = $url;
        }

        if ($price !== null && $price !== '') {
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => $currency,
                'availability' => 'https://schema.org/InStock',
                'url' => get_permalink($post->ID),
            );
        }

        $schema['organizer'] = array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url('/'),
        );

        return $schema;
    }

    /**
     * Get course schema
     */
    private function get_course_schema($post) {
        $name = get_post_meta($post->ID, '_nerdy_seo_course_name', true) ?: get_the_title($post->ID);
        $description = get_post_meta($post->ID, '_nerdy_seo_course_description', true);
        $provider = get_post_meta($post->ID, '_nerdy_seo_course_provider', true) ?: get_bloginfo('name');
        $price = get_post_meta($post->ID, '_nerdy_seo_course_price', true);
        $currency = get_post_meta($post->ID, '_nerdy_seo_course_currency', true) ?: 'USD';

        if (empty($name)) {
            return array();
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Course',
            'name' => $name,
            'provider' => array(
                '@type' => 'Organization',
                'name' => $provider,
            ),
        );

        if ($description) {
            $schema['description'] = $description;
        }

        if ($price !== null && $price !== '') {
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => $price,
                'priceCurrency' => $currency,
                'category' => 'Paid',
            );
        }

        return $schema;
    }

    /**
     * Get HowTo schema
     */
    private function get_howto_schema($post) {
        $name = get_post_meta($post->ID, '_nerdy_seo_howto_name', true) ?: get_the_title($post->ID);
        $description = get_post_meta($post->ID, '_nerdy_seo_howto_description', true);
        $total_time = get_post_meta($post->ID, '_nerdy_seo_howto_total_time', true);
        $steps = get_post_meta($post->ID, '_nerdy_seo_howto_steps', true);

        if (empty($name) || empty($steps)) {
            return array();
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'HowTo',
            'name' => $name,
        );

        if ($description) {
            $schema['description'] = $description;
        }

        if (has_post_thumbnail($post->ID)) {
            $schema['image'] = get_the_post_thumbnail_url($post->ID, 'full');
        }

        if ($total_time) {
            $schema['totalTime'] = 'PT' . intval($total_time) . 'M';
        }

        $step_lines = array_filter(array_map('trim', explode("\n", $steps)));
        $schema['step'] = array();
        foreach ($step_lines as $index => $step) {
            $schema['step'][] = array(
                '@type' => 'HowToStep',
                'position' => $index + 1,
                'text' => $step,
            );
        }

        return $schema;
    }

    /**
     * Get job posting schema
     */
    private function get_jobposting_schema($post) {
        $title = get_post_meta($post->ID, '_nerdy_seo_job_title', true) ?: get_the_title($post->ID);
        $description = get_post_meta($post->ID, '_nerdy_seo_job_description', true);
        $company = get_post_meta($post->ID, '_nerdy_seo_job_company', true) ?: get_bloginfo('name');
        $location = get_post_meta($post->ID, '_nerdy_seo_job_location', true);
        $salary = get_post_meta($post->ID, '_nerdy_seo_job_salary', true);
        $salary_currency = get_post_meta($post->ID, '_nerdy_seo_job_salary_currency', true) ?: 'USD';
        $employment_type = get_post_meta($post->ID, '_nerdy_seo_job_employment_type', true);
        $date_posted = get_post_meta($post->ID, '_nerdy_seo_job_date_posted', true);
        $valid_through = get_post_meta($post->ID, '_nerdy_seo_job_valid_through', true);

        if (empty($title) || empty($description)) {
            return array();
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $title,
            'description' => $description,
            'hiringOrganization' => array(
                '@type' => 'Organization',
                'name' => $company,
            ),
        );

        if ($date_posted) {
            $schema['datePosted'] = gmdate('c', strtotime($date_posted));
        } else {
            $schema['datePosted'] = get_the_date('c', $post->ID);
        }

        if ($valid_through) {
            $schema['validThrough'] = gmdate('c', strtotime($valid_through));
        }

        if ($location) {
            $schema['jobLocation'] = array(
                '@type' => 'Place',
                'address' => array(
                    '@type' => 'PostalAddress',
                    'addressLocality' => $location,
                ),
            );
        }

        if ($employment_type) {
            $schema['employmentType'] = $employment_type;
        }

        if ($salary) {
            $schema['baseSalary'] = array(
                '@type' => 'MonetaryAmount',
                'currency' => $salary_currency,
                'value' => array(
                    '@type' => 'QuantitativeValue',
                    'value' => $salary,
                    'unitText' => 'YEAR',
                ),
            );
        }

        return $schema;
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }

        wp_enqueue_script('jquery');
    }
}

<?php
/**
 * Local Business admin page
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Local Business class
 */
class Nerdy_SEO_Local_Business {

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
        add_action('admin_menu', array($this, 'add_admin_menu'), 66);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_head', array($this, 'output_local_business_schema'), 5);
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        // Only load on our page
        if ($hook !== 'nerdy-seo_page_nerdy-seo-local-business') {
            return;
        }

        // Enqueue WordPress media uploader
        wp_enqueue_media();
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'nerdy-seo',
            __('Local Business', 'nerdy-seo'),
            __('Local Business', 'nerdy-seo'),
            'manage_options',
            'nerdy-seo-local-business',
            array($this, 'render_page')
        );
    }

    /**
     * Render page
     */
    public function render_page() {
        // Save settings if form submitted
        if (isset($_POST['nerdy_seo_save_local_business'])) {
            check_admin_referer('nerdy_seo_local_business_nonce');

            // Basic business info
            $settings = array(
                'nerdy_seo_lb_enabled',
                'nerdy_seo_lb_name',
                'nerdy_seo_lb_type',
                'nerdy_seo_lb_description',
                'nerdy_seo_lb_url',
                'nerdy_seo_lb_phone',
                'nerdy_seo_lb_email',
                'nerdy_seo_lb_price_range',
                'nerdy_seo_lb_image',
                'nerdy_seo_lb_logo',
                // Address
                'nerdy_seo_lb_street_address',
                'nerdy_seo_lb_city',
                'nerdy_seo_lb_state',
                'nerdy_seo_lb_postal_code',
                'nerdy_seo_lb_country',
                // Coordinates
                'nerdy_seo_lb_latitude',
                'nerdy_seo_lb_longitude',
                // Social
                'nerdy_seo_lb_facebook',
                'nerdy_seo_lb_twitter',
                'nerdy_seo_lb_instagram',
                'nerdy_seo_lb_linkedin',
                'nerdy_seo_lb_youtube',
            );

            foreach ($settings as $setting) {
                if (isset($_POST[$setting])) {
                    update_option($setting, sanitize_text_field($_POST[$setting]));
                } else {
                    // Handle checkboxes
                    if (strpos($setting, '_enabled') !== false) {
                        update_option($setting, false);
                    }
                }
            }

            // Save hours (array)
            if (isset($_POST['nerdy_seo_lb_hours'])) {
                update_option('nerdy_seo_lb_hours', array_map('sanitize_text_field', $_POST['nerdy_seo_lb_hours']));
            }

            echo '<div class="notice notice-success is-dismissible"><p>' . __('Local Business settings saved successfully!', 'nerdy-seo') . '</p></div>';
        }

        // Get current values
        $enabled = get_option('nerdy_seo_lb_enabled', false);
        $name = get_option('nerdy_seo_lb_name', get_bloginfo('name'));
        $type = get_option('nerdy_seo_lb_type', 'LocalBusiness');
        $description = get_option('nerdy_seo_lb_description', get_bloginfo('description'));
        $url = get_option('nerdy_seo_lb_url', home_url());
        $phone = get_option('nerdy_seo_lb_phone', '');
        $email = get_option('nerdy_seo_lb_email', get_option('admin_email'));
        $price_range = get_option('nerdy_seo_lb_price_range', '$$');
        $image = get_option('nerdy_seo_lb_image', '');
        $logo = get_option('nerdy_seo_lb_logo', '');

        // Address
        $street_address = get_option('nerdy_seo_lb_street_address', '');
        $city = get_option('nerdy_seo_lb_city', '');
        $state = get_option('nerdy_seo_lb_state', '');
        $postal_code = get_option('nerdy_seo_lb_postal_code', '');
        $country = get_option('nerdy_seo_lb_country', 'US');

        // Coordinates
        $latitude = get_option('nerdy_seo_lb_latitude', '');
        $longitude = get_option('nerdy_seo_lb_longitude', '');

        // Social
        $facebook = get_option('nerdy_seo_lb_facebook', '');
        $twitter = get_option('nerdy_seo_lb_twitter', '');
        $instagram = get_option('nerdy_seo_lb_instagram', '');
        $linkedin = get_option('nerdy_seo_lb_linkedin', '');
        $youtube = get_option('nerdy_seo_lb_youtube', '');

        // Hours
        $hours = get_option('nerdy_seo_lb_hours', array(
            'monday_open' => '9:00',
            'monday_close' => '17:00',
            'tuesday_open' => '9:00',
            'tuesday_close' => '17:00',
            'wednesday_open' => '9:00',
            'wednesday_close' => '17:00',
            'thursday_open' => '9:00',
            'thursday_close' => '17:00',
            'friday_open' => '9:00',
            'friday_close' => '17:00',
            'saturday_open' => '',
            'saturday_close' => '',
            'sunday_open' => '',
            'sunday_close' => '',
        ));

        // Business types
        $business_types = array(
            'LocalBusiness' => 'Local Business (Generic)',
            'Store' => 'Store',
            'Restaurant' => 'Restaurant',
            'MedicalBusiness' => 'Medical Business',
            'HealthAndBeautyBusiness' => 'Health & Beauty Business',
            'HomeAndConstructionBusiness' => 'Home & Construction Business',
            'LegalService' => 'Legal Service',
            'FinancialService' => 'Financial Service',
            'RealEstateAgent' => 'Real Estate Agent',
            'ProfessionalService' => 'Professional Service',
            'AutomotiveBusiness' => 'Automotive Business',
            'LodgingBusiness' => 'Lodging Business',
            'EntertainmentBusiness' => 'Entertainment Business',
            'FoodEstablishment' => 'Food Establishment',
            'EducationalOrganization' => 'Educational Organization',
        );

        ?>
        <div class="wrap nerdy-seo-settings-wrap">
            <h1 class="nerdy-seo-page-title">
                <span class="dashicons dashicons-location"></span>
                <?php _e('Local Business Settings', 'nerdy-seo'); ?>
            </h1>

            <div class="nerdy-seo-settings-header">
                <p><?php _e('Set up your local business information to generate structured data that helps your business appear in local search results and Google Maps.', 'nerdy-seo'); ?></p>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('nerdy_seo_local_business_nonce'); ?>

                <div class="nerdy-seo-tabs-wrapper">
                    <div class="nerdy-seo-tabs-nav">
                        <button type="button" class="nerdy-seo-tab-btn active" data-tab="general">
                            <span class="dashicons dashicons-admin-home"></span>
                            <?php _e('Business Info', 'nerdy-seo'); ?>
                        </button>
                        <button type="button" class="nerdy-seo-tab-btn" data-tab="hours">
                            <span class="dashicons dashicons-clock"></span>
                            <?php _e('Hours', 'nerdy-seo'); ?>
                        </button>
                        <button type="button" class="nerdy-seo-tab-btn" data-tab="location">
                            <span class="dashicons dashicons-location-alt"></span>
                            <?php _e('Location', 'nerdy-seo'); ?>
                        </button>
                        <button type="button" class="nerdy-seo-tab-btn" data-tab="social">
                            <span class="dashicons dashicons-share"></span>
                            <?php _e('Social Profiles', 'nerdy-seo'); ?>
                        </button>
                    </div>

                    <!-- Business Info Tab -->
                    <div class="nerdy-seo-tab-content active" data-tab="general">
                        <div class="nerdy-seo-settings-card">
                            <h2><?php _e('Enable Local Business Schema', 'nerdy-seo'); ?></h2>
                            <p class="description"><?php _e('Add structured data about your business to help search engines display rich results.', 'nerdy-seo'); ?></p>

                            <table class="form-table">
                                <tr>
                                    <th scope="row"><?php _e('Enable Local Business', 'nerdy-seo'); ?></th>
                                    <td>
                                        <label class="nerdy-seo-toggle">
                                            <input type="checkbox" name="nerdy_seo_lb_enabled" value="1" <?php checked($enabled, true); ?> />
                                            <span class="nerdy-seo-toggle-slider"></span>
                                        </label>
                                        <p class="description"><?php _e('Enable to output LocalBusiness schema markup on your site.', 'nerdy-seo'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="nerdy-seo-settings-card">
                            <h2><?php _e('Basic Information', 'nerdy-seo'); ?></h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_name"><?php _e('Business Name', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <input
                                            type="text"
                                            id="nerdy_seo_lb_name"
                                            name="nerdy_seo_lb_name"
                                            value="<?php echo esc_attr($name); ?>"
                                            class="regular-text"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_type"><?php _e('Business Type', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <select id="nerdy_seo_lb_type" name="nerdy_seo_lb_type" class="regular-text">
                                            <?php foreach ($business_types as $value => $label): ?>
                                                <option value="<?php echo esc_attr($value); ?>" <?php selected($type, $value); ?>>
                                                    <?php echo esc_html($label); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <p class="description"><?php _e('Select the most specific business type that describes your business.', 'nerdy-seo'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_description"><?php _e('Description', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <textarea
                                            id="nerdy_seo_lb_description"
                                            name="nerdy_seo_lb_description"
                                            rows="3"
                                            class="large-text"
                                        ><?php echo esc_textarea($description); ?></textarea>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_url"><?php _e('Website URL', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <input
                                            type="url"
                                            id="nerdy_seo_lb_url"
                                            name="nerdy_seo_lb_url"
                                            value="<?php echo esc_url($url); ?>"
                                            class="regular-text"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_phone"><?php _e('Phone Number', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <input
                                            type="tel"
                                            id="nerdy_seo_lb_phone"
                                            name="nerdy_seo_lb_phone"
                                            value="<?php echo esc_attr($phone); ?>"
                                            class="regular-text"
                                            placeholder="+1-555-555-5555"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_email"><?php _e('Email', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <input
                                            type="email"
                                            id="nerdy_seo_lb_email"
                                            name="nerdy_seo_lb_email"
                                            value="<?php echo esc_attr($email); ?>"
                                            class="regular-text"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_price_range"><?php _e('Price Range', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <select id="nerdy_seo_lb_price_range" name="nerdy_seo_lb_price_range">
                                            <option value="$" <?php selected($price_range, '$'); ?>>$ (Inexpensive)</option>
                                            <option value="$$" <?php selected($price_range, '$$'); ?>>$$ (Moderate)</option>
                                            <option value="$$$" <?php selected($price_range, '$$$'); ?>>$$$ (Expensive)</option>
                                            <option value="$$$$" <?php selected($price_range, '$$$$'); ?>>$$$$ (Very Expensive)</option>
                                        </select>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="nerdy-seo-settings-card">
                            <h2><?php _e('Images', 'nerdy-seo'); ?></h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_logo"><?php _e('Logo', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <div class="nerdy-seo-image-upload">
                                            <input
                                                type="text"
                                                id="nerdy_seo_lb_logo"
                                                name="nerdy_seo_lb_logo"
                                                value="<?php echo esc_url($logo); ?>"
                                                class="large-text"
                                            />
                                            <button type="button" class="button nerdy-seo-upload-btn" data-target="nerdy_seo_lb_logo">
                                                <?php _e('Choose Image', 'nerdy-seo'); ?>
                                            </button>
                                        </div>
                                        <?php if ($logo): ?>
                                            <div class="nerdy-seo-image-preview">
                                                <img src="<?php echo esc_url($logo); ?>" alt="Logo" />
                                            </div>
                                        <?php endif; ?>
                                        <p class="description"><?php _e('Your business logo. Square format recommended (minimum 112x112px).', 'nerdy-seo'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_image"><?php _e('Featured Image', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <div class="nerdy-seo-image-upload">
                                            <input
                                                type="text"
                                                id="nerdy_seo_lb_image"
                                                name="nerdy_seo_lb_image"
                                                value="<?php echo esc_url($image); ?>"
                                                class="large-text"
                                            />
                                            <button type="button" class="button nerdy-seo-upload-btn" data-target="nerdy_seo_lb_image">
                                                <?php _e('Choose Image', 'nerdy-seo'); ?>
                                            </button>
                                        </div>
                                        <?php if ($image): ?>
                                            <div class="nerdy-seo-image-preview">
                                                <img src="<?php echo esc_url($image); ?>" alt="Featured" />
                                            </div>
                                        <?php endif; ?>
                                        <p class="description"><?php _e('A photo of your business, storefront, or location.', 'nerdy-seo'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Hours Tab -->
                    <div class="nerdy-seo-tab-content" data-tab="hours">
                        <div class="nerdy-seo-settings-card">
                            <h2><?php _e('Business Hours', 'nerdy-seo'); ?></h2>
                            <p class="description"><?php _e('Set your business hours. Leave blank for closed days.', 'nerdy-seo'); ?></p>

                            <table class="form-table">
                                <?php
                                $days = array(
                                    'monday' => __('Monday', 'nerdy-seo'),
                                    'tuesday' => __('Tuesday', 'nerdy-seo'),
                                    'wednesday' => __('Wednesday', 'nerdy-seo'),
                                    'thursday' => __('Thursday', 'nerdy-seo'),
                                    'friday' => __('Friday', 'nerdy-seo'),
                                    'saturday' => __('Saturday', 'nerdy-seo'),
                                    'sunday' => __('Sunday', 'nerdy-seo'),
                                );

                                foreach ($days as $day => $label):
                                    $open = isset($hours[$day . '_open']) ? $hours[$day . '_open'] : '';
                                    $close = isset($hours[$day . '_close']) ? $hours[$day . '_close'] : '';
                                ?>
                                <tr>
                                    <th scope="row">
                                        <label><?php echo esc_html($label); ?></label>
                                    </th>
                                    <td>
                                        <input
                                            type="time"
                                            name="nerdy_seo_lb_hours[<?php echo esc_attr($day); ?>_open]"
                                            value="<?php echo esc_attr($open); ?>"
                                            placeholder="9:00"
                                            style="width: 120px;"
                                        />
                                        <span style="margin: 0 10px;"><?php _e('to', 'nerdy-seo'); ?></span>
                                        <input
                                            type="time"
                                            name="nerdy_seo_lb_hours[<?php echo esc_attr($day); ?>_close]"
                                            value="<?php echo esc_attr($close); ?>"
                                            placeholder="17:00"
                                            style="width: 120px;"
                                        />
                                        <p class="description" style="margin: 5px 0 0 0;"><?php _e('Use 24-hour format (e.g., 09:00 for 9 AM, 17:00 for 5 PM). Leave blank if closed.', 'nerdy-seo'); ?></p>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        </div>
                    </div>

                    <!-- Location Tab -->
                    <div class="nerdy-seo-tab-content" data-tab="location">
                        <div class="nerdy-seo-settings-card">
                            <h2><?php _e('Business Address', 'nerdy-seo'); ?></h2>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_street_address"><?php _e('Street Address', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <input
                                            type="text"
                                            id="nerdy_seo_lb_street_address"
                                            name="nerdy_seo_lb_street_address"
                                            value="<?php echo esc_attr($street_address); ?>"
                                            class="regular-text"
                                            placeholder="123 Main Street"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_city"><?php _e('City', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <input
                                            type="text"
                                            id="nerdy_seo_lb_city"
                                            name="nerdy_seo_lb_city"
                                            value="<?php echo esc_attr($city); ?>"
                                            class="regular-text"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_state"><?php _e('State / Province', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <input
                                            type="text"
                                            id="nerdy_seo_lb_state"
                                            name="nerdy_seo_lb_state"
                                            value="<?php echo esc_attr($state); ?>"
                                            class="regular-text"
                                            placeholder="CA"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_postal_code"><?php _e('Postal Code', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <input
                                            type="text"
                                            id="nerdy_seo_lb_postal_code"
                                            name="nerdy_seo_lb_postal_code"
                                            value="<?php echo esc_attr($postal_code); ?>"
                                            class="regular-text"
                                            placeholder="90210"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_country"><?php _e('Country', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <input
                                            type="text"
                                            id="nerdy_seo_lb_country"
                                            name="nerdy_seo_lb_country"
                                            value="<?php echo esc_attr($country); ?>"
                                            class="regular-text"
                                            placeholder="US"
                                        />
                                        <p class="description"><?php _e('Two-letter country code (e.g., US, CA, GB).', 'nerdy-seo'); ?></p>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="nerdy-seo-settings-card">
                            <h2><?php _e('Geographic Coordinates', 'nerdy-seo'); ?></h2>
                            <p class="description"><?php _e('Optional: Add exact GPS coordinates for more accurate location data.', 'nerdy-seo'); ?></p>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_latitude"><?php _e('Latitude', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <input
                                            type="text"
                                            id="nerdy_seo_lb_latitude"
                                            name="nerdy_seo_lb_latitude"
                                            value="<?php echo esc_attr($latitude); ?>"
                                            class="regular-text"
                                            placeholder="34.0522"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_longitude"><?php _e('Longitude', 'nerdy-seo'); ?></label>
                                    </th>
                                    <td>
                                        <input
                                            type="text"
                                            id="nerdy_seo_lb_longitude"
                                            name="nerdy_seo_lb_longitude"
                                            value="<?php echo esc_attr($longitude); ?>"
                                            class="regular-text"
                                            placeholder="-118.2437"
                                        />
                                        <p class="description">
                                            <a href="https://www.latlong.net/" target="_blank"><?php _e('Find your coordinates', 'nerdy-seo'); ?></a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <!-- Social Tab -->
                    <div class="nerdy-seo-tab-content" data-tab="social">
                        <div class="nerdy-seo-settings-card">
                            <h2><?php _e('Social Media Profiles', 'nerdy-seo'); ?></h2>
                            <p class="description"><?php _e('Add your social media profile URLs to include them in your business schema.', 'nerdy-seo'); ?></p>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_facebook">
                                            <span class="dashicons dashicons-facebook"></span>
                                            <?php _e('Facebook', 'nerdy-seo'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <input
                                            type="url"
                                            id="nerdy_seo_lb_facebook"
                                            name="nerdy_seo_lb_facebook"
                                            value="<?php echo esc_url($facebook); ?>"
                                            class="regular-text"
                                            placeholder="https://www.facebook.com/yourbusiness"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_twitter">
                                            <span class="dashicons dashicons-twitter"></span>
                                            <?php _e('Twitter', 'nerdy-seo'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <input
                                            type="url"
                                            id="nerdy_seo_lb_twitter"
                                            name="nerdy_seo_lb_twitter"
                                            value="<?php echo esc_url($twitter); ?>"
                                            class="regular-text"
                                            placeholder="https://twitter.com/yourbusiness"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_instagram">
                                            <span class="dashicons dashicons-instagram"></span>
                                            <?php _e('Instagram', 'nerdy-seo'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <input
                                            type="url"
                                            id="nerdy_seo_lb_instagram"
                                            name="nerdy_seo_lb_instagram"
                                            value="<?php echo esc_url($instagram); ?>"
                                            class="regular-text"
                                            placeholder="https://www.instagram.com/yourbusiness"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_linkedin">
                                            <span class="dashicons dashicons-linkedin"></span>
                                            <?php _e('LinkedIn', 'nerdy-seo'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <input
                                            type="url"
                                            id="nerdy_seo_lb_linkedin"
                                            name="nerdy_seo_lb_linkedin"
                                            value="<?php echo esc_url($linkedin); ?>"
                                            class="regular-text"
                                            placeholder="https://www.linkedin.com/company/yourbusiness"
                                        />
                                    </td>
                                </tr>
                                <tr>
                                    <th scope="row">
                                        <label for="nerdy_seo_lb_youtube">
                                            <span class="dashicons dashicons-video-alt3"></span>
                                            <?php _e('YouTube', 'nerdy-seo'); ?>
                                        </label>
                                    </th>
                                    <td>
                                        <input
                                            type="url"
                                            id="nerdy_seo_lb_youtube"
                                            name="nerdy_seo_lb_youtube"
                                            value="<?php echo esc_url($youtube); ?>"
                                            class="regular-text"
                                            placeholder="https://www.youtube.com/c/yourbusiness"
                                        />
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>

                    <div class="nerdy-seo-save-bar">
                        <?php submit_button(__('Save Settings', 'nerdy-seo'), 'primary large', 'nerdy_seo_save_local_business', false); ?>
                    </div>
                </div>
            </form>
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
                    title: '<?php _e('Choose Image', 'nerdy-seo'); ?>',
                    button: {
                        text: '<?php _e('Use this image', 'nerdy-seo'); ?>'
                    },
                    multiple: false
                });

                mediaUploader.on('select', function() {
                    var attachment = mediaUploader.state().get('selection').first().toJSON();
                    $('#' + targetId).val(attachment.url);

                    // Show preview
                    var $parent = button.parent();
                    if ($parent.next('.nerdy-seo-image-preview').length) {
                        $parent.next('.nerdy-seo-image-preview').find('img').attr('src', attachment.url);
                    } else {
                        $parent.after('<div class="nerdy-seo-image-preview"><img src="' + attachment.url + '" alt="" /></div>');
                    }
                });

                mediaUploader.open();
            });
        });
        </script>
        <?php
    }

    /**
     * Output local business schema
     */
    public function output_local_business_schema() {
        $enabled = get_option('nerdy_seo_lb_enabled', false);

        if (!$enabled) {
            return;
        }

        // Get schema data
        $schema = $this->get_local_business_schema();

        if (empty($schema)) {
            return;
        }

        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        echo '</script>' . "\n";
    }

    /**
     * Get local business schema
     */
    private function get_local_business_schema() {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => get_option('nerdy_seo_lb_type', 'LocalBusiness'),
        );

        // Basic info
        $name = get_option('nerdy_seo_lb_name');
        if ($name) {
            $schema['name'] = $name;
        }

        $description = get_option('nerdy_seo_lb_description');
        if ($description) {
            $schema['description'] = $description;
        }

        $url = get_option('nerdy_seo_lb_url');
        if ($url) {
            $schema['url'] = $url;
        }

        $phone = get_option('nerdy_seo_lb_phone');
        if ($phone) {
            $schema['telephone'] = $phone;
        }

        $email = get_option('nerdy_seo_lb_email');
        if ($email) {
            $schema['email'] = $email;
        }

        $price_range = get_option('nerdy_seo_lb_price_range');
        if ($price_range) {
            $schema['priceRange'] = $price_range;
        }

        // Images
        $image = get_option('nerdy_seo_lb_image');
        if ($image) {
            $schema['image'] = $image;
        }

        $logo = get_option('nerdy_seo_lb_logo');
        if ($logo) {
            $schema['logo'] = $logo;
        }

        // Address
        $street = get_option('nerdy_seo_lb_street_address');
        $city = get_option('nerdy_seo_lb_city');
        $state = get_option('nerdy_seo_lb_state');
        $postal = get_option('nerdy_seo_lb_postal_code');
        $country = get_option('nerdy_seo_lb_country');

        if ($street || $city || $state || $postal || $country) {
            $address = array('@type' => 'PostalAddress');

            if ($street) $address['streetAddress'] = $street;
            if ($city) $address['addressLocality'] = $city;
            if ($state) $address['addressRegion'] = $state;
            if ($postal) $address['postalCode'] = $postal;
            if ($country) $address['addressCountry'] = $country;

            $schema['address'] = $address;
        }

        // Geo coordinates
        $latitude = get_option('nerdy_seo_lb_latitude');
        $longitude = get_option('nerdy_seo_lb_longitude');

        if ($latitude && $longitude) {
            $schema['geo'] = array(
                '@type' => 'GeoCoordinates',
                'latitude' => $latitude,
                'longitude' => $longitude,
            );
        }

        // Opening hours
        $hours = get_option('nerdy_seo_lb_hours', array());
        $opening_hours = array();

        $days_map = array(
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        );

        foreach ($days_map as $day => $day_name) {
            $open = isset($hours[$day . '_open']) ? $hours[$day . '_open'] : '';
            $close = isset($hours[$day . '_close']) ? $hours[$day . '_close'] : '';

            if ($open && $close) {
                $opening_hours[] = $day_name . ' ' . $open . '-' . $close;
            }
        }

        if (!empty($opening_hours)) {
            $schema['openingHours'] = $opening_hours;
        }

        // Social profiles
        $social_urls = array();

        $facebook = get_option('nerdy_seo_lb_facebook');
        if ($facebook) $social_urls[] = $facebook;

        $twitter = get_option('nerdy_seo_lb_twitter');
        if ($twitter) $social_urls[] = $twitter;

        $instagram = get_option('nerdy_seo_lb_instagram');
        if ($instagram) $social_urls[] = $instagram;

        $linkedin = get_option('nerdy_seo_lb_linkedin');
        if ($linkedin) $social_urls[] = $linkedin;

        $youtube = get_option('nerdy_seo_lb_youtube');
        if ($youtube) $social_urls[] = $youtube;

        if (!empty($social_urls)) {
            $schema['sameAs'] = $social_urls;
        }

        return $schema;
    }
}

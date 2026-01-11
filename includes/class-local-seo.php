<?php
/**
 * Local SEO functionality
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Local SEO class
 */
class Nerdy_SEO_Local_SEO {

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
        // Output local business schema
        add_action('wp_head', array($this, 'output_local_business_schema'), 25);

        // Settings
        add_action('admin_init', array($this, 'register_settings'));

        // Shortcodes
        add_shortcode('nerdy_business_info', array($this, 'business_info_shortcode'));
        add_shortcode('nerdy_business_hours', array($this, 'business_hours_shortcode'));
        add_shortcode('nerdy_google_map', array($this, 'google_map_shortcode'));
    }

    /**
     * Output local business schema
     */
    public function output_local_business_schema() {
        if (!get_option('nerdy_seo_local_enabled', false)) {
            return;
        }

        // Only output on homepage or if specifically enabled
        if (!is_front_page() && !get_option('nerdy_seo_local_all_pages', false)) {
            return;
        }

        $schema = $this->get_local_business_schema();

        if (empty($schema)) {
            return;
        }

        echo "<!-- Local Business Schema -->\n";
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n" . '</script>' . "\n";
        echo "<!-- / Local Business Schema -->\n\n";
    }

    /**
     * Get local business schema
     */
    private function get_local_business_schema() {
        $business_name = get_option('nerdy_seo_local_business_name', get_bloginfo('name'));
        $business_type = get_option('nerdy_seo_local_business_type', 'LocalBusiness');
        $street_address = get_option('nerdy_seo_local_street_address', '');
        $city = get_option('nerdy_seo_local_city', '');
        $state = get_option('nerdy_seo_local_state', '');
        $postal_code = get_option('nerdy_seo_local_postal_code', '');
        $country = get_option('nerdy_seo_local_country', 'US');
        $phone = get_option('nerdy_seo_local_phone', '');
        $email = get_option('nerdy_seo_local_email', '');
        $price_range = get_option('nerdy_seo_local_price_range', '');

        if (empty($street_address) || empty($city)) {
            return array();
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $business_type,
            'name' => $business_name,
            'image' => get_site_icon_url(),
            'url' => home_url('/'),
            'address' => array(
                '@type' => 'PostalAddress',
                'streetAddress' => $street_address,
                'addressLocality' => $city,
                'addressRegion' => $state,
                'postalCode' => $postal_code,
                'addressCountry' => $country,
            ),
        );

        // Add phone
        if ($phone) {
            $schema['telephone'] = $phone;
        }

        // Add email
        if ($email) {
            $schema['email'] = $email;
        }

        // Add price range
        if ($price_range) {
            $schema['priceRange'] = $price_range;
        }

        // Add business hours
        $hours = $this->get_business_hours();
        if (!empty($hours)) {
            $schema['openingHoursSpecification'] = $hours;
        }

        // Add geo coordinates
        $latitude = get_option('nerdy_seo_local_latitude', '');
        $longitude = get_option('nerdy_seo_local_longitude', '');

        if ($latitude && $longitude) {
            $schema['geo'] = array(
                '@type' => 'GeoCoordinates',
                'latitude' => $latitude,
                'longitude' => $longitude,
            );
        }

        // Add same as (social profiles)
        $social = array();
        $facebook = get_option('nerdy_seo_local_facebook', '');
        $twitter = get_option('nerdy_seo_local_twitter', '');
        $instagram = get_option('nerdy_seo_local_instagram', '');
        $linkedin = get_option('nerdy_seo_local_linkedin', '');

        if ($facebook) $social[] = $facebook;
        if ($twitter) $social[] = $twitter;
        if ($instagram) $social[] = $instagram;
        if ($linkedin) $social[] = $linkedin;

        if (!empty($social)) {
            $schema['sameAs'] = $social;
        }

        return apply_filters('nerdy_seo_local_business_schema', $schema);
    }

    /**
     * Get business hours for schema
     */
    private function get_business_hours() {
        $hours = array();
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');

        foreach ($days as $day) {
            $enabled = get_option("nerdy_seo_local_hours_{$day}_enabled", true);
            $open = get_option("nerdy_seo_local_hours_{$day}_open", '09:00');
            $close = get_option("nerdy_seo_local_hours_{$day}_close", '17:00');

            if ($enabled && $open && $close) {
                $hours[] = array(
                    '@type' => 'OpeningHoursSpecification',
                    'dayOfWeek' => ucfirst($day),
                    'opens' => $open,
                    'closes' => $close,
                );
            }
        }

        return $hours;
    }

    /**
     * Business info shortcode
     */
    public function business_info_shortcode($atts) {
        $atts = shortcode_atts(array(
            'show' => 'all', // all, address, phone, email
        ), $atts);

        $business_name = get_option('nerdy_seo_local_business_name', get_bloginfo('name'));
        $street_address = get_option('nerdy_seo_local_street_address', '');
        $city = get_option('nerdy_seo_local_city', '');
        $state = get_option('nerdy_seo_local_state', '');
        $postal_code = get_option('nerdy_seo_local_postal_code', '');
        $phone = get_option('nerdy_seo_local_phone', '');
        $email = get_option('nerdy_seo_local_email', '');

        ob_start();
        ?>
        <div class="nerdy-business-info">
            <?php if ($atts['show'] === 'all' || $atts['show'] === 'name'): ?>
                <div class="business-name">
                    <strong><?php echo esc_html($business_name); ?></strong>
                </div>
            <?php endif; ?>

            <?php if (($atts['show'] === 'all' || $atts['show'] === 'address') && $street_address): ?>
                <div class="business-address">
                    <span itemprop="streetAddress"><?php echo esc_html($street_address); ?></span><br>
                    <span itemprop="addressLocality"><?php echo esc_html($city); ?></span>,
                    <span itemprop="addressRegion"><?php echo esc_html($state); ?></span>
                    <span itemprop="postalCode"><?php echo esc_html($postal_code); ?></span>
                </div>
            <?php endif; ?>

            <?php if (($atts['show'] === 'all' || $atts['show'] === 'phone') && $phone): ?>
                <div class="business-phone">
                    <a href="tel:<?php echo esc_attr(preg_replace('/[^0-9+]/', '', $phone)); ?>">
                        <?php echo esc_html($phone); ?>
                    </a>
                </div>
            <?php endif; ?>

            <?php if (($atts['show'] === 'all' || $atts['show'] === 'email') && $email): ?>
                <div class="business-email">
                    <a href="mailto:<?php echo esc_attr($email); ?>">
                        <?php echo esc_html($email); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Business hours shortcode
     */
    public function business_hours_shortcode($atts) {
        $atts = shortcode_atts(array(
            'format' => 'list', // list, table
        ), $atts);

        $days = array(
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        );

        ob_start();

        if ($atts['format'] === 'table') {
            ?>
            <table class="nerdy-business-hours-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Day', 'nerdy-seo'); ?></th>
                        <th><?php esc_html_e('Hours', 'nerdy-seo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($days as $day_key => $day_name): ?>
                        <?php
                        $enabled = get_option("nerdy_seo_local_hours_{$day_key}_enabled", true);
                        $open = get_option("nerdy_seo_local_hours_{$day_key}_open", '09:00');
                        $close = get_option("nerdy_seo_local_hours_{$day_key}_close", '17:00');
                        ?>
                        <tr>
                            <td><?php echo esc_html($day_name); ?></td>
                            <td>
                                <?php if ($enabled): ?>
                                    <?php echo esc_html($this->format_time($open)); ?> - <?php echo esc_html($this->format_time($close)); ?>
                                <?php else: ?>
                                    <em><?php esc_html_e('Closed', 'nerdy-seo'); ?></em>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        } else {
            ?>
            <ul class="nerdy-business-hours-list">
                <?php foreach ($days as $day_key => $day_name): ?>
                    <?php
                    $enabled = get_option("nerdy_seo_local_hours_{$day_key}_enabled", true);
                    $open = get_option("nerdy_seo_local_hours_{$day_key}_open", '09:00');
                    $close = get_option("nerdy_seo_local_hours_{$day_key}_close", '17:00');
                    ?>
                    <li>
                        <strong><?php echo esc_html($day_name); ?>:</strong>
                        <?php if ($enabled): ?>
                            <?php echo esc_html($this->format_time($open)); ?> - <?php echo esc_html($this->format_time($close)); ?>
                        <?php else: ?>
                            <em><?php esc_html_e('Closed', 'nerdy-seo'); ?></em>
                        <?php endif; ?>
                    </li>
                <?php endforeach; ?>
            </ul>
            <?php
        }

        return ob_get_clean();
    }

    /**
     * Google Maps shortcode
     */
    public function google_map_shortcode($atts) {
        $atts = shortcode_atts(array(
            'width' => '100%',
            'height' => '400px',
            'zoom' => '15',
        ), $atts);

        $api_key = get_option('nerdy_seo_local_google_maps_api', '');
        $latitude = get_option('nerdy_seo_local_latitude', '');
        $longitude = get_option('nerdy_seo_local_longitude', '');
        $street_address = get_option('nerdy_seo_local_street_address', '');
        $city = get_option('nerdy_seo_local_city', '');
        $state = get_option('nerdy_seo_local_state', '');

        if (!$latitude || !$longitude) {
            return '<p>' . __('Please configure location coordinates in Local SEO settings.', 'nerdy-seo') . '</p>';
        }

        $address = urlencode($street_address . ', ' . $city . ', ' . $state);

        ob_start();
        ?>
        <div class="nerdy-google-map" style="width: <?php echo esc_attr($atts['width']); ?>; height: <?php echo esc_attr($atts['height']); ?>;">
            <?php if ($api_key): ?>
                <iframe
                    width="100%"
                    height="100%"
                    frameborder="0"
                    style="border:0"
                    referrerpolicy="no-referrer-when-downgrade"
                    src="https://www.google.com/maps/embed/v1/place?key=<?php echo esc_attr($api_key); ?>&q=<?php echo esc_attr($latitude); ?>,<?php echo esc_attr($longitude); ?>&zoom=<?php echo esc_attr($atts['zoom']); ?>"
                    allowfullscreen>
                </iframe>
            <?php else: ?>
                <!-- Fallback to basic Google Maps link -->
                <iframe
                    width="100%"
                    height="100%"
                    frameborder="0"
                    style="border:0"
                    src="https://maps.google.com/maps?q=<?php echo esc_attr($latitude); ?>,<?php echo esc_attr($longitude); ?>&z=<?php echo esc_attr($atts['zoom']); ?>&output=embed"
                    allowfullscreen>
                </iframe>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }

    /**
     * Format time for display
     */
    private function format_time($time) {
        return gmdate('g:i A', strtotime($time));
    }

    /**
     * Register settings
     */
    public function register_settings() {
        // Business info settings
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_enabled');
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_all_pages');
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_business_name');
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_business_type');
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_street_address');
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_city');
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_state');
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_postal_code');
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_country');
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_phone');
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_email');
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_price_range');
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_latitude');
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_longitude');
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_google_maps_api');

        // Social profiles
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_facebook');
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_twitter');
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_instagram');
        register_setting('nerdy_seo_settings', 'nerdy_seo_local_linkedin');

        // Business hours
        $days = array('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday');
        foreach ($days as $day) {
            register_setting('nerdy_seo_settings', "nerdy_seo_local_hours_{$day}_enabled");
            register_setting('nerdy_seo_settings', "nerdy_seo_local_hours_{$day}_open");
            register_setting('nerdy_seo_settings', "nerdy_seo_local_hours_{$day}_close");
        }

        // Local SEO section
        add_settings_section(
            'nerdy_seo_local_section',
            __('Local SEO Settings', 'nerdy-seo'),
            array($this, 'render_local_section'),
            'nerdy-seo'
        );

        add_settings_field(
            'nerdy_seo_local_enabled',
            __('Enable Local SEO', 'nerdy-seo'),
            array($this, 'render_local_enabled_field'),
            'nerdy-seo',
            'nerdy_seo_local_section'
        );

        add_settings_field(
            'nerdy_seo_local_business_info',
            __('Business Information', 'nerdy-seo'),
            array($this, 'render_business_info_fields'),
            'nerdy-seo',
            'nerdy_seo_local_section'
        );

        add_settings_field(
            'nerdy_seo_local_location',
            __('Location & Maps', 'nerdy-seo'),
            array($this, 'render_location_fields'),
            'nerdy-seo',
            'nerdy_seo_local_section'
        );

        add_settings_field(
            'nerdy_seo_local_hours',
            __('Business Hours', 'nerdy-seo'),
            array($this, 'render_hours_fields'),
            'nerdy-seo',
            'nerdy_seo_local_section'
        );

        add_settings_field(
            'nerdy_seo_local_social',
            __('Social Profiles', 'nerdy-seo'),
            array($this, 'render_social_fields'),
            'nerdy-seo',
            'nerdy_seo_local_section'
        );
    }

    /**
     * Render local section
     */
    public function render_local_section() {
        echo '<p>' . __('Configure your local business information for improved local search visibility and schema markup.', 'nerdy-seo') . '</p>';
    }

    /**
     * Render local enabled field
     */
    public function render_local_enabled_field() {
        $enabled = get_option('nerdy_seo_local_enabled', false);
        $all_pages = get_option('nerdy_seo_local_all_pages', false);
        ?>
        <label>
            <input type="checkbox" name="nerdy_seo_local_enabled" value="1" <?php checked($enabled, true); ?> />
            <?php esc_html_e('Enable Local Business schema markup', 'nerdy-seo'); ?>
        </label>
        <br><br>
        <label>
            <input type="checkbox" name="nerdy_seo_local_all_pages" value="1" <?php checked($all_pages, true); ?> />
            <?php esc_html_e('Show schema on all pages (default: homepage only)', 'nerdy-seo'); ?>
        </label>
        <?php
    }

    /**
     * Render business info fields
     */
    public function render_business_info_fields() {
        $business_name = get_option('nerdy_seo_local_business_name', get_bloginfo('name'));
        $business_type = get_option('nerdy_seo_local_business_type', 'LocalBusiness');
        $street_address = get_option('nerdy_seo_local_street_address', '');
        $city = get_option('nerdy_seo_local_city', '');
        $state = get_option('nerdy_seo_local_state', '');
        $postal_code = get_option('nerdy_seo_local_postal_code', '');
        $country = get_option('nerdy_seo_local_country', 'US');
        $phone = get_option('nerdy_seo_local_phone', '');
        $email = get_option('nerdy_seo_local_email', '');
        $price_range = get_option('nerdy_seo_local_price_range', '');
        ?>
        <table class="form-table" style="margin: 0;">
            <tr>
                <th><label><?php esc_html_e('Business Name', 'nerdy-seo'); ?></label></th>
                <td>
                    <input type="text" name="nerdy_seo_local_business_name" value="<?php echo esc_attr($business_name); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Business Type', 'nerdy-seo'); ?></label></th>
                <td>
                    <select name="nerdy_seo_local_business_type">
                        <option value="LocalBusiness" <?php selected($business_type, 'LocalBusiness'); ?>><?php esc_html_e('Local Business', 'nerdy-seo'); ?></option>
                        <option value="Restaurant" <?php selected($business_type, 'Restaurant'); ?>><?php esc_html_e('Restaurant', 'nerdy-seo'); ?></option>
                        <option value="Store" <?php selected($business_type, 'Store'); ?>><?php esc_html_e('Store', 'nerdy-seo'); ?></option>
                        <option value="ProfessionalService" <?php selected($business_type, 'ProfessionalService'); ?>><?php esc_html_e('Professional Service', 'nerdy-seo'); ?></option>
                        <option value="HealthAndBeautyBusiness" <?php selected($business_type, 'HealthAndBeautyBusiness'); ?>><?php esc_html_e('Health & Beauty', 'nerdy-seo'); ?></option>
                        <option value="HomeAndConstructionBusiness" <?php selected($business_type, 'HomeAndConstructionBusiness'); ?>><?php esc_html_e('Home & Construction', 'nerdy-seo'); ?></option>
                        <option value="AutoRepair" <?php selected($business_type, 'AutoRepair'); ?>><?php esc_html_e('Auto Repair', 'nerdy-seo'); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Street Address', 'nerdy-seo'); ?></label></th>
                <td>
                    <input type="text" name="nerdy_seo_local_street_address" value="<?php echo esc_attr($street_address); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('City', 'nerdy-seo'); ?></label></th>
                <td>
                    <input type="text" name="nerdy_seo_local_city" value="<?php echo esc_attr($city); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('State/Region', 'nerdy-seo'); ?></label></th>
                <td>
                    <input type="text" name="nerdy_seo_local_state" value="<?php echo esc_attr($state); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Postal Code', 'nerdy-seo'); ?></label></th>
                <td>
                    <input type="text" name="nerdy_seo_local_postal_code" value="<?php echo esc_attr($postal_code); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Country', 'nerdy-seo'); ?></label></th>
                <td>
                    <input type="text" name="nerdy_seo_local_country" value="<?php echo esc_attr($country); ?>" class="regular-text" placeholder="US" />
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Phone', 'nerdy-seo'); ?></label></th>
                <td>
                    <input type="text" name="nerdy_seo_local_phone" value="<?php echo esc_attr($phone); ?>" class="regular-text" placeholder="(555) 123-4567" />
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Email', 'nerdy-seo'); ?></label></th>
                <td>
                    <input type="email" name="nerdy_seo_local_email" value="<?php echo esc_attr($email); ?>" class="regular-text" />
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Price Range', 'nerdy-seo'); ?></label></th>
                <td>
                    <input type="text" name="nerdy_seo_local_price_range" value="<?php echo esc_attr($price_range); ?>" class="small-text" placeholder="$$" />
                    <p class="description"><?php esc_html_e('e.g., $, $$, $$$, or $$$$', 'nerdy-seo'); ?></p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render location fields
     */
    public function render_location_fields() {
        $latitude = get_option('nerdy_seo_local_latitude', '');
        $longitude = get_option('nerdy_seo_local_longitude', '');
        $api_key = get_option('nerdy_seo_local_google_maps_api', '');
        ?>
        <table class="form-table" style="margin: 0;">
            <tr>
                <th><label><?php esc_html_e('Latitude', 'nerdy-seo'); ?></label></th>
                <td>
                    <input type="text" name="nerdy_seo_local_latitude" value="<?php echo esc_attr($latitude); ?>" class="regular-text" placeholder="34.0522" />
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Longitude', 'nerdy-seo'); ?></label></th>
                <td>
                    <input type="text" name="nerdy_seo_local_longitude" value="<?php echo esc_attr($longitude); ?>" class="regular-text" placeholder="-118.2437" />
                    <p class="description">
                        <?php esc_html_e('Find coordinates:', 'nerdy-seo'); ?>
                        <a href="https://www.latlong.net/" target="_blank">latlong.net</a>
                    </p>
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Google Maps API Key', 'nerdy-seo'); ?></label></th>
                <td>
                    <input type="text" name="nerdy_seo_local_google_maps_api" value="<?php echo esc_attr($api_key); ?>" class="regular-text" />
                    <p class="description">
                        <?php esc_html_e('Optional. Get API key:', 'nerdy-seo'); ?>
                        <a href="https://developers.google.com/maps/documentation/embed/get-api-key" target="_blank">Google Maps Platform</a>
                    </p>
                </td>
            </tr>
        </table>
        <?php
    }

    /**
     * Render hours fields
     */
    public function render_hours_fields() {
        $days = array(
            'monday' => 'Monday',
            'tuesday' => 'Tuesday',
            'wednesday' => 'Wednesday',
            'thursday' => 'Thursday',
            'friday' => 'Friday',
            'saturday' => 'Saturday',
            'sunday' => 'Sunday',
        );
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th style="width: 20%;"><?php esc_html_e('Day', 'nerdy-seo'); ?></th>
                    <th style="width: 15%;"><?php esc_html_e('Open', 'nerdy-seo'); ?></th>
                    <th style="width: 30%;"><?php esc_html_e('Opening Time', 'nerdy-seo'); ?></th>
                    <th style="width: 30%;"><?php esc_html_e('Closing Time', 'nerdy-seo'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($days as $day_key => $day_name): ?>
                    <?php
                    $enabled = get_option("nerdy_seo_local_hours_{$day_key}_enabled", true);
                    $open = get_option("nerdy_seo_local_hours_{$day_key}_open", '09:00');
                    $close = get_option("nerdy_seo_local_hours_{$day_key}_close", '17:00');
                    ?>
                    <tr>
                        <td><strong><?php echo esc_html($day_name); ?></strong></td>
                        <td>
                            <input type="checkbox" name="nerdy_seo_local_hours_<?php echo esc_attr($day_key); ?>_enabled" value="1" <?php checked($enabled, true); ?> />
                        </td>
                        <td>
                            <input type="time" name="nerdy_seo_local_hours_<?php echo esc_attr($day_key); ?>_open" value="<?php echo esc_attr($open); ?>" />
                        </td>
                        <td>
                            <input type="time" name="nerdy_seo_local_hours_<?php echo esc_attr($day_key); ?>_close" value="<?php echo esc_attr($close); ?>" />
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <p class="description"><?php esc_html_e('Uncheck "Open" for days when you are closed', 'nerdy-seo'); ?></p>
        <?php
    }

    /**
     * Render social fields
     */
    public function render_social_fields() {
        $facebook = get_option('nerdy_seo_local_facebook', '');
        $twitter = get_option('nerdy_seo_local_twitter', '');
        $instagram = get_option('nerdy_seo_local_instagram', '');
        $linkedin = get_option('nerdy_seo_local_linkedin', '');
        ?>
        <table class="form-table" style="margin: 0;">
            <tr>
                <th><label><?php esc_html_e('Facebook URL', 'nerdy-seo'); ?></label></th>
                <td>
                    <input type="url" name="nerdy_seo_local_facebook" value="<?php echo esc_url($facebook); ?>" class="regular-text" placeholder="https://facebook.com/yourpage" />
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Twitter URL', 'nerdy-seo'); ?></label></th>
                <td>
                    <input type="url" name="nerdy_seo_local_twitter" value="<?php echo esc_url($twitter); ?>" class="regular-text" placeholder="https://twitter.com/youraccount" />
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('Instagram URL', 'nerdy-seo'); ?></label></th>
                <td>
                    <input type="url" name="nerdy_seo_local_instagram" value="<?php echo esc_url($instagram); ?>" class="regular-text" placeholder="https://instagram.com/youraccount" />
                </td>
            </tr>
            <tr>
                <th><label><?php esc_html_e('LinkedIn URL', 'nerdy-seo'); ?></label></th>
                <td>
                    <input type="url" name="nerdy_seo_local_linkedin" value="<?php echo esc_url($linkedin); ?>" class="regular-text" placeholder="https://linkedin.com/company/yourcompany" />
                </td>
            </tr>
        </table>
        <p class="description"><?php esc_html_e('These URLs are included in your Local Business schema', 'nerdy-seo'); ?></p>
        <?php
    }
}

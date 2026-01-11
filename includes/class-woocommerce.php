<?php
/**
 * WooCommerce Integration
 *
 * @package Nerdy_SEO
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * WooCommerce class
 */
class Nerdy_SEO_WooCommerce {

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
        // Only initialize if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            return;
        }

        // Output product schema
        add_action('wp_head', array($this, 'output_product_schema'), 25);

        // Disable WooCommerce's default schema (we'll use ours)
        add_filter('woocommerce_structured_data_product', '__return_false');

        // Settings
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Output product schema
     */
    public function output_product_schema() {
        if (!get_option('nerdy_seo_woocommerce_schema', true)) {
            return;
        }

        if (!is_product()) {
            return;
        }

        global $product;

        if (!$product) {
            return;
        }

        $schema = $this->get_product_schema($product);

        if (empty($schema)) {
            return;
        }

        echo "<!-- Product Schema -->\n";
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        echo "\n" . '</script>' . "\n";
        echo "<!-- / Product Schema -->\n\n";
    }

    /**
     * Get product schema
     */
    private function get_product_schema($product) {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->get_name(),
            'description' => wp_strip_all_tags($product->get_short_description() ?: $product->get_description()),
            'url' => get_permalink($product->get_id()),
            'sku' => $product->get_sku(),
        );

        // Image
        $image_id = $product->get_image_id();
        if ($image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'full');
            if ($image_url) {
                $schema['image'] = $image_url;
            }
        }

        // Brand (from custom field or site name)
        $brand = get_post_meta($product->get_id(), '_nerdy_seo_product_brand', true);
        if (empty($brand)) {
            $brand = get_bloginfo('name');
        }

        $schema['brand'] = array(
            '@type' => 'Brand',
            'name' => $brand,
        );

        // Offers (Price and Availability)
        $offers = $this->get_product_offers($product);
        if (!empty($offers)) {
            $schema['offers'] = $offers;
        }

        // Aggregate Rating
        $rating_data = $this->get_product_rating($product);
        if (!empty($rating_data)) {
            $schema['aggregateRating'] = $rating_data;
        }

        // Reviews
        $reviews = $this->get_product_reviews($product);
        if (!empty($reviews)) {
            $schema['review'] = $reviews;
        }

        // GTIN/MPN (if available)
        $gtin = get_post_meta($product->get_id(), '_nerdy_seo_product_gtin', true);
        if ($gtin) {
            $schema['gtin'] = $gtin;
        }

        $mpn = get_post_meta($product->get_id(), '_nerdy_seo_product_mpn', true);
        if ($mpn) {
            $schema['mpn'] = $mpn;
        }

        return apply_filters('nerdy_seo_product_schema', $schema, $product);
    }

    /**
     * Get product offers
     */
    private function get_product_offers($product) {
        $offers = array(
            '@type' => 'Offer',
            'url' => get_permalink($product->get_id()),
            'priceCurrency' => get_woocommerce_currency(),
            'price' => $product->get_price(),
            'priceValidUntil' => gmdate('Y-12-31'), // End of current year
        );

        // Availability
        if ($product->is_in_stock()) {
            $offers['availability'] = 'https://schema.org/InStock';
        } else {
            $offers['availability'] = 'https://schema.org/OutOfStock';
        }

        // Sale price
        if ($product->is_on_sale()) {
            $offers['price'] = $product->get_sale_price();
        }

        // Seller
        $offers['seller'] = array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
        );

        // Shipping details (if configured)
        $shipping_cost = get_option('nerdy_seo_woocommerce_shipping_cost', '');
        if ($shipping_cost !== '') {
            $offers['shippingDetails'] = array(
                '@type' => 'OfferShippingDetails',
                'shippingRate' => array(
                    '@type' => 'MonetaryAmount',
                    'value' => $shipping_cost,
                    'currency' => get_woocommerce_currency(),
                ),
            );
        }

        return $offers;
    }

    /**
     * Get product rating
     */
    private function get_product_rating($product) {
        $rating_count = $product->get_rating_count();
        $average_rating = $product->get_average_rating();

        if ($rating_count === 0 || !$average_rating) {
            return array();
        }

        return array(
            '@type' => 'AggregateRating',
            'ratingValue' => $average_rating,
            'reviewCount' => $rating_count,
            'bestRating' => '5',
            'worstRating' => '1',
        );
    }

    /**
     * Get product reviews
     */
    private function get_product_reviews($product) {
        $reviews = array();

        $comments = get_comments(array(
            'post_id' => $product->get_id(),
            'status' => 'approve',
            'type' => 'review',
            'number' => 5, // Limit to 5 most recent reviews
        ));

        foreach ($comments as $comment) {
            $rating = get_comment_meta($comment->comment_ID, 'rating', true);

            if (!$rating) {
                continue;
            }

            $reviews[] = array(
                '@type' => 'Review',
                'reviewRating' => array(
                    '@type' => 'Rating',
                    'ratingValue' => $rating,
                    'bestRating' => '5',
                    'worstRating' => '1',
                ),
                'author' => array(
                    '@type' => 'Person',
                    'name' => $comment->comment_author,
                ),
                'datePublished' => gmdate('c', strtotime($comment->comment_date)),
                'reviewBody' => $comment->comment_content,
            );
        }

        return $reviews;
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('nerdy_seo_settings', 'nerdy_seo_woocommerce_schema');
        register_setting('nerdy_seo_settings', 'nerdy_seo_woocommerce_shipping_cost');

        // WooCommerce section
        add_settings_section(
            'nerdy_seo_woocommerce_section',
            __('WooCommerce Settings', 'nerdy-seo'),
            array($this, 'render_woocommerce_section'),
            'nerdy-seo'
        );

        add_settings_field(
            'nerdy_seo_woocommerce_schema',
            __('Enable Product Schema', 'nerdy-seo'),
            array($this, 'render_woocommerce_schema_field'),
            'nerdy-seo',
            'nerdy_seo_woocommerce_section'
        );

        add_settings_field(
            'nerdy_seo_woocommerce_shipping_cost',
            __('Default Shipping Cost', 'nerdy-seo'),
            array($this, 'render_shipping_cost_field'),
            'nerdy-seo',
            'nerdy_seo_woocommerce_section'
        );
    }

    /**
     * Render WooCommerce section
     */
    public function render_woocommerce_section() {
        if (!class_exists('WooCommerce')) {
            echo '<p style="color: #dc3232;">' . __('WooCommerce is not active. Install and activate WooCommerce to use these features.', 'nerdy-seo') . '</p>';
            return;
        }

        echo '<p>' . __('Configure SEO settings for your WooCommerce store.', 'nerdy-seo') . '</p>';
    }

    /**
     * Render WooCommerce schema field
     */
    public function render_woocommerce_schema_field() {
        $enabled = get_option('nerdy_seo_woocommerce_schema', true);
        ?>
        <label>
            <input type="checkbox" name="nerdy_seo_woocommerce_schema" value="1" <?php checked($enabled, true); ?> />
            <?php esc_html_e('Enable Product schema markup (includes price, availability, reviews)', 'nerdy-seo'); ?>
        </label>
        <p class="description"><?php esc_html_e('Helps products appear in Google Shopping and rich results', 'nerdy-seo'); ?></p>
        <?php
    }

    /**
     * Render shipping cost field
     */
    public function render_shipping_cost_field() {
        $cost = get_option('nerdy_seo_woocommerce_shipping_cost', '');
        ?>
        <input
            type="text"
            name="nerdy_seo_woocommerce_shipping_cost"
            value="<?php echo esc_attr($cost); ?>"
            class="small-text"
            placeholder="0.00"
        />
        <p class="description">
            <?php esc_html_e('Default shipping cost in your store currency. Leave blank if free shipping or variable.', 'nerdy-seo'); ?>
        </p>
        <?php
    }

    /**
     * Add product meta box
     */
    public function add_product_meta_box() {
        add_meta_box(
            'nerdy_seo_product_meta_box',
            __('Product SEO Data', 'nerdy-seo'),
            array($this, 'render_product_meta_box'),
            'product',
            'side',
            'default'
        );
    }

    /**
     * Render product meta box
     */
    public function render_product_meta_box($post) {
        wp_nonce_field('nerdy_seo_product_meta_box', 'nerdy_seo_product_meta_box_nonce');

        $brand = get_post_meta($post->ID, '_nerdy_seo_product_brand', true);
        $gtin = get_post_meta($post->ID, '_nerdy_seo_product_gtin', true);
        $mpn = get_post_meta($post->ID, '_nerdy_seo_product_mpn', true);

        ?>
        <style>
            .nerdy-seo-product-field { margin-bottom: 15px; }
            .nerdy-seo-product-field label {
                display: block;
                font-weight: 600;
                margin-bottom: 5px;
                font-size: 13px;
            }
            .nerdy-seo-product-field input {
                width: 100%;
            }
            .nerdy-seo-product-hint {
                font-size: 12px;
                color: #666;
                margin-top: 3px;
            }
        </style>

        <div class="nerdy-seo-product-field">
            <label for="nerdy_seo_product_brand">
                <?php esc_html_e('Brand', 'nerdy-seo'); ?>
            </label>
            <input
                type="text"
                id="nerdy_seo_product_brand"
                name="nerdy_seo_product_brand"
                value="<?php echo esc_attr($brand); ?>"
                placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>"
            />
            <p class="nerdy-seo-product-hint">
                <?php esc_html_e('Product brand name', 'nerdy-seo'); ?>
            </p>
        </div>

        <div class="nerdy-seo-product-field">
            <label for="nerdy_seo_product_gtin">
                <?php esc_html_e('GTIN', 'nerdy-seo'); ?>
            </label>
            <input
                type="text"
                id="nerdy_seo_product_gtin"
                name="nerdy_seo_product_gtin"
                value="<?php echo esc_attr($gtin); ?>"
                placeholder="0123456789012"
            />
            <p class="nerdy-seo-product-hint">
                <?php esc_html_e('Global Trade Item Number (UPC, EAN, ISBN)', 'nerdy-seo'); ?>
            </p>
        </div>

        <div class="nerdy-seo-product-field">
            <label for="nerdy_seo_product_mpn">
                <?php esc_html_e('MPN', 'nerdy-seo'); ?>
            </label>
            <input
                type="text"
                id="nerdy_seo_product_mpn"
                name="nerdy_seo_product_mpn"
                value="<?php echo esc_attr($mpn); ?>"
            />
            <p class="nerdy-seo-product-hint">
                <?php esc_html_e('Manufacturer Part Number', 'nerdy-seo'); ?>
            </p>
        </div>
        <?php
    }

    /**
     * Save product meta box
     */
    public function save_product_meta_box($post_id, $post) {
        if (!isset($_POST['nerdy_seo_product_meta_box_nonce']) ||
            !wp_verify_nonce($_POST['nerdy_seo_product_meta_box_nonce'], 'nerdy_seo_product_meta_box')) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (isset($_POST['nerdy_seo_product_brand'])) {
            update_post_meta($post_id, '_nerdy_seo_product_brand', sanitize_text_field($_POST['nerdy_seo_product_brand']));
        }

        if (isset($_POST['nerdy_seo_product_gtin'])) {
            update_post_meta($post_id, '_nerdy_seo_product_gtin', sanitize_text_field($_POST['nerdy_seo_product_gtin']));
        }

        if (isset($_POST['nerdy_seo_product_mpn'])) {
            update_post_meta($post_id, '_nerdy_seo_product_mpn', sanitize_text_field($_POST['nerdy_seo_product_mpn']));
        }
    }
}

// Initialize product meta box if WooCommerce is active
if (class_exists('WooCommerce')) {
    add_action('add_meta_boxes', array('Nerdy_SEO_WooCommerce', 'get_instance')->add_product_meta_box());
    add_action('save_post', array('Nerdy_SEO_WooCommerce', 'get_instance')->save_product_meta_box(), 10, 2);
}

<?php
/**
 * Product Fields Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CMC_Product_Fields {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Add custom pricing fields for simple products
        add_action('woocommerce_product_options_pricing', array($this, 'add_simple_product_fields'));
        
        // Save custom pricing fields for simple products
        add_action('woocommerce_process_product_meta', array($this, 'save_simple_product_fields'));
        
        // Add custom pricing fields for product variations
        add_action('woocommerce_variation_options_pricing', array($this, 'add_variation_fields'), 10, 3);
        
        // Save custom pricing fields for variations
        add_action('woocommerce_save_product_variation', array($this, 'save_variation_fields'), 10, 2);
        
        // Enqueue admin scripts for product edit page
        add_action('admin_enqueue_scripts', array($this, 'enqueue_product_scripts'));
    }
    
    /**
     * Enqueue scripts for product edit page
     */
    public function enqueue_product_scripts($hook) {
        global $post_type;
        
        if ('product' !== $post_type) {
            return;
        }
        
        wp_enqueue_style('cmc-product-css', CMC_PLUGIN_URL . 'assets/css/product.css', array(), CMC_VERSION);
        wp_enqueue_script('cmc-product-js', CMC_PLUGIN_URL . 'assets/js/product.js', array('jquery'), CMC_VERSION, true);
    }
    
    /**
     * Add custom pricing fields for simple products
     */
    public function add_simple_product_fields() {
        global $post;

        // Only show fields for additional (non-default) currencies.
        // The base WooCommerce currency already has its own native price fields.
        $enabled_currencies = CMC_Currency_Manager::get_additional_currencies();

        if (empty($enabled_currencies)) {
            return;
        }
        
        echo '<div class="options_group cmc_custom_prices">';
        echo '<p class="form-field"><strong>' . esc_html__('Multi-Currency Pricing', 'custom-multi-currency') . '</strong></p>';
        echo '<p class="description" style="padding: 0 12px; margin-top: -10px; margin-bottom: 15px;">' . 
             esc_html__('Set custom prices for each currency. Leave empty to use the regular price.', 'custom-multi-currency') . 
             '</p>';
        
        echo '<div class="cmc-accordion-wrapper">';
        
        foreach ($enabled_currencies as $index => $currency) {
            $field_id = '_cmc_price_' . strtolower($currency);
            $regular_price = get_post_meta($post->ID, $field_id . '_regular', true);
            $sale_price = get_post_meta($post->ID, $field_id . '_sale', true);
            
            $is_open = $index === 0 ? 'open' : ''; // First one open by default
            
            ?>
            <div class="cmc-currency-accordion <?php echo esc_attr($is_open); ?>">
                <div class="cmc-accordion-header">
                    <h4>
                        <span class="cmc-currency-name">
                            <?php echo esc_html($currency); ?> - <?php echo esc_html(CMC_Currency_Manager::get_currency_symbol($currency)); ?>
                            <?php if ($regular_price || $sale_price) : ?>
                                <span class="cmc-price-preview">
                                    <?php 
                                    if ($sale_price) {
                                        echo '<del>' . esc_html(CMC_Currency_Manager::get_currency_symbol($currency) . $regular_price) . '</del> ';
                                        echo esc_html(CMC_Currency_Manager::get_currency_symbol($currency) . $sale_price);
                                    } elseif ($regular_price) {
                                        echo esc_html(CMC_Currency_Manager::get_currency_symbol($currency) . $regular_price);
                                    }
                                    ?>
                                </span>
                            <?php endif; ?>
                        </span>
                        <span class="cmc-toggle-indicator"></span>
                    </h4>
                </div>
                <div class="cmc-accordion-content">
                    <?php
                    woocommerce_wp_text_input(array(
                        'id'                => $field_id . '_regular',
                        'label'             => sprintf(__('Regular Price (%s)', 'custom-multi-currency'), $currency),
                        'placeholder'       => wc_format_localized_price(0),
                        'value'             => $regular_price,
                        'type'              => 'text',
                        'data_type'         => 'price',
                        'desc_tip'          => true,
                        'description'       => sprintf(__('Enter the regular price in %s', 'custom-multi-currency'), $currency),
                        'wrapper_class'     => 'form-row form-row-first',
                        'custom_attributes' => array('data-cmc-field' => 'regular'),
                    ));

                    woocommerce_wp_text_input(array(
                        'id'                => $field_id . '_sale',
                        'label'             => sprintf(__('Sale Price (%s)', 'custom-multi-currency'), $currency),
                        'placeholder'       => wc_format_localized_price(0),
                        'value'             => $sale_price,
                        'type'              => 'text',
                        'data_type'         => 'price',
                        'desc_tip'          => true,
                        'description'       => sprintf(__('Enter the sale price in %s', 'custom-multi-currency'), $currency),
                        'wrapper_class'     => 'form-row form-row-last',
                        'custom_attributes' => array('data-cmc-field' => 'sale'),
                    ));
                    ?>
                    <p class="cmc-price-error" style="display:none;">
                        <?php esc_html_e('Sale price must be lower than the regular price.', 'custom-multi-currency'); ?>
                    </p>
                </div>
            </div>
            <?php
        }

        echo '</div>'; // .cmc-accordion-wrapper
        echo '</div>'; // .options_group
    }
    
    /**
     * Save custom pricing fields for simple products
     */
    public function save_simple_product_fields($post_id) {
        $enabled_currencies = CMC_Currency_Manager::get_additional_currencies();
        
        foreach ($enabled_currencies as $currency) {
            $field_id = '_cmc_price_' . strtolower($currency);
            
            // Save regular price
            if (isset($_POST[$field_id . '_regular'])) {
                $regular_price = wc_format_decimal($_POST[$field_id . '_regular']);
                update_post_meta($post_id, $field_id . '_regular', $regular_price);
            }
            
            // Save sale price
            if (isset($_POST[$field_id . '_sale'])) {
                $sale_price = wc_format_decimal($_POST[$field_id . '_sale']);
                update_post_meta($post_id, $field_id . '_sale', $sale_price);
            }
        }
    }
    
    /**
     * Add custom pricing fields for product variations
     */
    public function add_variation_fields($loop, $variation_data, $variation) {
        // Only show fields for additional (non-default) currencies.
        $enabled_currencies = CMC_Currency_Manager::get_additional_currencies();

        if (empty($enabled_currencies)) {
            return;
        }
        
        echo '<div class="cmc_variation_prices">';
        echo '<h4 style="margin: 12px 0 8px 0; padding: 8px 12px; background: #f0f0f0; border-left: 3px solid #2271b1;">' 
             . esc_html__('Multi-Currency Pricing', 'custom-multi-currency') . '</h4>';
        
        echo '<div class="cmc-accordion-wrapper" style="margin-top: 0;">';
        
        foreach ($enabled_currencies as $index => $currency) {
            $field_id = '_cmc_price_' . strtolower($currency);
            $regular_price = get_post_meta($variation->ID, $field_id . '_regular', true);
            $sale_price = get_post_meta($variation->ID, $field_id . '_sale', true);
            
            $is_open = $index === 0 ? 'open' : '';
            
            ?>
            <div class="cmc-currency-accordion <?php echo esc_attr($is_open); ?>">
                <div class="cmc-accordion-header">
                    <h4>
                        <span class="cmc-currency-name">
                            <?php echo esc_html($currency); ?> - <?php echo esc_html(CMC_Currency_Manager::get_currency_symbol($currency)); ?>
                            <?php if ($regular_price || $sale_price) : ?>
                                <span class="cmc-price-preview">
                                    <?php 
                                    if ($sale_price) {
                                        echo '<del>' . esc_html(CMC_Currency_Manager::get_currency_symbol($currency) . $regular_price) . '</del> ';
                                        echo esc_html(CMC_Currency_Manager::get_currency_symbol($currency) . $sale_price);
                                    } elseif ($regular_price) {
                                        echo esc_html(CMC_Currency_Manager::get_currency_symbol($currency) . $regular_price);
                                    }
                                    ?>
                                </span>
                            <?php endif; ?>
                        </span>
                        <span class="cmc-toggle-indicator"></span>
                    </h4>
                </div>
                <div class="cmc-accordion-content">
                    <?php
                    woocommerce_wp_text_input(array(
                        'id'                => $field_id . '_regular[' . $loop . ']',
                        'name'              => $field_id . '_regular[' . $loop . ']',
                        'label'             => sprintf(__('Regular Price (%s)', 'custom-multi-currency'), $currency),
                        'placeholder'       => wc_format_localized_price(0),
                        'value'             => $regular_price,
                        'type'              => 'text',
                        'data_type'         => 'price',
                        'wrapper_class'     => 'form-row form-row-first',
                        'desc_tip'          => true,
                        'description'       => sprintf(__('Enter the regular price in %s', 'custom-multi-currency'), $currency),
                        'custom_attributes' => array('data-cmc-field' => 'regular'),
                    ));

                    woocommerce_wp_text_input(array(
                        'id'                => $field_id . '_sale[' . $loop . ']',
                        'name'              => $field_id . '_sale[' . $loop . ']',
                        'label'             => sprintf(__('Sale Price (%s)', 'custom-multi-currency'), $currency),
                        'placeholder'       => wc_format_localized_price(0),
                        'value'             => $sale_price,
                        'type'              => 'text',
                        'data_type'         => 'price',
                        'wrapper_class'     => 'form-row form-row-last',
                        'desc_tip'          => true,
                        'description'       => sprintf(__('Enter the sale price in %s', 'custom-multi-currency'), $currency),
                        'custom_attributes' => array('data-cmc-field' => 'sale'),
                    ));
                    ?>
                    <p class="cmc-price-error" style="display:none;">
                        <?php esc_html_e('Sale price must be lower than the regular price.', 'custom-multi-currency'); ?>
                    </p>
                </div>
            </div>
            <?php
        }
        
        echo '</div>'; // .cmc-accordion-wrapper
        echo '</div>'; // .cmc_variation_prices
    }
    
    /**
     * Save custom pricing fields for variations
     */
    public function save_variation_fields($variation_id, $loop) {
        $enabled_currencies = CMC_Currency_Manager::get_additional_currencies();
        
        foreach ($enabled_currencies as $currency) {
            $field_id = '_cmc_price_' . strtolower($currency);
            
            // Save regular price
            if (isset($_POST[$field_id . '_regular'][$loop])) {
                $regular_price = wc_format_decimal($_POST[$field_id . '_regular'][$loop]);
                update_post_meta($variation_id, $field_id . '_regular', $regular_price);
            }
            
            // Save sale price
            if (isset($_POST[$field_id . '_sale'][$loop])) {
                $sale_price = wc_format_decimal($_POST[$field_id . '_sale'][$loop]);
                update_post_meta($variation_id, $field_id . '_sale', $sale_price);
            }
        }
    }
    
    /**
     * Get product price for specific currency
     */
    public static function get_product_price($product_id, $currency, $type = 'regular') {
        $field_id = '_cmc_price_' . strtolower($currency) . '_' . $type;
        $price = get_post_meta($product_id, $field_id, true);
        
        return $price !== '' ? $price : null;
    }
}

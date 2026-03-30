<?php
/**
 * Frontend Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CMC_Frontend {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Never apply frontend currency overrides in the admin — doing so causes the
        // WC product edit page to show the wrong currency symbol/prices if a frontend
        // cookie is still set.
        if (is_admin()) {
            return;
        }

        $enabled_currencies = CMC_Currency_Manager::get_enabled_currencies();

        // Only apply filters if more than one currency is enabled
        if (count($enabled_currencies) > 1) {
            // Modify product prices based on selected currency
            add_filter('woocommerce_product_get_price', array($this, 'get_custom_price'), 10, 2);
            add_filter('woocommerce_product_get_regular_price', array($this, 'get_custom_regular_price'), 10, 2);
            add_filter('woocommerce_product_get_sale_price', array($this, 'get_custom_sale_price'), 10, 2);

            // For variations
            add_filter('woocommerce_product_variation_get_price', array($this, 'get_custom_price'), 10, 2);
            add_filter('woocommerce_product_variation_get_regular_price', array($this, 'get_custom_regular_price'), 10, 2);
            add_filter('woocommerce_product_variation_get_sale_price', array($this, 'get_custom_sale_price'), 10, 2);

            // Fix the variable product price range (e.g. "$35.99 – $39.99").
            // woocommerce_variation_prices directly overrides the prices array used
            // for range display, and the hash filter busts the per-currency cache.
            add_filter('woocommerce_variation_prices', array($this, 'filter_variation_prices'), 10, 3);
            add_filter('woocommerce_get_variation_prices_hash', array($this, 'variation_prices_hash'), 10, 3);

            // Only override currency symbol/code on the frontend
            add_filter('woocommerce_currency_symbol', array($this, 'get_currency_symbol'), 10, 2);
            add_filter('woocommerce_currency', array($this, 'get_currency'));
        }
    }
    
    /**
     * Get custom price for product
     */
    public function get_custom_price($price, $product) {
        $active_currency = CMC_Currency_Manager::get_active_currency();
        
        // Get custom sale price first
        $sale_price = CMC_Product_Fields::get_product_price($product->get_id(), $active_currency, 'sale');
        if ($sale_price !== null && $sale_price !== '') {
            return $sale_price;
        }
        
        // Get custom regular price
        $regular_price = CMC_Product_Fields::get_product_price($product->get_id(), $active_currency, 'regular');
        if ($regular_price !== null && $regular_price !== '') {
            return $regular_price;
        }
        
        // Return original price if no custom price is set
        return $price;
    }
    
    /**
     * Get custom regular price for product
     */
    public function get_custom_regular_price($price, $product) {
        $active_currency = CMC_Currency_Manager::get_active_currency();
        
        $regular_price = CMC_Product_Fields::get_product_price($product->get_id(), $active_currency, 'regular');
        if ($regular_price !== null && $regular_price !== '') {
            return $regular_price;
        }
        
        return $price;
    }
    
    /**
     * Get custom sale price for product
     */
    public function get_custom_sale_price($price, $product) {
        $active_currency = CMC_Currency_Manager::get_active_currency();
        
        $sale_price = CMC_Product_Fields::get_product_price($product->get_id(), $active_currency, 'sale');
        if ($sale_price !== null && $sale_price !== '') {
            return $sale_price;
        }
        
        // Return empty if no sale price for this currency
        return '';
    }
    
    /**
     * Replace prices in WooCommerce's variation prices array with CMC custom prices.
     * This fixes the price range display (e.g. "$35.99 – $39.99") on variable products.
     * The array is keyed by variation ID for price, regular_price, and sale_price.
     */
    public function filter_variation_prices($prices_array, $product, $for_display) {
        $active_currency = CMC_Currency_Manager::get_active_currency();

        // get_woocommerce_currency() runs through our own woocommerce_currency filter
        // and returns the active currency — so read the stored option directly.
        $base_currency = get_option('woocommerce_currency');

        // Base currency already has correct prices — nothing to override
        if ($active_currency === $base_currency) {
            return $prices_array;
        }

        foreach (array_keys($prices_array['price']) as $variation_id) {
            $regular_price = CMC_Product_Fields::get_product_price($variation_id, $active_currency, 'regular');
            $sale_price    = CMC_Product_Fields::get_product_price($variation_id, $active_currency, 'sale');

            if ($regular_price !== null && $regular_price !== '') {
                $prices_array['regular_price'][$variation_id] = $regular_price;

                if ($sale_price !== null && $sale_price !== '') {
                    $prices_array['sale_price'][$variation_id] = $sale_price;
                    $prices_array['price'][$variation_id]      = $sale_price;
                } else {
                    $prices_array['sale_price'][$variation_id] = '';
                    $prices_array['price'][$variation_id]      = $regular_price;
                }
            }
        }

        return $prices_array;
    }

    /**
     * Include active currency in the variation prices cache hash so each
     * currency gets its own cached price range.
     */
    public function variation_prices_hash($hash, $product, $for_display) {
        $hash[] = CMC_Currency_Manager::get_active_currency();
        return $hash;
    }

    /**
     * Get currency symbol based on active currency
     */
    public function get_currency_symbol($currency_symbol, $currency) {
        $active_currency = CMC_Currency_Manager::get_active_currency();
        return CMC_Currency_Manager::get_currency_symbol($active_currency);
    }
    
    /**
     * Get active currency
     */
    public function get_currency($currency) {
        return CMC_Currency_Manager::get_active_currency();
    }
}

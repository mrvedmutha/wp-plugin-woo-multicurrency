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

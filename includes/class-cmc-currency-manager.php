<?php
/**
 * Currency Manager Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CMC_Currency_Manager {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Handle currency switching
        add_action('init', array($this, 'handle_currency_switch'));
    }
    
    /**
     * Handle currency switching via URL parameter or cookie
     */
    public function handle_currency_switch() {
        if (isset($_GET['currency'])) {
            $currency = sanitize_text_field($_GET['currency']);
            $enabled_currencies = self::get_enabled_currencies(); // Use the method that includes default currency
            
            // Only allow switching to enabled currencies
            if (in_array($currency, $enabled_currencies)) {
                // Store in cookie (lasts 30 days)
                setcookie('cmc_selected_currency', $currency, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
                $_COOKIE['cmc_selected_currency'] = $currency;
                
                // Also store in WooCommerce session if available
                if (function_exists('WC') && WC()->session) {
                    WC()->session->set('cmc_selected_currency', $currency);
                }
            }
        }
    }
    
    /**
     * Get current selected currency (from WooCommerce session or cookie)
     */
    public static function get_active_currency() {
        // First try WooCommerce session
        if (function_exists('WC') && WC()->session) {
            $session_currency = WC()->session->get('cmc_selected_currency');
            if ($session_currency) {
                return $session_currency;
            }
        }
        
        // Then try cookie
        if (isset($_COOKIE['cmc_selected_currency'])) {
            return sanitize_text_field($_COOKIE['cmc_selected_currency']);
        }
        
        // Fallback to WooCommerce default
        return get_option('woocommerce_currency', 'INR');
    }
    
    /**
     * Get enabled currencies (always includes WooCommerce default currency)
     */
    public static function get_enabled_currencies() {
        $enabled = get_option('cmc_enabled_currencies', array());
        $default_currency = get_option('woocommerce_currency', 'USD');

        // Always include the default WooCommerce currency
        if (!in_array($default_currency, $enabled)) {
            array_unshift($enabled, $default_currency);
        }

        return $enabled;
    }

    /**
     * Get only the additional (non-default) currencies configured by the user.
     * Used in admin product fields — the base currency already has native WC price fields.
     */
    public static function get_additional_currencies() {
        $enabled = get_option('cmc_enabled_currencies', array());
        $default_currency = get_option('woocommerce_currency', 'USD');

        return array_values(array_filter($enabled, function($c) use ($default_currency) {
            return $c !== $default_currency;
        }));
    }
    
    /**
     * Get all available currencies
     */
    private function get_all_currencies() {
        return array(
            'USD' => 'US Dollar',
            'EUR' => 'Euro',
            'GBP' => 'British Pound',
            'INR' => 'Indian Rupee',
            'AUD' => 'Australian Dollar',
            'CAD' => 'Canadian Dollar',
            'JPY' => 'Japanese Yen',
            'CNY' => 'Chinese Yuan',
            'AED' => 'UAE Dirham',
            'SGD' => 'Singapore Dollar',
            'MYR' => 'Malaysian Ringgit',
            'THB' => 'Thai Baht',
            'NZD' => 'New Zealand Dollar',
            'CHF' => 'Swiss Franc',
            'SEK' => 'Swedish Krona',
            'NOK' => 'Norwegian Krone',
            'DKK' => 'Danish Krone',
            'PLN' => 'Polish Zloty',
            'ZAR' => 'South African Rand',
            'BRL' => 'Brazilian Real',
            'MXN' => 'Mexican Peso',
            'RUB' => 'Russian Ruble',
            'KRW' => 'South Korean Won',
            'TRY' => 'Turkish Lira',
            'HKD' => 'Hong Kong Dollar',
        );
    }
    
    /**
     * Get currency symbol
     */
    public static function get_currency_symbol($currency = '') {
        if (empty($currency)) {
            $currency = self::get_active_currency();
        }
        
        $symbols = array(
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'INR' => '₹',
            'AUD' => 'A$',
            'CAD' => 'C$',
            'JPY' => '¥',
            'CNY' => '¥',
            'AED' => 'د.إ',
            'SGD' => 'S$',
            'MYR' => 'RM',
            'THB' => '฿',
            'NZD' => 'NZ$',
            'CHF' => 'CHF',
            'SEK' => 'kr',
            'NOK' => 'kr',
            'DKK' => 'kr',
            'PLN' => 'zł',
            'ZAR' => 'R',
            'BRL' => 'R$',
            'MXN' => 'Mex$',
            'RUB' => '₽',
            'KRW' => '₩',
            'TRY' => '₺',
            'HKD' => 'HK$',
        );
        
        return isset($symbols[$currency]) ? $symbols[$currency] : $currency;
    }
}

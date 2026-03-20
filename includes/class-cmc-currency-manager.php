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
        // Use 'wp' hook — WC session is fully initialized by then (unlike 'init')
        add_action('wp', array($this, 'handle_currency_switch'));
    }

    /**
     * Handle currency switching.
     *
     * Priority order:
     *   1. Explicit ?currency= URL param  — manual override, highest priority
     *   2. Existing cookie / session      — returning visitor already has a preference
     *   3. Geolocation auto-detect        — first-time visitor, no preference stored
     *   4. Admin-configured fallback      — country not mapped to any enabled currency
     *   5. WooCommerce base currency      — absolute last resort
     */
    public function handle_currency_switch() {
        $enabled_currencies = self::get_enabled_currencies();

        // 1. Manual switch via URL param
        if (isset($_GET['currency'])) {
            $currency = sanitize_text_field($_GET['currency']);
            if (in_array($currency, $enabled_currencies)) {
                self::persist_currency($currency);
            }
            return;
        }

        // 2. Already have a stored preference — nothing to do
        if (isset($_COOKIE['cmc_selected_currency'])) {
            return;
        }
        if (function_exists('WC') && WC()->session && WC()->session->get('cmc_selected_currency')) {
            return;
        }

        // 3. First-time visitor: try geolocation
        if (get_option('cmc_geolocation_enabled', 'yes') === 'yes') {
            $detected = self::detect_currency_by_location();

            if ($detected && in_array($detected, $enabled_currencies)) {
                self::persist_currency($detected);
                return;
            }
        }

        // 4. Country not mapped or geo disabled — use admin-configured fallback
        $fallback = self::get_fallback_currency();
        if ($fallback && in_array($fallback, $enabled_currencies)) {
            self::persist_currency($fallback);
        }
    }

    /**
     * Detect the best matching currency from the visitor's IP via
     * WooCommerce's built-in geolocation.
     *
     * @return string|null  Currency code, or null if detection failed.
     */
    public static function detect_currency_by_location() {
        if (!class_exists('WC_Geolocation')) {
            return null;
        }

        $ip = WC_Geolocation::get_ip_address();

        // Local/private IPs can never be geolocated — skip silently
        if (self::is_private_ip($ip)) {
            return null;
        }

        // api_fallback=true so it works even without the MaxMind local database
        $geo     = WC_Geolocation::geolocate_ip('', false, true);
        $country = !empty($geo['country']) ? strtoupper($geo['country']) : '';

        if (empty($country)) {
            return null;
        }

        $map = self::get_country_currency_map();

        return isset($map[$country]) ? $map[$country] : null;
    }

    /**
     * Check if an IP is localhost or a private/reserved range.
     */
    private static function is_private_ip($ip) {
        if (empty($ip)) {
            return true;
        }
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    }

    /**
     * Persist the selected currency in both cookie and WC session.
     */
    private static function persist_currency($currency) {
        setcookie('cmc_selected_currency', $currency, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
        $_COOKIE['cmc_selected_currency'] = $currency;

        if (function_exists('WC') && WC()->session) {
            WC()->session->set('cmc_selected_currency', $currency);
        }
    }

    /**
     * Get the currently active currency for this request.
     */
    public static function get_active_currency() {
        if (function_exists('WC') && WC()->session) {
            $session_currency = WC()->session->get('cmc_selected_currency');
            if ($session_currency) {
                return $session_currency;
            }
        }

        if (isset($_COOKIE['cmc_selected_currency'])) {
            return sanitize_text_field($_COOKIE['cmc_selected_currency']);
        }

        return get_option('woocommerce_currency', 'USD');
    }

    /**
     * Get enabled currencies (always includes WooCommerce default currency).
     */
    public static function get_enabled_currencies() {
        $enabled          = get_option('cmc_enabled_currencies', array());
        $default_currency = get_option('woocommerce_currency', 'USD');

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
        $enabled          = get_option('cmc_enabled_currencies', array());
        $default_currency = get_option('woocommerce_currency', 'USD');

        return array_values(array_filter($enabled, function ($c) use ($default_currency) {
            return $c !== $default_currency;
        }));
    }

    /**
     * Get the admin-configured fallback currency for unrecognised regions.
     * Defaults to the WooCommerce base currency if not explicitly set.
     */
    public static function get_fallback_currency() {
        $fallback = get_option('cmc_fallback_currency', '');
        if (empty($fallback)) {
            return get_option('woocommerce_currency', 'USD');
        }
        return $fallback;
    }

    /**
     * ISO country code → currency code map.
     * Countries not listed here fall through to the admin-configured fallback currency.
     */
    public static function get_country_currency_map() {
        return array(
            'IN' => 'INR',
            // USD
            'US' => 'USD', 'EC' => 'USD', 'SV' => 'USD', 'PA' => 'USD',
            'PR' => 'USD', 'GU' => 'USD', 'VI' => 'USD',
            // EUR
            'DE' => 'EUR', 'FR' => 'EUR', 'IT' => 'EUR', 'ES' => 'EUR',
            'NL' => 'EUR', 'BE' => 'EUR', 'AT' => 'EUR', 'PT' => 'EUR',
            'FI' => 'EUR', 'IE' => 'EUR', 'GR' => 'EUR', 'SK' => 'EUR',
            'SI' => 'EUR', 'EE' => 'EUR', 'LV' => 'EUR', 'LT' => 'EUR',
            'LU' => 'EUR', 'MT' => 'EUR', 'CY' => 'EUR',
            // GBP
            'GB' => 'GBP',
            // AUD
            'AU' => 'AUD',
            // CAD
            'CA' => 'CAD',
            // JPY
            'JP' => 'JPY',
            // CNY
            'CN' => 'CNY',
            // AED
            'AE' => 'AED',
            // SGD
            'SG' => 'SGD',
            // MYR
            'MY' => 'MYR',
            // THB
            'TH' => 'THB',
            // NZD
            'NZ' => 'NZD',
            // CHF
            'CH' => 'CHF', 'LI' => 'CHF',
            // SEK
            'SE' => 'SEK',
            // NOK
            'NO' => 'NOK',
            // DKK
            'DK' => 'DKK',
            // PLN
            'PL' => 'PLN',
            // ZAR
            'ZA' => 'ZAR',
            // BRL
            'BR' => 'BRL',
            // MXN
            'MX' => 'MXN',
            // RUB
            'RU' => 'RUB',
            // KRW
            'KR' => 'KRW',
            // TRY
            'TR' => 'TRY',
            // HKD
            'HK' => 'HKD',
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

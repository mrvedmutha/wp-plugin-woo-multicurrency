<?php
/**
 * Admin Settings Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CMC_Admin_Settings {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Add settings page
        add_action('admin_menu', array($this, 'add_settings_page'));
        
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Enqueue admin scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Add settings page to WooCommerce menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'woocommerce',
            __('Multi-Currency Settings', 'custom-multi-currency'),
            __('Multi-Currency', 'custom-multi-currency'),
            'manage_woocommerce',
            'cmc-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register plugin settings
     */
    public function register_settings() {
        register_setting('cmc_settings_group', 'cmc_enabled_currencies');
        register_setting('cmc_settings_group', 'cmc_auto_display_location');
        register_setting('cmc_settings_group', 'cmc_geolocation_enabled');
        register_setting('cmc_settings_group', 'cmc_fallback_currency');
    }
    
    /**
     * Enqueue admin scripts
     */
    public function enqueue_admin_scripts($hook) {
        if ('woocommerce_page_cmc-settings' !== $hook) {
            return;
        }
        
        wp_enqueue_style('cmc-admin-css', CMC_PLUGIN_URL . 'assets/css/admin.css', array(), CMC_VERSION);
        wp_enqueue_script('cmc-admin-js', CMC_PLUGIN_URL . 'assets/js/admin.js', array('jquery'), CMC_VERSION, true);
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_woocommerce')) {
            return;
        }
        
        // Save settings
        if (isset($_POST['cmc_save_settings']) && check_admin_referer('cmc_settings_action', 'cmc_settings_nonce')) {
            $this->save_settings();
        }
        
        // Get current settings
        $enabled_currencies    = get_option('cmc_enabled_currencies', array());
        $auto_display_location = get_option('cmc_auto_display_location', 'none');
        $geolocation_enabled   = get_option('cmc_geolocation_enabled', 'yes');
        $fallback_currency     = get_option('cmc_fallback_currency', '');
        
        // Available currencies
        $available_currencies = $this->get_available_currencies();
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Multi-Currency Settings', 'custom-multi-currency'); ?></h1>
            
            <div class="notice notice-info">
                <p><strong><?php esc_html_e('How it works:', 'custom-multi-currency'); ?></strong></p>
                <ul style="list-style: disc; padding-left: 20px;">
                    <li><?php esc_html_e('Your WooCommerce base currency remains unchanged in WooCommerce > Settings > General.', 'custom-multi-currency'); ?></li>
                    <li><?php esc_html_e('Your default WooCommerce currency is always available - select additional currencies below.', 'custom-multi-currency'); ?></li>
                    <li><?php esc_html_e('The currency switcher will automatically show your default currency plus any selected currencies.', 'custom-multi-currency'); ?></li>
                    <li><?php esc_html_e('Set custom prices for each currency when editing products.', 'custom-multi-currency'); ?></li>
                </ul>
            </div>
            
            <form method="post" action="">
                <?php wp_nonce_field('cmc_settings_action', 'cmc_settings_nonce'); ?>
                
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label><?php esc_html_e('Enable Currencies', 'custom-multi-currency'); ?></label>
                            </th>
                            <td>
                                <fieldset>
                                    <legend class="screen-reader-text">
                                        <span><?php esc_html_e('Select currencies to enable', 'custom-multi-currency'); ?></span>
                                    </legend>
                                    
                                    <?php foreach ($available_currencies as $code => $name) : ?>
                                        <label style="display: block; margin-bottom: 8px;">
                                            <input 
                                                type="checkbox" 
                                                name="cmc_enabled_currencies[]" 
                                                value="<?php echo esc_attr($code); ?>"
                                                <?php checked(in_array($code, $enabled_currencies)); ?>
                                            />
                                            <strong><?php echo esc_html($code); ?></strong> - <?php echo esc_html($name); ?>
                                        </label>
                                    <?php endforeach; ?>
                                    
                                    <p class="description">
                                        <?php 
                                        $default_currency = get_option('woocommerce_currency', 'USD');
                                        printf(
                                            esc_html__('Select additional currencies for your store. Your WooCommerce default currency (%s) is always included automatically. You can set custom prices for each currency.', 'custom-multi-currency'),
                                            '<strong>' . esc_html($default_currency) . '</strong>'
                                        );
                                        ?>
                                    </p>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="cmc_auto_display_location"><?php esc_html_e('Auto Display Currency Switcher', 'custom-multi-currency'); ?></label>
                            </th>
                            <td>
                                <select name="cmc_auto_display_location" id="cmc_auto_display_location">
                                    <option value="none" <?php selected($auto_display_location, 'none'); ?>><?php esc_html_e('None - Use Widget or Shortcode', 'custom-multi-currency'); ?></option>
                                    <option value="before_shop_loop" <?php selected($auto_display_location, 'before_shop_loop'); ?>><?php esc_html_e('Before Shop Page Products', 'custom-multi-currency'); ?></option>
                                    <option value="after_shop_loop" <?php selected($auto_display_location, 'after_shop_loop'); ?>><?php esc_html_e('After Shop Page Products', 'custom-multi-currency'); ?></option>
                                    <option value="before_single_product" <?php selected($auto_display_location, 'before_single_product'); ?>><?php esc_html_e('Before Single Product', 'custom-multi-currency'); ?></option>
                                    <option value="after_product_title" <?php selected($auto_display_location, 'after_product_title'); ?>><?php esc_html_e('After Product Title', 'custom-multi-currency'); ?></option>
                                    <option value="before_add_to_cart" <?php selected($auto_display_location, 'before_add_to_cart'); ?>><?php esc_html_e('Before Add to Cart Button', 'custom-multi-currency'); ?></option>
                                    <option value="header" <?php selected($auto_display_location, 'header'); ?>><?php esc_html_e('Site Header (wp_head)', 'custom-multi-currency'); ?></option>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Automatically display the currency switcher dropdown at the selected location. Leave as "None" to manually add it using the widget or [currency_switcher] shortcode.', 'custom-multi-currency'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="cmc_geolocation_enabled"><?php esc_html_e('Auto-Detect Currency by Location', 'custom-multi-currency'); ?></label>
                            </th>
                            <td>
                                <label>
                                    <input type="checkbox" name="cmc_geolocation_enabled" id="cmc_geolocation_enabled" value="yes" <?php checked($geolocation_enabled, 'yes'); ?> />
                                    <?php esc_html_e('Automatically set currency based on the visitor\'s country on their first visit', 'custom-multi-currency'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('Uses WooCommerce\'s built-in geolocation. Make sure "Default customer location" is set to "Geolocate" in WooCommerce → Settings → Advanced.', 'custom-multi-currency'); ?>
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row">
                                <label for="cmc_fallback_currency"><?php esc_html_e('Fallback Currency', 'custom-multi-currency'); ?></label>
                            </th>
                            <td>
                                <?php
                                $default_currency    = get_option('woocommerce_currency', 'USD');
                                $all_enabled         = CMC_Currency_Manager::get_enabled_currencies();
                                $available_currencies = $this->get_available_currencies();
                                ?>
                                <select name="cmc_fallback_currency" id="cmc_fallback_currency">
                                    <option value="" <?php selected($fallback_currency, ''); ?>>
                                        <?php printf(esc_html__('Store Default (%s)', 'custom-multi-currency'), esc_html($default_currency)); ?>
                                    </option>
                                    <?php foreach ($all_enabled as $code) : ?>
                                        <option value="<?php echo esc_attr($code); ?>" <?php selected($fallback_currency, $code); ?>>
                                            <?php echo esc_html($code); ?> - <?php echo esc_html($available_currencies[$code] ?? $code); ?>
                                            <?php if ($code === $default_currency) : ?>
                                                <?php esc_html_e('(base)', 'custom-multi-currency'); ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description">
                                    <?php esc_html_e('Currency shown to visitors from countries not mapped to any of your enabled currencies (e.g. traffic from Africa, Latin America). Can be set to any enabled currency — does not have to be the store base.', 'custom-multi-currency'); ?>
                                </p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                
                <?php submit_button(__('Save Settings', 'custom-multi-currency'), 'primary', 'cmc_save_settings'); ?>
            </form>
        </div>
        <?php
    }
    
    /**
     * Save settings
     */
    private function save_settings() {
        $enabled_currencies = isset($_POST['cmc_enabled_currencies'])
            ? array_map('sanitize_text_field', $_POST['cmc_enabled_currencies'])
            : array();

        $auto_display_location = isset($_POST['cmc_auto_display_location'])
            ? sanitize_text_field($_POST['cmc_auto_display_location'])
            : 'none';

        $geolocation_enabled = isset($_POST['cmc_geolocation_enabled']) ? 'yes' : 'no';

        $fallback_currency = isset($_POST['cmc_fallback_currency'])
            ? sanitize_text_field($_POST['cmc_fallback_currency'])
            : '';

        update_option('cmc_enabled_currencies', $enabled_currencies);
        update_option('cmc_auto_display_location', $auto_display_location);
        update_option('cmc_geolocation_enabled', $geolocation_enabled);
        update_option('cmc_fallback_currency', $fallback_currency);
        
        // Show success message
        add_settings_error(
            'cmc_messages',
            'cmc_message',
            __('Settings saved successfully.', 'custom-multi-currency'),
            'updated'
        );
        
        settings_errors('cmc_messages');
    }
    
    /**
     * Get available currencies
     */
    private function get_available_currencies() {
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
}

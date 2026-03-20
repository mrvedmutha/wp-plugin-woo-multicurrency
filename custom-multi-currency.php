<?php
/**
 * Plugin Name: Custom Multi-Currency for WooCommerce
 * Plugin URI: https://yourdomain.com
 * Description: A custom multi-currency plugin that allows manual price setting for each currency
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourdomain.com
 * Text Domain: custom-multi-currency
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * WC requires at least: 5.0
 * WC tested up to: 8.0
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('CMC_VERSION', '1.0.0');
define('CMC_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('CMC_PLUGIN_URL', plugin_dir_url(__FILE__));
define('CMC_PLUGIN_FILE', __FILE__);

/**
 * Main Plugin Class
 */
class Custom_Multi_Currency {
    
    /**
     * Plugin instance
     */
    private static $instance = null;
    
    /**
     * Get plugin instance
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
        // Declare WooCommerce compatibility
        add_action('before_woocommerce_init', array($this, 'declare_compatibility'));
        
        // Check if WooCommerce is active
        add_action('plugins_loaded', array($this, 'init'));
    }
    
    /**
     * Declare WooCommerce HPOS compatibility
     */
    public function declare_compatibility() {
        if (class_exists('\Automattic\WooCommerce\Utilities\FeaturesUtil')) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility('custom_order_tables', CMC_PLUGIN_FILE, true);
        }
    }
    
    /**
     * Initialize plugin
     */
    public function init() {
        // Check if WooCommerce is active
        if (!class_exists('WooCommerce')) {
            add_action('admin_notices', array($this, 'woocommerce_missing_notice'));
            return;
        }
        
        // Load plugin files
        $this->load_dependencies();
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Load plugin dependencies
     */
    private function load_dependencies() {
        require_once CMC_PLUGIN_DIR . 'includes/class-cmc-admin-settings.php';
        require_once CMC_PLUGIN_DIR . 'includes/class-cmc-currency-manager.php';
        require_once CMC_PLUGIN_DIR . 'includes/class-cmc-product-fields.php';
        require_once CMC_PLUGIN_DIR . 'includes/class-cmc-frontend.php';
        require_once CMC_PLUGIN_DIR . 'includes/class-cmc-currency-switcher.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Initialize admin settings
        if (is_admin()) {
            CMC_Admin_Settings::get_instance();
        }
        
        // Initialize currency manager
        CMC_Currency_Manager::get_instance();
        
        // Initialize product fields
        CMC_Product_Fields::get_instance();
        
        // Initialize frontend
        CMC_Frontend::get_instance();
        
        // Initialize currency switcher
        CMC_Currency_Switcher::get_instance();
        
        // Activation hook
        register_activation_hook(CMC_PLUGIN_FILE, array($this, 'activate'));
    }
    
    /**
     * Plugin activation
     */
    public function activate() {
        // Get the current WooCommerce currency as default
        $default_currency = get_option('woocommerce_currency', 'INR');
        
        // Set default options (only if not already set)
        if (!get_option('cmc_enabled_currencies')) {
            update_option('cmc_enabled_currencies', array($default_currency));
        }
        
        // Remove old active_currency option (we use session now)
        delete_option('cmc_active_currency');
    }
    
    /**
     * WooCommerce missing notice
     */
    public function woocommerce_missing_notice() {
        ?>
        <div class="error">
            <p><?php esc_html_e('Custom Multi-Currency requires WooCommerce to be installed and activated.', 'custom-multi-currency'); ?></p>
        </div>
        <?php
    }
}

// Initialize plugin
Custom_Multi_Currency::get_instance();

<?php
/**
 * Currency Switcher Class
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

class CMC_Currency_Switcher {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Register shortcode
        add_shortcode('currency_switcher', array($this, 'currency_switcher_shortcode'));
        
        // Register widget
        add_action('widgets_init', array($this, 'register_widget'));
        
        // Enqueue frontend scripts
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        
        // Auto-display currency switcher based on settings
        add_action('wp', array($this, 'setup_auto_display'));
    }
    
    /**
     * Enqueue frontend scripts
     */
    public function enqueue_scripts() {
        wp_enqueue_style('cmc-switcher-css', CMC_PLUGIN_URL . 'assets/css/switcher.css', array(), CMC_VERSION);
        wp_enqueue_script('cmc-switcher-js', CMC_PLUGIN_URL . 'assets/js/switcher.js', array('jquery'), CMC_VERSION, true);
    }
    
    /**
     * Setup auto-display based on admin settings
     */
    public function setup_auto_display() {
        $location = get_option('cmc_auto_display_location', 'none');
        
        if ($location === 'none') {
            return;
        }
        
        switch ($location) {
            case 'before_shop_loop':
                add_action('woocommerce_before_shop_loop', array($this, 'display_auto_switcher'), 15);
                break;
            case 'after_shop_loop':
                add_action('woocommerce_after_shop_loop', array($this, 'display_auto_switcher'), 15);
                break;
            case 'before_single_product':
                add_action('woocommerce_before_single_product', array($this, 'display_auto_switcher'), 15);
                break;
            case 'after_product_title':
                add_action('woocommerce_single_product_summary', array($this, 'display_auto_switcher'), 6);
                break;
            case 'before_add_to_cart':
                add_action('woocommerce_before_add_to_cart_form', array($this, 'display_auto_switcher'), 15);
                break;
            case 'header':
                add_action('wp_head', array($this, 'display_auto_switcher_header'), 99);
                break;
        }
    }
    
    /**
     * Display auto switcher (dropdown by default)
     */
    public function display_auto_switcher() {
        echo $this->render_switcher('dropdown');
    }
    
    /**
     * Display auto switcher in header with wrapper
     */
    public function display_auto_switcher_header() {
        echo '<div style="position: fixed; top: 10px; right: 10px; z-index: 9999; background: white; padding: 10px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">';
        echo $this->render_switcher('dropdown');
        echo '</div>';
    }
    
    /**
     * Currency switcher shortcode
     */
    public function currency_switcher_shortcode($atts) {
        $atts = shortcode_atts(array(
            'type' => 'dropdown', // dropdown or flags
            'show_flag' => 'no',
        ), $atts);
        
        return $this->render_switcher($atts['type'], $atts['show_flag'] === 'yes');
    }
    
    /**
     * Render currency switcher
     */
    public function render_switcher($type = 'dropdown', $show_flag = false) {
        $enabled_currencies = CMC_Currency_Manager::get_enabled_currencies();
        $current_currency = CMC_Currency_Manager::get_active_currency();
        
        // Don't show if only one currency
        if (count($enabled_currencies) <= 1) {
            return '';
        }
        
        $all_currencies = $this->get_all_currencies();
        
        ob_start();
        ?>
        <div class="cmc-currency-switcher cmc-<?php echo esc_attr($type); ?>">
            <?php if ($type === 'dropdown') : ?>
                <form method="get" class="cmc-switcher-form">
                    <?php
                    // Preserve current URL parameters
                    foreach ($_GET as $key => $value) {
                        if ($key !== 'currency') {
                            echo '<input type="hidden" name="' . esc_attr($key) . '" value="' . esc_attr($value) . '" />';
                        }
                    }
                    ?>
                    <select name="currency" class="cmc-currency-select" onchange="this.form.submit()">
                        <?php foreach ($enabled_currencies as $code) : ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($current_currency, $code); ?>>
                                <?php echo esc_html($all_currencies[$code] ?? $code); ?> (<?php echo esc_html(CMC_Currency_Manager::get_currency_symbol($code)); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </form>
            <?php else : ?>
                <div class="cmc-currency-buttons">
                    <?php foreach ($enabled_currencies as $code) : ?>
                        <a href="<?php echo esc_url(add_query_arg('currency', $code)); ?>" 
                           class="cmc-currency-button <?php echo $current_currency === $code ? 'active' : ''; ?>">
                            <?php echo esc_html($code); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Register widget
     */
    public function register_widget() {
        register_widget('CMC_Currency_Switcher_Widget');
    }
    
    /**
     * Get all currencies
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
}

/**
 * Currency Switcher Widget
 */
class CMC_Currency_Switcher_Widget extends WP_Widget {
    
    public function __construct() {
        parent::__construct(
            'cmc_currency_switcher',
            __('Currency Switcher', 'custom-multi-currency'),
            array('description' => __('Allow visitors to switch currencies', 'custom-multi-currency'))
        );
    }
    
    public function widget($args, $instance) {
        echo $args['before_widget'];
        
        if (!empty($instance['title'])) {
            echo $args['before_title'] . apply_filters('widget_title', $instance['title']) . $args['after_title'];
        }
        
        $switcher = CMC_Currency_Switcher::get_instance();
        echo $switcher->render_switcher($instance['type'] ?? 'dropdown');
        
        echo $args['after_widget'];
    }
    
    public function form($instance) {
        $title = !empty($instance['title']) ? $instance['title'] : '';
        $type = !empty($instance['type']) ? $instance['type'] : 'dropdown';
        ?>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">
                <?php esc_html_e('Title:', 'custom-multi-currency'); ?>
            </label>
            <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" 
                   name="<?php echo esc_attr($this->get_field_name('title')); ?>" 
                   type="text" value="<?php echo esc_attr($title); ?>">
        </p>
        <p>
            <label for="<?php echo esc_attr($this->get_field_id('type')); ?>">
                <?php esc_html_e('Display Type:', 'custom-multi-currency'); ?>
            </label>
            <select class="widefat" id="<?php echo esc_attr($this->get_field_id('type')); ?>" 
                    name="<?php echo esc_attr($this->get_field_name('type')); ?>">
                <option value="dropdown" <?php selected($type, 'dropdown'); ?>><?php esc_html_e('Dropdown', 'custom-multi-currency'); ?></option>
                <option value="buttons" <?php selected($type, 'buttons'); ?>><?php esc_html_e('Buttons', 'custom-multi-currency'); ?></option>
            </select>
        </p>
        <?php
    }
    
    public function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = (!empty($new_instance['title'])) ? sanitize_text_field($new_instance['title']) : '';
        $instance['type'] = (!empty($new_instance['type'])) ? sanitize_text_field($new_instance['type']) : 'dropdown';
        return $instance;
    }
}

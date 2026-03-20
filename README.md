# Custom Multi-Currency for WooCommerce

A custom WordPress plugin that enables multi-currency support for WooCommerce with manual price setting for each currency.

## Features

- ✅ Select which currencies you want to enable in your store
- ✅ Set custom prices for each currency (no automatic conversion)
- ✅ Currency selector in WooCommerce General Settings
- ✅ Custom price fields for simple products
- ✅ Custom price fields for product variations
- ✅ Automatic price display based on selected currency
- ✅ Support for 25+ major currencies

## Installation

1. Upload the `custom-multi-currency` folder to `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Make sure WooCommerce is installed and activated

## Usage

### Step 1: Enable Currencies

1. Go to **WooCommerce > Multi-Currency** in your WordPress admin
2. Check the currencies you want to enable in your store
3. Click **Save Settings**

### Step 2: Select Active Currency

1. Go to **WooCommerce > Settings > General**
2. Scroll down to find **Active Currency** dropdown
3. Select the currency you want to display prices in
4. Click **Save changes**

### Step 3: Set Product Prices

#### For Simple Products:

1. Edit any product
2. Go to the **General** tab (Product Data section)
3. Scroll down to find **Multi-Currency Pricing** section
4. Enter custom prices for each enabled currency
5. You can set both Regular Price and Sale Price for each currency
6. If you don't set a price for a currency, the default price will be used

#### For Variable Products:

1. Edit a variable product
2. Go to the **Variations** tab
3. Expand any variation
4. In the pricing section, you'll see **Multi-Currency Pricing**
5. Set Regular and Sale prices for each enabled currency
6. Repeat for all variations

### Step 4: Frontend Display

- Prices will automatically display in the active currency selected in WooCommerce settings
- The currency symbol will also update automatically
- If a product doesn't have a price set for the active currency, it will fall back to the default price

## Supported Currencies

The plugin supports the following currencies:

- USD - US Dollar
- EUR - Euro
- GBP - British Pound
- INR - Indian Rupee
- AUD - Australian Dollar
- CAD - Canadian Dollar
- JPY - Japanese Yen
- CNY - Chinese Yuan
- AED - UAE Dirham
- SGD - Singapore Dollar
- MYR - Malaysian Ringgit
- THB - Thai Baht
- NZD - New Zealand Dollar
- CHF - Swiss Franc
- SEK - Swedish Krona
- NOK - Norwegian Krone
- DKK - Danish Krone
- PLN - Polish Zloty
- ZAR - South African Rand
- BRL - Brazilian Real
- MXN - Mexican Peso
- RUB - Russian Ruble
- KRW - South Korean Won
- TRY - Turkish Lira
- HKD - Hong Kong Dollar

## Example Use Case

If you're selling a product for:

- 2500 INR in India
- 35 USD in the United States

You can set these prices independently without any automatic conversion. When a customer selects USD as the currency, they'll see $35, and when they select INR, they'll see ₹2500.

## Requirements

- WordPress 5.8 or higher
- WooCommerce 5.0 or higher
- PHP 7.4 or higher

## Frequently Asked Questions

**Q: Does this plugin convert prices automatically?**
A: No, this plugin allows you to manually set custom prices for each currency. There is no automatic conversion.

**Q: Can I add more currencies?**
A: Yes, you can modify the `get_available_currencies()` method in the `class-cmc-admin-settings.php` and `class-cmc-currency-manager.php` files to add more currencies.

**Q: What happens if I don't set a price for a specific currency?**
A: If you don't set a custom price for a currency, the product will use its default price.

**Q: Can customers switch currencies on the frontend?**
A: The current version displays prices in the currency selected in WooCommerce settings. To allow customer-side currency switching, you would need to add a currency switcher widget (future enhancement).

## Future Enhancements

- Currency switcher widget for frontend
- GeoIP-based automatic currency selection
- Currency-specific price import/export
- Bulk price setting tool
- Currency conversion rate suggestions (optional)

## Support

For support and feature requests, please contact the plugin author.

## License

This plugin is licensed under GPL v2 or later.

## Changelog

### Version 1.0.0

- Initial release
- Multi-currency selection
- Custom price setting for each currency
- Support for simple and variable products
- Frontend price display

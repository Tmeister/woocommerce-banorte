<?php
/**
 * Plugin Name: WooCommerce Banorte Integration
 * Plugin URI: https://enriquechavez.co
 * Description: Baorte integration for WooCommerce
 * Version:     0.1.0
 * Author:      Enrique Chavez
 * Author URI:  https://enriquechavez.co
 * Text Domain: woocommerce-banorte-integration
 * Domain Path: /languages
 *
 * @package WoocommerceBanorteIntegration
 */

// Validate that WooCommerce is active

if ( ! in_array('woocommerce/woocommerce.php', apply_filters('active_plugins', get_option('active_plugins')))) {
	return;
}

// Useful global constants.
define('WOOCOMMERCE_BANORTE_INTEGRATION_VERSION', '0.1.0');
define('WOOCOMMERCE_BANORTE_INTEGRATION_URL', plugin_dir_url(__FILE__));
define('WOOCOMMERCE_BANORTE_INTEGRATION_PATH', plugin_dir_path(__FILE__));
define('WOOCOMMERCE_BANORTE_INTEGRATION_FILE', plugin_basename(__FILE__));
define('WOOCOMMERCE_BANORTE_INTEGRATION_INC', WOOCOMMERCE_BANORTE_INTEGRATION_PATH . 'includes/');

// Include files.
require_once WOOCOMMERCE_BANORTE_INTEGRATION_INC . 'functions/core.php';

// Activation/Deactivation.
register_activation_hook(__FILE__, '\WoocommerceBanorteIntegration\Core\activate');
register_deactivation_hook(__FILE__, '\WoocommerceBanorteIntegration\Core\deactivate');

// Bootstrap.
WoocommerceBanorteIntegration\Core\setup();

// Require Composer autoloader if it exists.
if (file_exists(WOOCOMMERCE_BANORTE_INTEGRATION_PATH . '/vendor/autoload.php')) {
	require_once WOOCOMMERCE_BANORTE_INTEGRATION_PATH . 'vendor/autoload.php';
}

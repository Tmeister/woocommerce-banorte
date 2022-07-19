<?php
/**
 * Core plugin functionality.
 *
 * @package WoocommerceBanorteIntegration
 */

namespace WoocommerceBanorteIntegration\Core;

use \WP_Error as WP_Error;

/**
 * Default setup routine
 *
 * @return void
 */
function setup()
{
	$n = function ($function) {
		return __NAMESPACE__ . "\\$function";
	};

	add_action('init', $n('i18n'));
	add_action('plugins_loaded', $n('init'));
	add_action('wp_enqueue_scripts', $n('scripts'));
	add_action('wp_enqueue_scripts', $n('styles'));
	add_action('woocommerce_payment_gateways', $n('add_wc_banorter_payment'));
	add_filter('plugin_action_links_' . WOOCOMMERCE_BANORTE_INTEGRATION_FILE, $n('add_plugin_links'));
	add_action('wp', $n('process_banorte_callback'));
	// Hook to allow async or defer on asset loading.
	add_filter('script_loader_tag', $n('script_loader_tag'), 10, 2);
	do_action('woocommerce_banorte_integration_loaded');
}

/**
 * Registers the default textdomain.
 *
 * @return void
 */
function i18n()
{
	$locale = apply_filters('plugin_locale', get_locale(), 'woocommerce-banorte-integration');
	load_textdomain('woocommerce-banorte-integration', WP_LANG_DIR . '/woocommerce-banorte-integration/woocommerce-banorte-integration-' . $locale . '.mo');
	load_plugin_textdomain('woocommerce-banorte-integration', false, plugin_basename(WOOCOMMERCE_BANORTE_INTEGRATION_PATH) . '/languages/');
}

/**
 * Initializes the plugin and fires an action other plugins can hook into.
 *
 * @return void
 */
function init()
{
	do_action('woocommerce_banorte_integration_init');
}

/**
 * @param $gateways
 *
 * @return mixed
 */
function add_wc_banorter_payment($gateways)
{
	require_once WOOCOMMERCE_BANORTE_INTEGRATION_INC . 'classes/Admin/Gateways/WC_Banorte_Gateway.php';

	$gateways[] = 'WC_Banorte_Gateway';

	return $gateways;
}

function add_plugin_links($links)
{
	$plugin_links = [
		'<a href="' . admin_url('admin.php?page=wc-settings&tab=checkout&section=banorte') . '">Configuración</a>'
	];

	return array_merge($plugin_links, $links);
}

function process_banorte_callback()
{
	$logger  = wc_get_logger();
	$context = ['source' => 'banorte-woocommerce'];
	$logger->info(print_r($_REQUEST, true), $context);
	error_log(print_r($_REQUEST, true));

	if ( ! isset($_REQUEST['CONTROL_NUMBER']) && ! isset($_REQUEST['PAYW_RESULT'])) {
		return;
	}

	require_once WOOCOMMERCE_BANORTE_INTEGRATION_INC . 'classes/Admin/Gateways/WC_Banorte_Gateway.php';

	$reference = $_REQUEST['REFERENCE'];
	$auth_code = $_REQUEST['AUTH_CODE'];

	$order_id     = (int)$_REQUEST['CONTROL_NUMBER'];
	$order        = new \WC_Order($order_id);
	$message      = '';
	$messageClass = '';

	$result = $_REQUEST['PAYW_RESULT'];

	if ($order->get_status() !== 'completed' && $order->get_status() !== 'processing') {
		switch ($result) {
			case 'A':
				$order->payment_complete($order_id);
				$message      = 'Pago exitoso';
				$messageClass = 'woocommerce-message';
				$order->add_order_note(sprintf('Pago exitoso, referencia: (%s).', $reference));
				$order->add_order_note(sprintf('Codigo de autorizacion: (%s).', $auth_code));
				break;
			case 'D':
			case 'R':
			case 'T':
			default:
				$message      = 'Pago fallido';
				$messageClass = 'woocommerce-error';
				$order->update_status('failed');
				$order->add_order_note('Pago fallido');
				$wc_banorte_gateway = new \WC_Banorte_Gateway();
				$wc_banorte_gateway->restore_order_stock($order_id);
				break;
		}
	} elseif ($order->get_status() === 'completed') {
		$message      = 'Pago exitoso';
		$messageClass = 'woocommerce-message';
	} elseif ($order->get_status() === 'processing') {
		$message      = 'Pago iniciado';
		$messageClass = 'woocommerce-info';
	}

	$redirect_url = add_query_arg(array('msg' => urlencode($message), 'type' => $messageClass), $order->get_checkout_order_received_url());
	wp_redirect($redirect_url);

}

/**
 * Activate the plugin
 *
 * @return void
 */
function activate()
{
	// First load the init scripts in case any rewrite functionality is being loaded
	init();
	flush_rewrite_rules();
}

/**
 * Deactivate the plugin
 *
 * Uninstall routines should be in uninstall.php
 *
 * @return void
 */
function deactivate()
{

}


/**
 * The list of knows contexts for enqueuing scripts/styles.
 *
 * @return array
 */
function get_enqueue_contexts()
{
	return ['admin', 'frontend', 'shared'];
}

/**
 * Generate an URL to a script, taking into account whether SCRIPT_DEBUG is enabled.
 *
 * @param string $script Script file name (no .js extension)
 * @param string $context Context for the script ('admin', 'frontend', or 'shared')
 *
 * @return string|WP_Error URL
 */
function script_url($script, $context)
{

	if ( ! in_array($context, get_enqueue_contexts(), true)) {
		return new WP_Error('invalid_enqueue_context', 'Invalid $context specified in WoocommerceBanorteIntegration script loader.');
	}

	return WOOCOMMERCE_BANORTE_INTEGRATION_URL . "dist/js/${script}.js";

}

/**
 * Generate an URL to a stylesheet, taking into account whether SCRIPT_DEBUG is enabled.
 *
 * @param string $stylesheet Stylesheet file name (no .css extension)
 * @param string $context Context for the script ('admin', 'frontend', or 'shared')
 *
 * @return string URL
 */
function style_url($stylesheet, $context)
{

	if ( ! in_array($context, get_enqueue_contexts(), true)) {
		return new WP_Error('invalid_enqueue_context', 'Invalid $context specified in WoocommerceBanorteIntegration stylesheet loader.');
	}

	return WOOCOMMERCE_BANORTE_INTEGRATION_URL . "dist/css/${stylesheet}.css";

}

/**
 * Enqueue scripts for front-end.
 *
 * @return void
 */
function scripts()
{

	wp_enqueue_script(
		'woocommerce_banorte_integration_shared',
		script_url('shared', 'shared'),
		[],
		WOOCOMMERCE_BANORTE_INTEGRATION_VERSION,
		true
	);

	wp_enqueue_script(
		'woocommerce_banorte_integration_frontend',
		script_url('frontend', 'frontend'),
		[],
		WOOCOMMERCE_BANORTE_INTEGRATION_VERSION,
		true
	);

}

/**
 * Enqueue scripts for admin.
 *
 * @return void
 */
function admin_scripts()
{

	wp_enqueue_script(
		'woocommerce_banorte_integration_shared',
		script_url('shared', 'shared'),
		[],
		WOOCOMMERCE_BANORTE_INTEGRATION_VERSION,
		true
	);

	wp_enqueue_script(
		'woocommerce_banorte_integration_admin',
		script_url('admin', 'admin'),
		[],
		WOOCOMMERCE_BANORTE_INTEGRATION_VERSION,
		true
	);

}

/**
 * Enqueue styles for front-end.
 *
 * @return void
 */
function styles()
{

	wp_enqueue_style(
		'woocommerce_banorte_integration_shared',
		style_url('shared-style', 'shared'),
		[],
		WOOCOMMERCE_BANORTE_INTEGRATION_VERSION
	);

	if (is_admin()) {
		wp_enqueue_style(
			'woocommerce_banorte_integration_admin',
			style_url('admin-style', 'admin'),
			[],
			WOOCOMMERCE_BANORTE_INTEGRATION_VERSION
		);
	} else {
		wp_enqueue_style(
			'woocommerce_banorte_integration_frontend',
			style_url('style', 'frontend'),
			[],
			WOOCOMMERCE_BANORTE_INTEGRATION_VERSION
		);
	}

}

/**
 * Enqueue styles for admin.
 *
 * @return void
 */
function admin_styles()
{

	wp_enqueue_style(
		'woocommerce_banorte_integration_shared',
		style_url('shared-style', 'shared'),
		[],
		WOOCOMMERCE_BANORTE_INTEGRATION_VERSION
	);

	wp_enqueue_style(
		'woocommerce_banorte_integration_admin',
		style_url('admin-style', 'admin'),
		[],
		WOOCOMMERCE_BANORTE_INTEGRATION_VERSION
	);

}

/**
 * Enqueue editor styles. Filters the comma-delimited list of stylesheets to load in TinyMCE.
 *
 * @param string $stylesheets Comma-delimited list of stylesheets.
 *
 * @return string
 */
function mce_css($stylesheets)
{
	if ( ! empty($stylesheets)) {
		$stylesheets .= ',';
	}

	return $stylesheets . WOOCOMMERCE_BANORTE_INTEGRATION_URL . ((defined('SCRIPT_DEBUG') && SCRIPT_DEBUG) ?
			'assets/css/frontend/editor-style.css' :
			'dist/css/editor-style.min.css');
}

/**
 * Add async/defer attributes to enqueued scripts that have the specified script_execution flag.
 *
 * @link https://core.trac.wordpress.org/ticket/12009
 *
 * @param string $tag The script tag.
 * @param string $handle The script handle.
 *
 * @return string
 */
function script_loader_tag($tag, $handle)
{
	$script_execution = wp_scripts()->get_data($handle, 'script_execution');

	if ( ! $script_execution) {
		return $tag;
	}

	if ('async' !== $script_execution && 'defer' !== $script_execution) {
		return $tag; // _doing_it_wrong()?
	}

	// Abort adding async/defer for scripts that have this script as a dependency. _doing_it_wrong()?
	foreach (wp_scripts()->registered as $script) {
		if (in_array($handle, $script->deps, true)) {
			return $tag;
		}
	}

	// Add the attribute if it hasn't already been added.
	if ( ! preg_match(":\s$script_execution(=|>|\s):", $tag)) {
		$tag = preg_replace(':(?=></script>):', " $script_execution", $tag, 1);
	}

	return $tag;
}

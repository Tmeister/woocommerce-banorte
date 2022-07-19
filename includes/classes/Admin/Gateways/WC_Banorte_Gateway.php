<?php

class WC_Banorte_Gateway extends WC_Payment_Gateway
{
	/**
	 * @var string
	 */
	private $payment_url;

	public function __construct()
	{
		$this->id                 = 'banorte_hosted';
		$this->method_title       = 'Banorte Hosted';
		$this->has_fields         = false;
		$this->method_description = 'Pagos con tarjetas de crédito, débito VISA y Mastercard a través de Banorte.';
		$this->supports           = ['products'];
		$this->title              = $this->get_option('title');
		$this->payment_url        = $this->get_option('banorte_url');

		$this->init_form_fields();
		$this->init_settings();

		add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
		add_action('woocommerce_receipt_' . $this->id, [$this, 'receipt_page']);
		add_filter('woocommerce_thankyou_order_received_text', [$this, 'order_received_message'], 10, 2);
		add_action('woocommerce_available_payment_gateways', [$this, 'disable_payment_if_options_empty'], 20);

	}

	public function init_form_fields()
	{
		$this->form_fields = [
			'enabled'     => array(
				'title'   => 'Activar/Desactivar',
				'type'    => 'checkbox',
				'label'   => 'Activar Banorte',
				'default' => 'no'
			),
			'title'       => [
				'title'       => 'Título',
				'type'        => 'text',
				'description' => 'El nombre de pago que se muestra el usuario en la el proceso de pago.',
				'default'     => 'Banorte WooCommerce.',
				'desc_tip'    => true,
			],
			'description' => [
				'title'       => 'Descripción',
				'type'        => 'textarea',
				'description' => 'La descripción que se muestra el usuario en la el proceso de pago.',
				'default'     => 'Pago con tarjetas de credito o debito VISA y Mastercard.',
				'desc_tip'    => true,
			],
			'merchant_id' => [
				'title'       => 'Numero de Afiliacion',
				'type'        => 'number',
				'description' => 'Número de afiliación asignado por Banorte.',
				'desc_tip'    => true,
				'default'     => '',
			],
			'user'        => [
				'title'       => 'Usuario',
				'type'        => 'text',
				'description' => 'Nombre de usuario asignado por Banorte.',
				'desc_tip'    => true,
				'default'     => '',
			],
			'password'    => [
				'title'       => 'Constraseña',
				'type'        => 'text',
				'description' => 'Contraseña asignada por Banorte.',
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => ''
			],
			'terminal_id' => [
				'title'       => 'Número de Terminal',
				'type'        => 'text',
				'description' => 'Número de terminal asignada por Banorte.',
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => ''
			],
			'environment' => [
				'title'       => 'Ambiente',
				'type'        => 'select',
				'class'       => 'wc-enhanced-select',
				'description' => 'Permite ejecutar pruebas',
				'desc_tip'    => true,
				'default'     => 'AUT',
				'options'     => [
					'PRD' => 'Producción',
					'AUT' => 'Modo de pruebas, siempre autoriza pago',
					'DEC' => 'Modo de pruebas, siempre regresa pago no autorizado',
					'RND' => 'Modo de pruebas, regresa una respuesta random'
				],
			],
			'banorte_url' => [
				'title'       => 'URL de proceso de pago.',
				'type'        => 'url',
				'description' => 'Es la URL asignada por Banorte para enviar la información de pago.',
				'default'     => '',
				'desc_tip'    => true,
				'placeholder' => ''
			],
		];
	}

	public function receipt_page($order_id)
	{
		global $woocommerce;
		$order = new WC_Order($order_id);
		echo $this->generate_banorte_bank_form($order);
	}

	public function generate_banorte_bank_form($order)
	{
		$date_new = strtotime('+2 year', strtotime(date('m/y')));
		$date_new = date('m/y', $date_new);
		?>
		<div id="card-banorte-bank-bb">
			<form id="banorte-bank" action="<?php echo $this->payment_url; ?>" method="POST">
				<input type="hidden" name="MERCHANT_ID" value="<?php echo $this->get_option('merchant_id'); ?>">
				<input type="hidden" name="USER" value="<?php echo $this->get_option('user'); ?>">
				<input type="hidden" name="PASSWORD" value="<?php echo $this->get_option('password'); ?>">
				<input type="hidden" name="MODE" value="<?php echo $this->get_option('environment'); ?>">
				<input type="hidden" name="ENTRY_MODE" value="MANUAL">
				<input type="hidden" name="Mr" value="0">
				<input type="hidden" name="CMD_TRANS" value="AUTH">
				<input type="hidden" name="RESPONSE_URL" value="<?php echo home_url('/'); ?>">
				<input type="hidden" name="CONTROL_NUMBER" value="<?php echo $order->get_id(); ?>"/>
				<input type="hidden" name="TERMINAL_ID" value="<?php echo $this->get_option('terminal_id'); ?>">
				 <input type="hidden" name="AMOUNT" value="<?php echo $order->get_total(); ?>"/>
				<input type="hidden" name="MerchantNumber" value="<?php echo $this->get_option('merchant_id'); ?>">
				<input type="hidden" name="MerchantName" value="CASH APOYO EFECTIVO">
				<input type="hidden" name="MerchantCity" value="Toluca">
				<button type="submit" style="display: none"></button>
			</form>
			<script>
				jQuery( document ).ready(function($) {
				  $('#banorte-bank').submit();
				});
			</script>
		</div>
		<?php
	}

	public function admin_options()
	{
		?>
		<h3><?php echo $this->title; ?></h3>
		<p><?php echo $this->method_description; ?></p>
		<table class="form-table">
			<?php
			$this->check_options();
			$this->generate_settings_html();
			?>
		</table>
		<?php
	}

	public function order_received_message($text, $order)
	{
		if ( ! empty($_GET['msg'])) {
			return $text . ' ' . $_GET['msg'];
		}

		return $text;
	}

	public function disable_payment_if_options_empty($availableGateways)
	{
		if ( ! $this->get_is_valid_options()) {
			if (isset($availableGateways[$this->id])) {
				unset($availableGateways[$this->id]);
			}
		}

		return $availableGateways;
	}

	public function check_options()
	{
		if ( ! $this->get_is_valid_options()) {
			do_action('notices_action_tag_banorte_bank_bb', __('Banorte Woocommerce: All fields are required', 'woo-banorte'));
		}
	}

	public function get_is_valid_options()
	{
		if (empty($this->get_option('merchant_id')) || empty($this->get_option('user')) || empty($this->get_option('password')) || empty($this->get_option('terminal_id'))) {
			return false;
		}

		return true;
	}

	public function process_payment($order_id)
	{
		$order = wc_get_order($order_id);
		wc_reduce_stock_levels($order_id);
		WC()->cart->empty_cart();

		return array(
			'result'   => 'success',
			'redirect' => $order->get_checkout_payment_url(true)
		);
	}

	public function restore_order_stock($order_id)
	{
		$order = new WC_Order($order_id);
		if ( ! get_option('woocommerce_manage_stock') == 'yes' && ! sizeof($order->get_items()) > 0) {
			return;
		}
		foreach ($order->get_items() as $item) {
			if ($item['product_id'] > 0) {
				$_product = $item->get_product();
				if ($_product && $_product->exists() && $_product->managing_stock()) {
					$old_stock    = $_product->stock;
					$qty          = apply_filters('woocommerce_order_item_quantity', $item['qty'], $this, $item);
					$new_quantity = $_product->increase_stock($qty);
					do_action('woocommerce_auto_stock_restored', $_product, $item);
					$order->add_order_note(sprintf(__('Item #%s stock incremented from %s to %s.', 'woocommerce'), $item['product_id'], $old_stock, $new_quantity));
					$order->send_stock_notifications($_product, $new_quantity, $item['qty']);
				}
			}
		}
	}

}

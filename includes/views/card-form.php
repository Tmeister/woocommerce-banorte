<?php
$date_new = strtotime('+2 year', strtotime(date('m/y')));
$date_new = date('m/y', $date_new);
?>

<div id="card-banorte-bank-bb">
	<div class="overlay">
		<div class="overlay-text"></div>
	</div>
	<div class="msj-error-banorte">
		<ul class="woocommerce-error" role="alert">
			<strong><li></li></strong>
		</ul>
	</div>
	<form id="banorte-bank" action="<?php echo $this->url_payment; ?>" method="POST">
		<label for="number" class="label"><?php echo __('Data of card', 'woo-banorte'); ?> *</label>
		<input type="hidden" name="MERCHANT_ID" value="<?php echo $this->get_option('merchant_id'); ?>">
		<input type="hidden" name="USER" value="<?php echo $this->get_option('user'); ?>">
		<input type="hidden" name="PASSWORD" value="<?php echo $this->get_option('password'); ?>">
		<input type="hidden" name="TERMINAL_ID" value="<?php echo $this->get_option('terminal_id'); ?>">
		<input type="hidden" name="CMD_TRANS" value="VENTA">
		<input type="hidden" name="MODE" value="<?php echo $this->get_option('environment'); ?>">
		<input type="hidden" name="ENTRY_MODE" value="MANUAL">
		<input type="hidden" name="RESPONSE_URL" value="<?php echo home_url('/'); ?>">
		<input type="hidden" name="RESPONSE_LANGUAGE" value="ES">
		<input type="hidden" name="AMOUNT" value="<?php echo $order->get_total(); ?>"/>
		<input type="hidden" name="CONTROL_NUMBER" value="<?php echo $order->get_id(); ?>"/>
		<div>
			<input type="text" name="CARD_NUMBER" id="card_number" placeholder="<?php echo __('Card number', 'woo-banorte'); ?>" required/>
		</div>
		<div>
			<input type="text" name="name" placeholder="<?php echo __('Headline', 'woo-banorte'); ?>">
		</div>
		<div>
			<input type="text" name="CARD_EXP" id="card_exp" placeholder="<?php echo $date_new; ?>" required/>
		</div>
		<div>
			<input type="number" minlength="3" name="SECURITY_CODE" id="security_code" placeholder="CVV" required/>
		</div>
		<button type="submit"><?php echo __('Pay', 'woo-banorte'); ?></button>
	</form>
</div>

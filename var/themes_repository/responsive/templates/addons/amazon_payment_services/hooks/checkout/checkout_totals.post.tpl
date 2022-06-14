{$apple_button = "cart"|fn_amazon_payment_services_check_applypay_button}
{if $cart.total > 0 && $apple_button}
	<button type="button" class="btn_aps_applypay_checkout hidden b_cart {$apple_button.processor_params.apple_button_type|default:'apple-pay-buy'} notloaded" 
		data-display_name="{$apple_button.processor_params.apple_display_name}" 
		data-page="cart" 
		data-payment_id="{$apple_button.payment_id}"
		data-mi="{$apple_button.merchant_identifier}" 
		data-params='{$apple_button.data.params|json_encode}'
		data-extra='{$apple_button.data.extra|json_encode}'> </button>
{/if}
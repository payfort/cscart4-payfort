{$apple_button = "product"|fn_amazon_payment_services_check_applypay_button}
{if $apple_button && $product.product_type eq 'P' && !empty($apple_button.processor_params.apple_button_type)}
	<button type="button" class="btn_aps_applypay_checkout hidden b_product {$apple_button.processor_params.apple_button_type|default:'apple-pay-buy'}" 
		data-display_name="{$apple_button.processor_params.apple_display_name}" 
		data-page="product" 
		data-payment_id="{$apple_button.payment_id}" 
		data-mi="{$apple_button.merchant_identifier}"> </button>
	<input type="hidden" name="payment_data[aps][gateway]" value="apple" />
	<input type="hidden" name="payment_data[aps][apple][request_data]" class="inp_applepay_request_data" value="" />
	<input type="hidden" name="contact_data" class="inp_applepay_contact_data" value="" />
{/if}
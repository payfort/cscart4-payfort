{*
{$apply_button = "product"|fn_amazon_payment_services_check_applypay_button}
{if $apply_button}
	<button type="button" class="btn_aps_applypay_checkout hidden" data-display_name="{$apply_button.processor_params.apple_display_name}" data-page="product" data-payment_id="{$apply_button.payment_id}" data-mi="{$apply_button.merchant_identifier}" style="color:#fff; background: #111; padding:10px 12px; margin-bottom: 10px; border-radius:4px; border:none;text-transform: uppercase;">Checkout with Apple Pay</button>
{/if}
*}
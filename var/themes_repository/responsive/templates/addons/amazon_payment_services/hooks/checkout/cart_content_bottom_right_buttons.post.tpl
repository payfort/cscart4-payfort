{$apple_button = "cart"|fn_amazon_payment_services_check_applypay_button:false}
{if $cart.total > 0 && $apple_button}
<form style="display:none; vertical-align: middle;" class="hidden" id="cart_frm_aps_applypay_checkout">
	<button></button>
	<input type="hidden" name="payment_data[aps][gateway]" value="apple" />
	<input type="hidden" name="payment_data[aps][apple][request_data]" class="inp_applepay_request_data" value="" />
	<input type="hidden" name="contact_data" class="inp_applepay_contact_data" value="" />
</form>
{/if}
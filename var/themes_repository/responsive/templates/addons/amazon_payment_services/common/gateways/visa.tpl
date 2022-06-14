{* <input type="hidden" id="aps_visa_checkout_status" class="fld"{if !$is_active} disabled="disabled"{/if} name="payment_data[aps][visa][status]" value="" /> *}
<input type="hidden" id="aps_visa_checkout_callid" class="fld"{if !$is_active} disabled="disabled"{/if}  name="payment_data[aps][visa][call_id]" value="" />

<div class="button_container" id="button_aps_visa_checkout" 
	data-api_key="{$gateway->getConfig('api_key')}"
	data-profile_id="{$gateway->getConfig('profile_id')}"
	data-language="{$gateway->getLanguage()}"
	data-country_code="{$cart.user_data.s_country}"
	data-screen_msg="CS-CART"
	data-continue_btn="Continue"
	data-currency="{$gateway->getCurrency()}"
	data-total="{$gateway->getAmount($cart.total)}"
	data-sdk_url="{$gateway->sdk_url}">
	<img alt="Visa Checkout" class="v-button" role="button" src="{$gateway->button_url}?cardBrands=VISA,MASTERCARD,DISCOVER,AMEX" />
</div>
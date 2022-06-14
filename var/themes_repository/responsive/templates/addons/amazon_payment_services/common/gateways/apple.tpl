{$cart = $smarty.session.cart}
{$currency = $smarty.const.CART_SECONDARY_CURRENCY}

{$cart_subtotal = $gateway->formatPrice($cart.subtotal,$currency)|number_format:2:'.':''}
{$shipping_cost = $gateway->formatPrice($cart.shipping_cost,$currency)|number_format:2:'.':''}
{$tax_subtotal = $gateway->formatPrice($cart.tax_subtotal,$currency)|number_format:2:'.':''}
{$cart_total = $gateway->formatPrice($cart.total,$currency)|number_format:2:'.':''}

{$all_total = $cart_subtotal+$shipping_cost+$tax_subtotal}
{$discount = $all_total-$cart_total}
{if $discount < 0}{$discount = 0}{/if}

{$lineItems = []}
{$lineItems[] = ["type"=>'final', "label"=>__('subtotal'), "amount"=>$cart_subtotal]}
{if $shipping_cost > 0}
	{$lineItems[] = ["type"=>'final', "label"=>__('shipping_cost'), "amount"=>$shipping_cost|number_format:2:'.':'']}
{/if}
{if $discount > 0}
	{$lineItems[] = ["type"=>'final', "label"=>__('discount'), "amount"=>$discount|number_format:2:'.':'']}
{/if}
{if $tax_subtotal > 0}
	{$lineItems[] = ["type"=>'final', "label"=>__('tax'), "amount"=>$tax_subtotal]}
{/if}
{* "lineItems" => $lineItems, *}
{$button_params = [
    "countryCode" => $cart.user_data.s_country,
    "currencyCode" => $currency,
    "merchantCapabilities" => ['supports3DS', 'supportsEMV', 'supportsCredit', 'supportsDebit'],
    "supportedNetworks" => $gateway->getConfig('supported_networks')|array_keys,
    "lineItems" => $lineItems,
    "total" => [
        "label"=> $gateway->getConfig('display_name'),
        'type' => 'final',
        "amount"=> $cart_total
    ]
]}

{$button_type = $gateway->getConfig('button_type')|replace:"apple-pay-":""|replace:"-":""|strtoupper}
<button type="button" id="btnApsApplePay" class="button_aps_applypay_checkout b_checkout {$gateway->getConfig('button_type')|default:'apple-pay-buy'}" data-mi="{$gateway->getApMerchantIdentifier()}" data-params='{$button_params|json_encode}'> </button>
<input type="hidden" name="payment_data[aps][apple][request_data]" class="inp_applepay_request_data" value="" />

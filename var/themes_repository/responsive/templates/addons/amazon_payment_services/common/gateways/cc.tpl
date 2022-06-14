{if $gateway->integration_type eq 'hosted_checkout' OR $gateway->getConfig('enable_tokenization') eq 'Y'}
	{if $gateway->link}
		{include file="addons/amazon_payment_services/common/gateways/installments.tpl" gateway=$gateway type="cc" is_linked=true is_active=$is_active}
	{else}

		{$card_tokens = []}
		{$have_tokens = false}
		{if $gateway->getConfig('enable_tokenization') eq 'Y'}
			{$card_tokens = $gateway->getCardTokens()}
			{include file="addons/amazon_payment_services/common/card_tokens.tpl" is_active=$is_active card_tokens=$card_tokens}
			{if !empty($card_tokens)}
				{$is_active = false}
				{$have_tokens = true}
			{/if}
		{/if}

		{if $gateway->integration_type eq 'hosted_checkout'}
		<div id="aps_field_card_token_0" class="aps_field_card_token{if !empty($card_tokens)} hidden{/if}">
			{include file="addons/amazon_payment_services/common/cc_form.tpl" card_id=$type is_active=$is_active}
		</div>
		{/if}
	{/if}
{/if}
{foreach from=$card_tokens item="token"}
	{$integration_type = $gateway->integration_type}
	{if $gateway->type eq 'installments'}
		{$integration_type = 'hosted_checkout'}
	{/if}

	<div class="aps_card_token ct_type_{$integration_type}{if $gateway->link} installments_linked{/if}">
		{$card_type = $token.type|trim}
		{if empty($card_type)}
			{$card_type = $gateway->getCardType($token.card_number)}
		{/if}
		<label for="aps_card_token_{$token.card_id}">
			<input 
				type="radio" 
				id="aps_card_token_{$token.card_id}" 
				name="payment_data[aps][{$type}][token_name]" 
				value="{$token.token_name}"
				class="fld tknm"
				data-target="aps_field_card_token_{$token.card_id}"
				{if $token.default eq 'Y'}checked="checked"{/if} 
				{if !$is_active}disabled="disabled"{/if}/>
			<img src="{$config.current_location}/design/themes/responsive/templates/addons/amazon_payment_services/images/{$card_type|strtolower}-logo.{if $card_type eq 'MEEZA'}jpg{else}png{/if}" height="19" />
			<b>{$token.card_number|substr:"-4"}</b>
			<span{if $integration_type eq 'redirection'} class="rht"{/if}>exp {$token.expiry_date|substr:"-2"}/20{$token.expiry_date|substr:0:2}</span>
		</label>
		{if $integration_type neq 'redirection'}
		<div id="aps_field_card_token_{$token.card_id}" class="aps_field_card_token {if $token.default neq 'Y'}hidden{/if} ty-control-group">
            <label for="aps_field_card_code_{$token.card_id}" class="ty-control-group__title cm-required cm-autocomplete-off hidden"></label>
            <input type="number"
				id="aps_field_card_code_{$token.card_id}" 
				name= "payment_data[aps][{$type}][card_security_code]" 
				value="" 
				data-card_number="{$token.card_number}"
				placeholder="{__("cvv2")}" 
				class="fld ty-cc-number-validation{if $type eq 'installments'} ty-credit_card_number_installments{/if} cvv{if $token.default neq 'Y'} fld_dsbl3{/if}"
				{if !$is_active}disabled="disabled"{else}{if $token.default neq 'Y'}disabled="disabled"{/if}{/if}
				maxlength="{if $card_type|in_array:['MADA','AMEX']}4{else}3{/if}" 
				/>
		</div>
		{/if}
	</div> 	
{/foreach}
<div class="aps_card_token ct_type_{$gateway->integration_type} last">
	<label for="aps_card_token_0">
		<input 
			type="radio" 
			id="aps_card_token_0" 
			name="payment_data[aps][{$type}][token_name]" 
			value=""
			class="fld tknm"
			data-target="aps_field_card_token_0"
			{if empty($card_tokens) eq 'Y'}checked="checked"{/if} 
			{if !$is_active}disabled="disabled"{/if}/>
		 {__("aps_add_new_card")}
	</label>
</div>
{if $gateway->integration_type eq 'hosted_checkout' OR $gateway->getConfig('enable_tokenization') eq 'Y'}
	{$is_linked = $is_linked|default:false}
	{$card_tokens = []}
	{$have_tokens = false}
	{if $gateway->getConfig('enable_tokenization') eq 'Y'}
		
		{$card_tokens = $gateway->getCardTokens()}
		{if !empty($card_tokens) && !$is_linked}
			{$is_active = false}
			{$have_tokens = true}
		{/if}

		{include file="addons/amazon_payment_services/common/card_tokens.tpl" is_active=$is_active card_tokens=$card_tokens}		
		
	{/if}

	{if $gateway->integration_type eq 'hosted_checkout'}
	<div id="aps_field_card_token_0" class="aps_field_card_token{if !empty($card_tokens)} hidden{/if}{if $is_linked} installments_linked{/if}">
		{include file="addons/amazon_payment_services/common/cc_form.tpl" card_id=$type is_active=$is_active}
	</div>
	{/if}
	{if $gateway->integration_type neq 'redirection' || !empty($card_tokens)}

		{if $gateway->integration_type eq 'standard_checkout' && empty($card_tokens)}
			{$is_active = false}
		{/if}
		{$show_issuer_name = false}
		{$show_issuer_logo = false}
		{if $gateway->getConfig('show_issuer_name') eq 'Y'}
			{$show_issuer_name = true}
		{/if}		
		{if $gateway->getConfig('show_issuer_logo') eq 'Y'}
			{$show_issuer_logo=true}
		{/if}

		{if $gateway->link}
			{if $gateway->link->getConfig('show_issuer_name') eq 'Y'}
				{$show_issuer_name = true}
			{/if}
			{if $gateway->link->getConfig('show_issuer_logo') eq 'Y'}
				{$show_issuer_logo = true}
			{/if}		
		{/if}

		{if $is_linked}
			{$is_active = false}
		{/if}

		<div class="ty-control-group box_installment_plans hidden" 
			data-text-months="{__("months")|replace:"(":""|replace:")":""}" 
			data-text-month="{__("month")|strtolower}"
			data-currency="{$smarty.const.CART_SECONDARY_CURRENCY}"
			data-text-interest="{__("aps_interest")}"
		>
			<label for="aps_installments_plan_code" class="cm-required hidden">{__("plan")}</label>

			<div class="issuer_name{if !$show_issuer_name} hidden{/if}">
				{__("aps_issuer_name")}: <b></b>
			</div>
			<div class="list_plans"></div>

			<input type="hidden" name="payment_data[aps][installments][issuer_code]" class="fld installments_issuer_code fld_dsbl2"{if !$is_active} disabled="disabled"{/if} value=""/>
			<input type="hidden" name="payment_data[aps][installments][plan_code]" id="aps_installments_plan_code" class="fld installments_plan_code{if $gateway->integration_type neq 'hosted_checkout' || $is_linked} fld_dsbl2{/if} "{if !$is_active} disabled="disabled"{/if} value=""/>
			<div class="cm-field-container box{if $is_linked} hidden{/if}">
			    <label for="chk_aps_installment_terms_condition" class="cm-check-agreement"><input type="checkbox" id="chk_aps_installment_terms_condition" name="accept_terms" value="Y"{if !$is_active} disabled="disabled"{/if} class="fld{if $is_linked OR $gateway->integration_type neq 'hosted_checkout'} fld_dsbl2{/if} cm-agreement checkbox"/>
			    	<img src="" class="issuer_logo" style="height:21px;margin-top:-4px;" {if !$show_issuer_logo} class="hidden"{/if}/> <span class="terms_conditions">{__("aps_installments_terms_conditions")}</span>
			    	<div class="fees_message hidden"></div>
			    </label>
			</div>

		</div>
	{/if}
{/if}
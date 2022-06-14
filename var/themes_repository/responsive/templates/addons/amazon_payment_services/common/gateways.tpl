{if !empty($gateways)}
	<div class="aps_gateways_list">
		{foreach from=$gateways item="gateway" key="gw_type"}
			<div class="gateway_item gateway_type-{$gw_type}{if $gw_type eq 'apple' && $gateways|count >1} hidden{/if}">
				<label class="gt-label" for="elm_aps_gateway_{$gw_type}">
		            <input type="radio" name="payment_data[aps][gateway]" id="elm_aps_gateway_{$gw_type}"{if $gateway.first} checked="checked"{/if} value="{$gw_type}"/>
		            {$gateway.title}

		            {if !empty($gateway.object->logos)}
		            <span class="aps_logos">
		            	{foreach from=$gateway.object->logos item="_logo"}
		            		<img src="{$config.current_location}/design/themes/responsive/templates/addons/amazon_payment_services/images/{$_logo}" height="19" />
		            	{/foreach}
		            </span>
		            {/if}
		        </label>
		        {if $gateway.object->template}
		        <div class="tpl_gateway"{if !$gateway.first} style="display:none;"{/if} id="tpl_aps_gateway_{$gw_type}">
		        	{include file="addons/amazon_payment_services/common/gateways/{$gw_type}.tpl" gateway=$gateway.object type=$gw_type is_active=$gateway.first}
		        </div>
		        {/if}	
			</div>
			
			{if $gateway.object->integration_type eq 'standard_checkout'}
				<input id="iframe_standard_checkout_{$gw_type}" type="hidden" value="Y">
			{/if}
			
		{/foreach}
	</div>

	<div class="hidden" title="" class="aps_payment_iframe_container" id="aps_payment_iframe_container_{$payment.payment_id}"><div class="iframe_container"></div></div>
	
	<a id="opener_aps_payment_iframe_container_{$payment.payment_id}" class="cm-dialog-opener cm-dialog-auto-size hidden" data-ca-target-id="aps_payment_iframe_container_{$payment.payment_id}" title="" rel="nofollow"></a>

{else}
	<p class="ty-error-text">{__("aps_no_payment_option")} !</p>
{/if}

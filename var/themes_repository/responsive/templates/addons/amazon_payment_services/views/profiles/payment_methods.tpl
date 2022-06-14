
<table class="ty-table">
    <thead>
        <tr>
            <th width="50%">{__("method")}</th>
            <th>{__("expiry_date")}</th>
            
            <th>{__("actions")}</th>
        </tr>
    </thead>
    {foreach from=$user_cards item="card"}
        <tr>
        	<td>
				<img src="{$config.current_location}/design/themes/responsive/templates/addons/amazon_payment_services/images/{$card.type|strtolower}-logo.{if $card.type eq 'MEEZA'}jpg{else}png{/if}" height="19" style="margin-top: -2px;display: inline-block;margin-right: 10px;" /> 
				{$card.type} {$card.card_number|substr:"-4"}
        	</td>
            <td>
            	{$card.expiry_date|substr:"-2"} / 20{$card.expiry_date|substr:0:2}
        	</td>
            <td>
            	{if $card.default eq 'N'}
	                <a class="ty-btn cm-post" href="{"profiles.card_default?card_id=`$card.card_id`"|fn_url}">{__("set")} {__("default")}</a>
                {/if}
                <a class="ty-btn cm-post" href="{"profiles.card_delete?card_id=`$card.card_id`"|fn_url}" title="{__('delete')}">{__("delete")}</a>
            </td>
        </tr>
    {foreachelse}
        <tr class="ty-table__no-items">
            <td colspan="7">
                <p class="ty-no-items">{__("no_items")}</p>
            </td>
        </tr>
    {/foreach}
</table>

{capture name="mainbox_title"}{__("payment_methods")}{/capture}
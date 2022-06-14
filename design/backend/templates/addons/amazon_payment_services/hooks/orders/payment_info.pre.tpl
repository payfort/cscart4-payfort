{if 'aps.php'|fn_check_payment_script:$order_info.order_id}
	{$opinfo = $order_info.payment_info}
	{if $opinfo.order_status eq 'P'}
		{$aps_mode = $opinfo.payment_mode|strtoupper}
		{if $aps_mode eq 'PURCHASE' OR $aps_mode eq 'CAPTURE' OR $aps_mode eq 'AUTHORIZATION'}
			
			{if $aps_mode eq 'AUTHORIZATION'}
				<button type="button" class="btn btn-primary cm-dialog-opener btn-aps-order-action" data-ca-target-id="dialog_aps_order_rc_auth" title="{__("capture")}: #{$order_info.order_id}" data-amount="{$opinfo.amount}" data-done_amount="{$opinfo.captured_amount|floatval}" data-action="capture" data-currency="{$opinfo.currency}">{__("capture")}</button>
				{if empty($opinfo.captured_amount)}
				<a class="btn cm-confirm cm-post cm-ajax1" href="{"amazon_payment_services.action?order_id=`$order_info.order_id`&action=void"|fn_url}">{__("aps_void")}</a>
				{/if}
			{/if}

			{if $aps_mode eq 'PURCHASE' OR $aps_mode eq 'CAPTURE' OR !empty($opinfo.captured_amount)}
				<button type="button" class="btn cm-dialog-opener cm-dialog-auto-size btn-aps-order-action" data-ca-target-id="dialog_aps_order_rc_auth" title="{__("refund")}: #{$order_info.order_id}" data-action="refund" data-done_amount="{$opinfo.refunded_amount|floatval}" data-amount="{if !empty($opinfo.captured_amount) && $aps_mode neq 'CAPTURE'}{$opinfo.captured_amount}{else}{$opinfo.amount}{/if}" data-currency="{$opinfo.currency}">{__("refund")}</button>
			{/if}
			
		{/if}
	{/if}
{/if}
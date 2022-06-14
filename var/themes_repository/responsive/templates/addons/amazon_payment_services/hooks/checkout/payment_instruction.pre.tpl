{if !empty($order_info.payment_info) && 'aps.php'|fn_check_payment_script:$order_info.order_id}
	{$opinfo = $order_info.payment_info}
	{if !empty($opinfo.knet_ref_number)}
		{foreach from=['third_party_transaction_number','knet_ref_number'] item="pkey"}
			{if !empty($opinfo[$pkey])}
				<p><b>{__($pkey)}</b>: {$opinfo[$pkey]}</p>
			{/if}
		{/foreach}
	{/if}
	
	{if !empty($opinfo.number_of_installments) }
		{foreach from=['third_party_transaction_number','installments','number_of_installments','issuer_code','plan_code','currency','amount'] item="pkey"}
			{if !empty($opinfo[$pkey])}
				<p><b>{__($pkey)}</b>: {$opinfo[$pkey]}</p>
			{/if}
		{/foreach}
	{/if}
	{if !empty($opinfo.tenure_amount)}
		{foreach from=['tenure','tenure_amount','tenure_interest'] item="pkey"}
			{if !empty($opinfo[$pkey])}
				<p><b>{__($pkey)}</b>: {$opinfo[$pkey]|replace:'/Month':"/{ucfirst(__("month"))}"}</p>
			{/if}
		{/foreach}
	{/if}
{/if}
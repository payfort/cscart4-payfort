{if 'aps.php'|fn_check_payment_script:$order_info.order_id}
	{$opinfo = $order_info.payment_info}
	{if $opinfo.order_status eq 'P'}
		{$aps_mode = $opinfo.payment_mode|strtoupper}
		{if $aps_mode eq 'PURCHASE' OR $aps_mode eq 'CAPTURE' OR $aps_mode eq 'AUTHORIZATION'}
		<form action="{""|fn_url}" method="post" class="form-horizontal form-edit">
			<input type="hidden" name="order_id" value="{$order_info.order_id}" />
			<div class="hidden ufa" title="" id="dialog_aps_order_rc_auth">
				<fieldset class="asp-order-action-fields">
					<input type="hidden" class="inp_asp_action" name="action" value="">
					<div class="control-group">
				        <label for="elm_aps_amount" class="control-label">{__("order")} {__("total")}:</label>
				        <div class="controls">
				            <span style="padding-top: 5px;display: inline-block;font-weight: bold;">{$opinfo.currency} {$opinfo.amount}</span>
				        </div>
				    </div>
				    {if $aps_mode neq 'PURCHASE'}
					<div class="control-group">
				        <label for="elm_aps_amount" class="control-label">{__("captured")} {__("total")}:</label>
				        <div class="controls">
				            <span style="padding-top: 5px;display: inline-block;font-weight: bold;">{$opinfo.currency} {$opinfo.captured_amount|floatval|number_format:2:'.':''}</span>
				        </div>
				    </div>
				    {/if}
				    {if !empty($opinfo.refunded_amount)}
				    <div class="control-group">
				        <label for="elm_aps_amount" class="control-label">{__("refunded")} {__("total")}:</label>
				        <div class="controls">
				            <span style="padding-top: 5px;display: inline-block;font-weight: bold;">{$opinfo.currency} {$opinfo.refunded_amount|floatval|number_format:2:'.':''}</span>
				        </div>
				    </div>
				    {/if}
					<div class="control-group">
				        <label for="elm_aps_amount" class="control-label cm-required cm-price cm-number"><span class="txt_amount" data-txt_capture="{__("capture")}" data-txt_refund="{__("refund")}"></span> {__("total")}:</label>
				        <div class="controls">
				            <span style="padding-top: 5px;display: inline-block;font-weight: bold;">{$opinfo.currency}</span> <input type="text" name="amount" id="elm_aps_amount" maxlength="99999" value="" class="inp_asp_mount input-small">
				        </div>
				    </div>
				</fieldset>
			    <div class="buttons-container">
				    <a class="cm-dialog-closer cm-inline-dialog-closer cm-cancel btn">{__("cancel")}</a>
				    <input  class="btn btn-primary" type="submit" name="dispatch[amazon_payment_services.action]" value="{__("submit")}" />    
	    		</div>
			</div>
		</form>
		<style type="text/css">
			[aria-describedby="dialog_aps_order_rc_auth"]{ width: 460px !important; left: 50% !important;margin-left: -230px; }
			[aria-describedby="dialog_aps_order_rc_auth"] .object-container,#dialog_aps_order_rc_auth{ height: auto !important; }
			[aria-describedby="dialog_aps_order_rc_auth"] .object-container fieldset{ padding:0px 0 40px 0; }
			#dialog_aps_order_rc_auth .control-group{ margin-bottom: 5px; }
			[aria-describedby="dialog_aps_order_rc_auth"] .control-label{ text-align: right; }
		</style>
		<script type="text/javascript">
		jQuery(function($){
			$(document).on("click",".btn-aps-order-action",function(){
				_data = $(this).data();
				trgt = $('fieldset.asp-order-action-fields');
				label = trgt.find('label span.txt_amount');
				label.html(label.data('txt_'+_data.action));
				done_amount = parseFloat(_data.done_amount);
				amount = parseFloat(_data.amount);
				trgt.find('.inp_asp_action').val(_data.action);
				trgt.find('.inp_asp_mount').val((amount-done_amount).toFixed(2));
			});
		});	
		</script>
		{/if}
	{/if}
{/if}
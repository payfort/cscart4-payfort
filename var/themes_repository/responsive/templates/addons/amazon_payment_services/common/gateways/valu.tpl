<input type="hidden" name="payment_data[aps][merchant_reference]" {if !$is_active} disabled="disabled"{/if} class="fld aps_merchant_reference" value=""/>
<input type="hidden" name="payment_data[aps][action]" {if !$is_active} disabled="disabled"{/if} value="" class="fld aps_action" />
<input type="hidden" name="payment_data[aps][valu][transaction_id]" {if !$is_active} disabled="disabled"{/if} class="fld valu_transaction_id" value=""/>

<div class="ty-control-group box_aps_phone_verify"
	data-required-text="{__("api_required_field",['[field]'=>"<b>{__("aps_phone_number")}</b>"])}"
	data-invalid-text="{__("aps_invalid_mobile_number")}" >
	<span class="pcode">+20</span><input 
		size="35" type="text" 
		name="payment_data[aps][valu][phone_number]" 
		value="" 
		class="fld cm-autocomplete-off aps_phone_number" 
		autocomplete="off"
		{if !$is_active} disabled="disabled"{/if}
		placeholder="{__("aps_phone_number_placeholder")}" 
		maxlength="19" 
	/><button type="button" class="ty-btn ty-btn__primary btn-aps-action-verify btn-aps-phone-verify" data-txt_processing="{__('processing')}" data-action="phone_verify" data-target=".box_aps_otp_verify">{__("aps_request_otp")}</button>
</div>

<div class="ty-control-group box_aps_otp_verify hidden"
	data-required-text="{__("api_required_field",['[field]'=>"<b>OTP</b>"])}"
	data-invalid-text="{__("aps_invalid_mobile_number")}" >
	<label class="ty-control-group__title">{__("aps_otp_mobile_sent")}: <span data-lang="{$smarty.const.DESCR_SL}"></span></label>
	<input 
		size="35" type="password" 
		name="payment_data[aps][valu][otp]" 
		value="" 
		class="fld cm-autocomplete-off aps_phone_otp" 
		autocomplete="off"
		{if !$is_active} disabled="disabled"{/if} 
		placeholder="{__("aps_otp_placeholder")}" 
		maxlength="6" 
	/><button type="button" class="ty-btn ty-btn__primary btn-aps-action-verify btn-aps-otp-verify" data-action="otp_verify" data-target=".box_aps_valu_tenures" data-txt_processing="{__('processing')}">{__("aps_verify_otp")}</button>
</div>

<div class="ty-control-group box_aps_valu_tenures hidden" 
	data-text-months="{__("months")|replace:"(":""|replace:")":""}" 
	data-text-month="{__("month")|strtolower}"
	data-currency="{$smarty.const.CART_SECONDARY_CURRENCY}"
	data-text-interest="{__("aps_interest")}"	
  >
	<label for="aps_valu_tenure" class="cm-required hidden">{__("aps_installment_plan")}</label>
	
	<label class="ty-control-group__title">{__("aps_otp_verified_success")}</label>
	<div class="list_valu_tenures"></div>
	<input type="hidden" id="aps_valu_tenure" name="payment_data[aps][valu][tenure]" value="" class="fld fld_dsbl aps_valu_tenure" disabled="disabled"/>
	<input type="hidden" name="payment_data[aps][valu][total_down_payment]" value="" class="fld fld_dsbl aps_down_payment" disabled="disabled" />
	<input type="hidden" name="payment_data[aps][valu][interest]" value="" class="fld fld_dsbl aps_valu_interest" disabled="disabled" />

	<div class="cm-field-container">
	    <label for="chk_aps_valu_terms_condition" class="cm-check-agreement"><input type="checkbox" id="chk_aps_valu_terms_condition" name="accept_terms" value="Y" class="fld fld_dsbl cm-agreement checkbox" disabled="disabled"/>
	    	{__("aps_valu_terms_conditions")}
	    </label>
	</div>
    <div class="hidden" id="dialog_aps_valu_terms_condition">
    	<div class="box_valu_terms_conditions">
	    	{* $smarty.const.DESCR_SL *}
	    	<ul>
		     <li>
		        <p><span>valU monthly payments option applies only to qualifying products purchased and shipped by Merchant and/or any of its affiliates, including where the &quot;monthly payments&quot; option is available on the product checkout page.</span></p>
		    </li>
		     <li>
		        <p><span>valU monthly payments option is not transferable and may not be combined with other options.</span></p>
		    </li>
		     <li>
		        <p><span>To be eligible for this option, you must be a valU customer, you must have available valU balance associated with your valU account, and you must have a good payment history on your valU purchases.&nbsp;</span></p>
		    </li>
		     <li>
		        <p><span>valU monthly payments option may not be available to every customer and may not be available to you for all qualifying products.&nbsp;</span></p>
		    </li>
		     <li>
		        <p><span>valU reserves the right to consider for each transaction factors including your transaction history and past products purchased using valU and the price of the qualifying product, in determining your eligibility for this option.&nbsp;</span></p>
		    </li>
		     <li>
		        <p><span>Each purchase you make using valU monthly payments option will be added to your overall valU outstanding balance and will be reported to The Egyptian Credit Bureau (I-score) along with your monthly payments.</span></p>
		    </li>
		     <li>
		        <p><span>Option is only available for qualifying products in terms of products availability for shipment and delivery.</span></p>
		    </li>
		     <li>
		        <p><span>valU reserves the right to cancel this option at any time.</span></p>
		    </li>
		     <li>
		        <p><span>You will be charged with the full price of the qualifying product you selected in subsequent monthly payments including interest or finance charges.</span></p>
		    </li>
		     <li>
		        <p><span>Any interest, finance charges or fees assessed by the issuer of the payment plan to which payments are charged may still apply.&nbsp;</span></p>
		    </li>
		     <li>
		        <p><span>Any applicable tax and shipping charges will be due and assessed in full as part of your monthly payment when you check out.</span></p>
		    </li>
		     <li>
		        <p><span>You authorize valU to charge your balance with your preferred payment plan from 3 months and up to 36 months.</span></p>
		    </li>
		     <li>
		        <p><span>You may prepay the full remaining balance of your purchase at any time, but you may not prepay a portion of the remaining balance.</span></p>
		    </li>
		    <li>
		        <p><span>Warranty legal terms and conditions</span></a><span>&nbsp;therein.</span></p>
		    </li>
		     <li>
		        <p><span>Any return of a product purchased through this option, will be subject to Merchant return policy terms and the amount of any resulting refund will be available to use for buying another product with the same refund amount. This process has no impact whatsoever on your selected monthly payment plan and you&rsquo;re fully liable for paying your monthly payment on due date shown on your valU mobile application.</span></p>
		    </li>
		    <li>
		        <p><span>You may not dispose the purchased goods through any type of transaction prior full settlement of the selected monthly payments plan. Any act to dispose of the purchased goods prior full settlement may subject you to criminal consequences in case of defaults on due payments.</span></p>
		    </li>
		    <li>
		        <p><span>In the event that you fail to pay two consecutive monthly payments, valU reserves its rights to annul this purchase and claim full settlement of due payments without prior notice or warning to you.</span></p>
		    </li>
		    <li>
		        <p><span>You acknowledge that all purchases you do on Merchant website using valU monthly payments option are subject to valU&rsquo;s terms and conditions signed by you on valU application form.</span></p>
		    </li>
		    <li>
		        <p><span>You are fully responsible for using your valU balance on Merchant website as well as fully responsible for safeguarding the usage of your username, and OTP received on your mobile registered number from any third parties &amp;/or breaches, which, in the event that this happens, valU bears no responsibility.</span></p>
		    </li>
		    <li>
		        <p><span>You fully and unconditionally agree to the right of valU to collect, preserve and possess all the information, data and documents submitted by you for use in legally permissible purposes.</span></p>
		    </li>
		    <li>
		        <p><span>You agree expressly and unconditionally to deal through electronic means and media (including e-mail messages), and that such means and media are valid and with full legal effect.</span></p>
		    </li>
		    <li>
		        <p><span>Products identified in the Consumer Finance Law no. 18 of 2020 only shall be permissible.</span></p>
		    </li>
		</ul>	

	    </div>
    </div>

</div>
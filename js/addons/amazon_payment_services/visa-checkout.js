var firstVCLoaded = false;
setInterval(function(){
    if( $("#button_aps_visa_checkout").length ){
    	if( !$("#button_aps_visa_checkout").hasClass('sdk_loaded') ){
	    	$("#button_aps_visa_checkout").addClass('sdk_loaded')
	        $.getScript($("#button_aps_visa_checkout").data('sdk_url'), function(data, sts){ 
				if( firstVCLoaded ) onVisaCheckoutReady();
				firstVCLoaded = true;
			});
	    }
    }
}, 500);

function onVisaCheckoutReady() {
	if( !jQuery("#button_aps_visa_checkout").length ) return false;

	var vc_params = jQuery("#button_aps_visa_checkout").data();

	vi_params = {
		apikey : vc_params.api_key, 
		externalProfileId : vc_params.profile_id,
		settings : {
			locale : vc_params.language,
			countryCode : vc_params.country_code, 
			review : {
				message : vc_params.screen_msg,
				buttonAction : vc_params.continue_btn
			},
			threeDSSetup : { threeDSActive : "false" }
		},
		paymentRequest : {
			currencyCode : vc_params.currency,
			subtotal : vc_params.total, 
		}
	};

	V.init(vi_params);

	V.on("payment.success",function(payment) {
		console.log(payment);
		if (payment.callid) {
			jQuery("#aps_visa_checkout_callid").val(payment.callid);
			//jQuery("#aps_visa_checkout_status").val('success');
		}
		jQuery('[name="dispatch[checkout.place_order]"]').click();
	});

	V.on("payment.cancel",function(payment){
		console.log(payment);
		//jQuery("#aps_visa_checkout_status").val('cancel');
		//jQuery('[name="dispatch[checkout.place_order]"]').click();
	});
	
	V.on("payment.error",function(payment, error){ 
		console.log(payment);
		console.log(error);
		//jQuery("#aps_visa_checkout_status").val('error');
	});
}
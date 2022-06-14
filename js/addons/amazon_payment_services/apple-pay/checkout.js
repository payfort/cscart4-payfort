(function(_, $) {
	var firstAPLoaded = false;

	setInterval(function(){
		var optApplePay = $(".aps_gateways_list .gateway_type-apple");
		if( optApplePay.length ){
			if( !optApplePay.hasClass("chckd") ){
				optApplePay.addClass("chckd");
				if( !firstAPLoaded ){
					$.getScript('https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js', function(data, sts){ 
						firstAPLoaded = true;
						initApplePayButton(optApplePay,1);
					});
				} else 
					initApplePayButton(optApplePay,2);
			}
		}
	},500);

	function initApplePayButton(opt,ls){
		if (window.ApplePaySession) {
			var promise = ApplePaySession.canMakePaymentsWithActiveCard(opt.find('button#btnApsApplePay').data('mi'));
		    promise.then(function(canMakePayments){
		    	if(canMakePayments || 1)
					opt.removeClass('hidden');
			});
	    }
	}

	$(document).on("click","button#btnApsApplePay",function(){
		var jelm = $(this), form = jelm.parents('form');
		if( !form.ceFormValidator('check') ){ 
			form.find('button[name="dispatch[checkout.place_order]"]').trigger('click');
			return false;
		}
		params = jelm.data('params');
		initApplePayment(params,form);
	});

	function initApplePayment(paymentRequest,form){
        try{ 
            
            var session = new ApplePaySession( 1, paymentRequest );
            
            // Merchant Validation
            session.onvalidatemerchant = function (event) {
                var promise = performApplePayValidation( event.validationURL,form );
                promise.then(function(merchantSession){
                    session.completeMerchantValidation( merchantSession );
               	});
            }

            session.onpaymentmethodselected = function(event) {
                session.completePaymentMethodSelection( paymentRequest.total , paymentRequest.lineItems );
            }

            session.onpaymentauthorized = function (event) {
                var promise = sendApplePaymentToken( event.payment.token );
                promise.then(
                    function (success) {
                        if (success) {
                            status = ApplePaySession.STATUS_SUCCESS;
                            form.find('.inp_applepay_request_data').val(JSON.stringify(event.payment.token));
                            form.find('button[name="dispatch[checkout.place_order]"]').trigger('click');
                        } else {
                        	$.ceNotification('show',{ type: 'E', title: _.tr('error'), message: "Payment failed, try again !" });
                            status = ApplePaySession.STATUS_FAILURE;
                        }
                        session.completePayment( status );
                    }
                );
            }

            session.oncancel = function(event) {
                $.ceNotification('show',{ type: 'E', title: _.tr('error'), message: "Payment Canceled !" });
            }

            session.begin();

        } catch(err) {
            $.ceNotification('show',{ type: 'E', title: _.tr('error'), message: err.message });
        }
    }

	function sendApplePaymentToken(paymentToken) {
        return new Promise(function(resolve, reject){ resolve(true); });
    }
    
    function performApplePayValidation(apple_url,form) {
        return new Promise(
            function(resolve, reject) {
                $.ajax({ 
                	url:fn_url("amazon_payment_services.ajax?action=validate_merchant"), 
                	data: form.serialize()+'&payment_data[aps][apple][apple_url]='+encodeURI(apple_url), 
                	method: "POST", 
                	success: function(res){
						if( res.success ){ resolve(res.data); }
						else{ reject; }
					}, 
					error: function(){ reject; }
				});
            }
        );
    }

})(Tygh, Tygh.$);
 
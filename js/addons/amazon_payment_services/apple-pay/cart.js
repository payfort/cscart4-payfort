(function(_, $) {
	
	var firstAPLoaded = false;	
	setInterval(function(){
		var btnApplePay = $("button.btn_aps_applypay_checkout.notloaded");
		if( btnApplePay.length ){
			var apForm = $("#cart_frm_aps_applypay_checkout");
			if( btnApplePay.data('page') == 'cart' ){
				btnApplePay.removeClass('notloaded');
				apForm.find("button").replaceWith(btnApplePay);
				apForm.css({'display':'inline-block'});
				loadAppleCheckoutButton(btnApplePay);
			}
		}
	},200);

	function loadAppleCheckoutButton(btnApplePay){
		$.getScript('https://applepay.cdn-apple.com/jsapi/v1/apple-pay-sdk.js', function(data, sts){ 
			if( !firstAPLoaded ){
				firstAPLoaded = true;
				initAppleCheckoutButton(btnApplePay,1);
			} else 
				initAppleCheckoutButton(btnApplePay,2);
		});
	}

	function initAppleCheckoutButton(btn,ls){
		if (window.ApplePaySession) {
			var promise = ApplePaySession.canMakePaymentsWithActiveCard(btn.data('mi'));
		    promise.then(function(canMakePayments){
		    	if (canMakePayments || 1){
					btn.removeClass('hidden');
				}
			});
	    }
	}

	$(document).on("click",".btn_aps_applypay_checkout",function(e){
		e.preventDefault();
		var jelm = $(this), form = jelm.parents('form');
		
		initApplePayment(jelm.data('params'),jelm.data('extra'),jelm,form);
	});

	function initApplePayment(paymentRequest,extra,btn,form){
       	try{ 
       		    
      		var runningAmount = parseFloat(extra.running_total);
			var runningPP     = 0;
			var runningTotal  = function(){ return parseFloat(runningAmount+runningPP).toFixed(2); };
			var parseLineItems = function(items){
				var _nitems=[];
				$.each(items,function(idx,itm){
					_nitems.push(itm);
				});
				return _nitems;
			};
			var shippingMethods = extra.shippings;
			var displayName = btn.data('display_name');
			var lineItems = paymentRequest.lineItems;
			var slcSIdentifier = '';
			var slcSContact = {};

			paymentRequest.lineItems = parseLineItems(lineItems);
            if( extra.identifier )
            	getShippingCosts(extra.identifier);
  	
            var session = new ApplePaySession( 1, paymentRequest );
            
            function getShippingOptions1( contact,form,payment_id ){
            	
				return new Promise(
		            function(resolve, reject) {
		            	form.find('.inp_applepay_contact_data').val(JSON.stringify(contact));
		            	_fdata = form.serialize();
						_fdata = _fdata+'selected_payment_method='+payment_id+'&payment_id='+payment_id+'&shipping_id=0';

		                $.ajax({ 
		                	url:fn_url("amazon_payment_services.applepay_checkout?page=cart&action=get_shippings"), 
		                	data: _fdata, 
		                	method: "POST", 
		                	success: function(res){
								if( res.success ){ resolve(res); }
								else{ reject; }
							}, 
							error: function(){ reject; }
						});
		            }
		        );
            }

            function getShippingOptions(contact){
				shippingMethods  = extra.shippings;
				return shippingMethods;
			}

			function getShippingCosts(shippingIdentifier){
				slcSIdentifier = shippingIdentifier;
				$.each(shippingMethods,function(idx,sh){
					if( shippingIdentifier == sh.identifier )
						runningPP = parseFloat(sh.amount);
				});
			}

			function processAspProductOrder(btn,form,aptoken,ap_contact){
				_fdata = form.serialize();
				payment_id = btn.data('payment_id');
				aptoken = JSON.stringify(aptoken);
				slcSContact = ap_contact;

				_fdata = _fdata+'selected_payment_method='+payment_id+'&payment_id='+payment_id+'&shipping_id='+slcSIdentifier.replace('si_','');
				
				if( btn.data('order_id') != undefined )
					_fdata += '&order_id='+btn.data('order_id');
				
				$(".ty-ajax-overlay,.ty-ajax-loading-box").show();    

				$.ajax({ url:fn_url("amazon_payment_services.applepay_checkout?page=cart&action=place_order"), method:"POST", data:_fdata , success: function(res){
					$(".ty-ajax-overlay,.ty-ajax-loading-box").hide(); 

					if( res.data ){
						if( res.data.order_id )
							btn.attr('data-order_id',res.data.order_id).data('order_id',res.data.order_id);
					}
 
					if( res.success ){
						
						if( !res.redirect_url )
							res.redirect_url = fn_url('checkout.complete?order_id='+res.data.order_id);
						
						$(".ty-ajax-overlay,.ty-ajax-loading-box").show(); 					
						window.location.href = res.redirect_url;
						
					} else {
						if( res.error )
							$.ceNotification('show',{ type: 'E', title: _.tr('error'), message: res.error });
					}
				}});
			}
            
            // Merchant Validation
            session.onvalidatemerchant = function (event) {
                var promise = performApplePayValidation( event.validationURL,form,btn.data('payment_id') );
                promise.then(function(merchantSession){
                    session.completeMerchantValidation( merchantSession );
               	});
            }

			session.onshippingcontactselected = function(event) {
				slcSContact = event.shippingContact;
				var promise = getShippingOptions1( event.shippingContact,form,btn.data('payment_id') );
                promise.then(function(res){
    
                	shippingMethods = res.extra.shippings;
    
                   	if( res.extra.identifier )
	                   	getShippingCosts(res.extra.identifier);
    
                   	var newTotal = { type: 'final', label: btn.data('display_name'), amount: runningTotal() };
					lineItems['shipping'] = {type: 'final',label: _.tr('shipping_cost'), amount: runningPP };
					
					session.completeShippingContactSelection(ApplePaySession.STATUS_SUCCESS, shippingMethods, newTotal, parseLineItems(lineItems) );
               	});
			}

			session.onshippingmethodselected = function(event) {
				getShippingCosts(event.shippingMethod.identifier);
				var newTotal = { type: 'final', label: btn.data('display_name'), amount: runningTotal() };
				lineItems['shipping'] = { type: 'final',label: _.tr('shipping_cost'), amount: runningPP };
				session.completeShippingMethodSelection(ApplePaySession.STATUS_SUCCESS, newTotal,parseLineItems(lineItems)  );
			}
			
            session.onpaymentmethodselected = function(event) {
            	var newTotal = { type: 'final', label: displayName, amount: runningTotal() };
				session.completePaymentMethodSelection( newTotal, parseLineItems(lineItems) );
            }

            session.onpaymentauthorized = function (event) {
                var promise = sendApplePaymentToken( event.payment.token );
                promise.then(
                    function (success) {
                        if (success) {
                            status = ApplePaySession.STATUS_SUCCESS;
                           	form.find('.inp_applepay_request_data').val(JSON.stringify(event.payment.token));
                            form.find('.inp_applepay_contact_data').val(JSON.stringify(event.payment.shippingContact));
                            setTimeout(function(){  processAspProductOrder(btn,form); },10);
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
    
    function performApplePayValidation(apple_url,form,payment_id){
        return new Promise(
            function(resolve, reject) {
				_data = 'selected_payment_method='+payment_id+'&payment_id='+payment_id+'&security_hash='+_.security_hash+'&payment_data[aps][gateway]=apple&payment_data[aps][apple][apple_url]='+encodeURI(apple_url);
                $.ajax({ 
                	url:fn_url("amazon_payment_services.ajax?action=validate_merchant"), 
                	data: _data, 
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
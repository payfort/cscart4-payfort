(function(_, $) {

	if( $('#litecheckout_payments_form').length )
		$('#litecheckout_payments_form')[0].reset();

	function apsShowHideLoading(hide,txt){
		txt = txt.trim().replace('...','');
		if( hide)
			$(".ty-ajax-loading-box,.ty-ajax-overlay").fadeOut(); 
		else
			$(".ty-ajax-loading-box,.ty-ajax-overlay").show();
		$(".ty-ajax-loading-box").find('.ty-ajax-loading-box-with__text-wrapper').remove();	

		if( !hide )	
			$(".ty-ajax-loading-box").append('<div class="ty-ajax-loading-box-with__text-wrapper">'+txt+' ...</div>').css({'margin-left':'-60px','width':'130px'});
	}

	function renderItemsSlider(obj,items){
		obj.html('<div class="items_slider">'+items.join(' ')+'</div>');
		var _osldr = obj.find('.items_slider');
		_osldr.width(_osldr.innerWidth());
		_osldr.slick({
			slidesToShow: 4,
			slidesToScroll: 1,
			autoplay: false,
			infinite: false,
			responsive: [
			    { breakpoint: 1024,settings: { slidesToShow: 3 } },
			    { breakpoint: 600, settings: { slidesToShow: 2 } },
			    { breakpoint: 460, settings: { slidesToShow: 1 } }
			]
		});	
		if( !_osldr.find('.slick-arrow').length ) 
			obj.addClass('no_arrows') 
		else 
			obj.removeClass('no_arrows');
	}

	function renderPaymentIframe(payment_id,checkout_url,params,gtwy){
		var _ifrCntr = $("#aps_payment_iframe_container_"+payment_id),
			_formId = "aps_payment_standard_checkout_form_" + payment_id,
			_ifrslcr = "aps_payment_iframe_"+payment_id;

		apsShowHideLoading(0,_.tr('loading'));

		$('iframe#' +_ifrslcr).remove();
		$('form#' +_formId).remove();
		
		$('<form id="'+_formId+'" action="'+checkout_url+'" method="POST" class="hidden"><input type="submit"/></form>').appendTo("body");

		$.each(params, function(k, v){ 
			$('<input>').attr({ type: 'hidden', name: k, value: v } ).appendTo('form#' +_formId); 
		});
		
		_width = gtwy == 'installments' ? "630px" : "470px";
		_height = gtwy == 'installments' ? "630px" : "435px";

		_ifrCntr.find('.iframe_container').css({'width' : _width, 'height' : _height,'overflow':'hidden'});

		$("#opener_aps_payment_iframe_container_"+payment_id).trigger('click');
		setTimeout(function(){
			$('<iframe>').attr({ 
				name: _ifrslcr,
				id: _ifrslcr,
				height: _height,
				width: _width, 
				frameborder:"0",
				scrolling:"no",
				onload: function(ths){ 
					setTimeout(function(){ apsShowHideLoading(1,'');},3000);
				}
			}).appendTo(_ifrCntr.find('.iframe_container'));

			//$("#"+_ifrslcr).attr('src',checkout_url);
			$("#"+_formId).attr( "target",_ifrslcr );
			$("#"+_formId).find('input[name="security_hash"]').remove();			
			setTimeout(function(){  $("#"+_formId).submit(); },5);
		},5);
	}

	$(_.doc).on("change",".tpl_gateway .aps_card_token label input.tknm",function(){
		_prnt = $(this).closest('.tpl_gateway');
		_prnt.find('.aps_field_card_token').addClass('hidden').find('.fld').prop('disabled',true);
		_prnt.find('span.help-inline').remove(); 
		_prnt.find('.aps_installments_plan_code').val('');
		_prnt.find('.box_installment_plans').addClass('hidden');
		_trgt = _prnt.find('#'+$(this).data('target'));
		
		if( _trgt.length )
			_trgt.removeClass('hidden').find('.fld').prop('disabled',false);
		
		if( $(this).val() == '' || $(this).closest('.installments_linked').length )
			_prnt.find('.fld_dsbl2').prop('disabled',true);
		else
			_prnt.find('.fld_dsbl2').prop('disabled',false);
	});

	$(_.doc).on("click",".aps_gateways_list .gt-label input",function(){
		if( $(this).hasClass('pf_active') ) return false;
		$(".aps_gateways_list .pf_active").removeClass('pf_active');
		$(this).addClass('pf_active');
		$(".aps_gateways_list .tpl_gateway").hide();
		$(".aps_gateways_list .tpl_gateway .fld").prop('disabled',true);
		_obj = $("#tpl_aps_gateway_"+$(this).val().trim().replace(/[^a-z]+/g, ''));
		if( _obj.length ){
			_obj.fadeIn().find(".fld").prop('disabled',false);
			_obj.find(".fld_dsbl").prop('disabled',true);
			_obj.find(".fld_dsbl3").prop('disabled',true);
		}
	});
	
	var verifyRegx = {  
		phone_verify: /^(1\s|1|)?((\(\d{3}\))|\d{3})(\-|\s)?(\d{3})(\-|\s)?(\d{4,13})$/,
		otp_verify: /^(\d{6,10})$/,
	};

	$(_.doc).on("click",".aps_gateways_list .items_slider .item-tenure",function(){
		if( $(this).hasClass('active') ) return false;
		_gtwy = $(this).data('gateway');
		$(this).closest('.items_slider').find('.item-tenure').removeClass('active');
		$(this).addClass('active');
		
		_cntr = $(this).closest('.ty-control-group');
		if( _gtwy == 'valu' ){
			_cntr.find('input.aps_valu_tenure').val($(this).data('tenure'));
			_cntr.find('input.aps_down_payment').val($(this).data('amount'));
			_cntr.find('input.aps_valu_interest').val($(this).data('rate'));
			
		}
		_pcode = $(this).data('plan_code');
			
		if( _gtwy == 'installments' )
			_cntr.find('input.installments_plan_code').val(_pcode).prop('disabled',false);

		if( _gtwy == 'cc' ){
			_cntr.find('.fld_dsbl2,.cm-agreement').prop('disabled',false);
			_cntr.find('.cm-field-container').removeClass('hidden');
			_cntr.find('.installments_extr_field').remove();
			if( _pcode == 'FULL'){
				_cntr.find('.fld_dsbl2,.cm-agreement').prop('disabled',true);
				_cntr.find('.cm-field-container').addClass('hidden');
			} else {
				_cntr.append('<input type="hidden" class="installments_extr_field" name="payment_data[aps][target_gateway]" value="installments"/> <input type="hidden" class="installments_extr_field" name="payment_data[aps][gateway_linked]" value="cc"/>');
				_cntr.find('input.installments_issuer_code').prop('disabled',false);
				_cntr.find('input.installments_plan_code').val(_pcode).prop('disabled',false);
			}
		}
		
	});

	var loadingAjxInmlt = false;
	$(_.doc).on("input",".aps_card_token input.cvv",function(){
		_val = $(this).val().trim();
		$(this).removeClass('is-valid');
		
		if(_val.length > this.maxLength) 
			_val = _val.slice(0, this.maxLength);
		$(this).val(_val);
		
		if( _val.length == this.maxLength )
			$(this).addClass('is-valid');

		if( _val.length > this.maxLength )
			return false;
	});

	$(_.doc).on("blur",".ty-credit_card_number_installments,.installments_linked .ty-credit_card_number_cc, .installments_linked .aps_field_card_token .cvv",function(){
		var ths = $(this), _cntr = $(this).closest('.ty-control-group'), _boxTpl = $(this).closest('.tpl_gateway'), _isLinked = $(this).hasClass('ty-credit_card_number_cc') || $(this).closest('.installments_linked').hasClass('ct_type_hosted_checkout');

		setTimeout(function(){

			if( ths.hasClass('is-valid') && !loadingAjxInmlt ){
				loadingAjxInmlt = true;
				var _fdata = ths.parents('form').serialize();
				var _btype = 'c';
				if( ths.data('card_number') != undefined ){
					_cntr = ths.closest('.aps_card_token');
					_btype = 't';
					_fdata += '&payment_data[aps][installments][card_number]='+ths.data('card_number');
				}
				_cntr.find('.help-inline').remove();
				if( _isLinked )
					_fdata += '&payment_data[aps][gateway]=installments&payment_data[aps][gateway_linked]=cc';

				apsShowHideLoading(0,_.tr('loading'));
				$.ajax({ url:fn_url("amazon_payment_services.ajax?action=get_installments"), method:"POST", data:_fdata , success: function(res){
					
					apsShowHideLoading(1,'');
					loadingAjxInmlt = false;
					_cntr.find('.help-inline').remove();
					_error = res.error;
					_bip = _boxTpl.find(".box_installment_plans");
					_bip.addClass('hidden');
					_bip.find('.installments_plan_code').val('');
							
					if( _error ){
						if( !_isLinked ){
							_cntr.append('<span class="help-inline"><p>'+_error+'</p></span>');
							_cntr.find('.help-inline').hide().fadeIn();
						}
					} else {
						_boxTpl.find('.fld_dsbl').prop('disabled',false);
						if( _btype == 't' )
							_boxTpl.find(".aps_field_card_token .fld_dsbl").prop('disabled',true);

						_details = res.installment_details;

						_bip.removeClass('hidden');
						_bip.find('.issuer_name b').html(_details.issuer_name);
						_bip.find('.installments_issuer_code').val(_details.issuer_code).prop('disabled',false);
						
						_bip.find('.cm-check-agreement a').attr('href',_details.terms_and_condition);
						_bip.find('.progessing_fee_message').addClass('hidden');
						if( _details.fees_message != '')
							_bip.find('.fees_message').html(_details.fees_message).removeClass('hidden');
						_bip.find('.issuer_logo').removeClass('hidden');
						if( _details.issuer_logo != '')
							_bip.find('.issuer_logo').attr('src',_details.issuer_logo).show();
						else
							_bip.find('.issuer_logo').hide();

						var _list = [];
						if( _isLinked ){
							_list.push('<div class="item-tenure active" data-gateway="cc" data-plan_code="FULL"><p class="tenure" style="padding-top: 15px;">Proceed with</p><p class="emi" style="padding-bottom: 16px;">full amount</p></div>');
						}
						$.each(_details.plans,function(idx,pln){
							_list.push('<div class="item-tenure" data-gateway="'+(_isLinked ? 'cc' : 'installments')+'" data-plan_code="'+pln.code+'"><p class="tenure">'+pln.noi+' '+_bip.data('textMonths')+'</p> <p class="emi"><strong>'+pln.amount+'</strong> '+_bip.data('currency')+'/'+_bip.data('textMonth')+'</p> <p class="rate"><a>'+pln.fees+'% '+_bip.data('textInterest')+'</a></p></div>');
						});

						renderItemsSlider(_bip.find('.list_plans'),_list);	

					}
				}, error: function(res){ loadingAjxInmlt = false; }});
			}
		},500);
	});
	

	$(_.doc).on("click",".btn-aps-action-verify",function(){
		
		var ths = $(this), _cntr = $(this).closest('.ty-control-group'), _action = $(this).data('action'),_url = $(this).data('url'), _boxTpl = $(this).closest('.tpl_gateway');

		_boxTpl.find('.aps_action').val(_action);
		_cntr.find('.help-inline').remove();
		_error = false;
		_value = _cntr.find('input').val().trim();
		
		if( _value == '' )
			_error = _cntr.data('requiredText');
		else if( ! verifyRegx[_action].test(_value) )
			_error = _cntr.data('invalidText');

		if( !_error ){

			apsShowHideLoading(0,ths.data('txt_processing'));
			$.ajax({ url:fn_url("checkout.place_order"), method:"POST", data: ths.parents('form').serializeArray(), success: function(res){
				
				apsShowHideLoading(1,'');
				_error = false;

				if( res.params ){
					if( res.params.success ){

						_cntr.addClass('hidden');
						var target = _boxTpl.find(ths.data('target')).removeClass('hidden');
						
						if( _action == 'phone_verify' ){
							_spn = target.find('label span');

							_spn.html(( _spn.data('lang') == 'ar' ? '20'+_value+'+' : '+20'+_value));
							
							_boxTpl.find('.valu_transaction_id').val(res.params.transaction_id);
							_boxTpl.find('.aps_action').val('otp_verify');	
							_boxTpl.find('.aps_merchant_reference').val(res.params.merchant_reference);
						}

						if( _action == 'otp_verify' ){
							var _list = [];
							_boxTpl.find('.aps_action').val('');
							_boxTpl.find('.fld_dsbl').prop('disabled',false);
							$.each(res.params.tenures,function(idx,tnr){
								_list.push('<div class="item-tenure" data-gateway="valu" data-amount="'+tnr.EMI+'" data-tenure="'+tnr.TENURE+'" data-rate="'+tnr.InterestRate+'"><p class="tenure">'+tnr.TENURE+' '+target.data('textMonths')+'</p> <p class="emi"><strong>'+tnr.EMI+'</strong> '+target.data('currency')+'/'+target.data('textMonth')+'</p> <p class="rate"><a>'+tnr.InterestRate+'% '+target.data('textInterest')+'</a></p></div>');
							});

							renderItemsSlider(target.find('.list_valu_tenures'),_list);	
						}
						
						target.hide().fadeIn();

					} else 
						_error = res.params.error; 
				} else
					_error = "Invalid request"; 

				if( _error ){
					_cntr.append('<span class="help-inline"><p>'+_error+'</p></span>');
					_cntr.find('.help-inline').hide().fadeIn();
				}
			}});
		} else {
			_cntr.append('<span class="help-inline"><p>'+_error+'</p></span>');
			_cntr.find('.help-inline').hide().fadeIn();
		}
	});

	$(_.doc).on('keydown',"#tpl_aps_gateway_valu input",function(e) {
        if (e.keyCode == 13){ 
        	$(this).parent().find('.btn-aps-action-verify').trigger('click');
        	return false;
        }
    });

	$(_.doc).on('click', '[name="dispatch[checkout.place_order]"]', function() {
		var jelm = $(this), form = jelm.parents('form');

		_gwField = form.find('input[name="payment_data[aps][gateway]"]:checked');
		if( _gwField.length ){

			_slcGw = _gwField.val().trim().toLowerCase();
			if( !form.ceFormValidator('check') ) return;
		        
			if( _slcGw == 'visa' ){
				if( form.find("#aps_visa_checkout_callid").length > 0 ){
					if( form.find("#aps_visa_checkout_callid").val().trim() == '' ){
						form.find("#button_aps_visa_checkout img").trigger('click');
						return false;
					}
				}
			}

			if( _slcGw == 'apple' ){
				_ardata = form.find('.inp_applepay_request_data').val().trim();
				if( _ardata == '' ){
					form.find("#btnApsApplePay").trigger('click');
					return false;
				}
			}

			if( _slcGw == 'valu' ){
				if( !( form.find(".aps_phone_number").val().trim() != '' &&  form.find(".aps_phone_otp").val().trim() != '' ) ){
					$("html,body").animate({ scrollTop: $("#tpl_aps_gateway_valu").offset().top-40 }, 1000);
					if( form.find(".aps_phone_number").val().trim() != '' )
						form.find(".btn-aps-otp-verify").trigger('click');
					else
						form.find(".btn-aps-phone-verify").trigger('click');
					return false;
				}
			}
			
			_ifrCheck = form.find('#iframe_standard_checkout_'+_slcGw).length > 0;
			if( form.find('#tpl_aps_gateway_'+_slcGw+' .aps_card_token.ct_type_standard_checkout input.tknm').length ){ 
				_vtn = form.find('#tpl_aps_gateway_'+_slcGw+' .aps_card_token.ct_type_standard_checkout input.tknm:checked').val().trim();
				if( _vtn != ''){
					_ifrCheck = false;
				}
			}
			
			if( _ifrCheck ){
		        form.addClass('cm-ajax');
	       		$.ceEvent('on', 'ce.ajaxdone', function (elms, scripts, params, resp, responseText) {
		           	if( resp.success )
		           		renderPaymentIframe(resp.payment_id,resp.checkout_url,resp.params,_slcGw);
			    });
	       	}
       	}
   
    });

	$.fn.serializeApsForm = function() {
	    var o = {};
	    $(this).find('input[type="hidden"], input[type="text"], input[type="password"], input[type="checkbox"]:checked, input[type="radio"]:checked, select').each(function() {
	        if ($(this).attr('type') == 'hidden') { 
	            var $parent = $(this).parent();
	            var $chb = $parent.find('input[type="checkbox"][name="' + this.name.replace(/\[/g, '\[').replace(/\]/g, '\]') + '"]');
	            if ($chb != null) {
	                if ($chb.prop('checked')) return;
	            }
	        }
	        if (this.name === null || this.name === undefined || this.name === '' || $(this).prop('disabled'))
	            return;

	        var elemValue = null;
	        if ($(this).is('select'))
	            elemValue = $(this).find('option:selected').val();
	        else elemValue = this.value;
	        if (o[this.name] !== undefined) {
	            if (!o[this.name].push) {
	                o[this.name] = [o[this.name]];
	            }
	            o[this.name].push(elemValue || '');
	        } else {
	            o[this.name] = elemValue || '';
	        }
	    });
	    return o;
	}

})(Tygh, Tygh.$);
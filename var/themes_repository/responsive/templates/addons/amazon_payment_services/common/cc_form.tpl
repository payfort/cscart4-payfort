{if $card_id}
    {assign var="id_suffix" value="`$card_id`"}
{else}
    {assign var="id_suffix" value=""}
{/if}
{$have_tokens = $have_tokens|default:false}

<div class="litecheckout__item">
<div class="clearfix">
    <div class="ty-credit-card cm-cc_form_{$id_suffix}">
            <div class="ty-credit-card__control-group ty-control-group">
                <label for="credit_card_number_{$id_suffix}" class="ty-control-group__title cm-cc-number cc-number_{$id_suffix} cm-required">{__("card_number")}</label>
                <input{if !$is_active} disabled="disabled"{/if} size="35" type="text" id="credit_card_number_{$id_suffix}" name="payment_data[aps][{$card_id}][card_number]" value="" class="fld{if $have_tokens} fld_dsbl{/if} ty-credit-card__input cm-autocomplete-off ty-inputmask-bdi ty-credit_card_number_{$id_suffix}" data-mada-bins="{$gateway->getConfig('mada_bins')}" data-meeza-bins="{$gateway->getConfig('meeza_bins')}" />
                <ul class="ty-cc-icons cm-cc-icons cc-icons_{$id_suffix}">
                    <li class="ty-cc-icons__item cc-default cm-cc-default"><span class="ty-cc-icons__icon default">&nbsp;</span></li>
                    <li class="ty-cc-icons__item cm-cc-visa"><span class="ty-cc-icons__icon visa">&nbsp;</span></li>
                    <li class="ty-cc-icons__item cm-cc-visa_electron"><span class="ty-cc-icons__icon visa-electron">&nbsp;</span></li>
                    <li class="ty-cc-icons__item cm-cc-mastercard"><span class="ty-cc-icons__icon mastercard">&nbsp;</span></li>
                    <li class="ty-cc-icons__item cm-cc-amex"><span class="ty-cc-icons__icon american-express">&nbsp;</span></li>
                    <li class="ty-cc-icons__item cm-cc-mada"><span class="ty-cc-icons__icon mada">&nbsp;</span></li>
                    <li class="ty-cc-icons__item cm-cc-meeza"><span class="ty-cc-icons__icon meeza">&nbsp;</span></li>
                </ul>
            </div>

            <div class="ty-credit-card__control-group ty-control-group">
                <label for="credit_card_name_{$id_suffix}" class="ty-control-group__title cm-required">{__("cardholder_name")}</label>
                <input{if !$is_active} disabled="disabled"{/if} size="35" maxlength="30" type="text" id="credit_card_name_{$id_suffix}" name="payment_data[aps][{$card_id}][card_holder_name]" value="" class="fld{if $have_tokens} fld_dsbl{/if} cm-cc-name ty-credit-card__input" />
            </div>

            <div class="ty-credit-card__date_ccv">            
                <div class="ty-credit-card__control-group ty-control-group">
                    <label for="credit_card_month_{$id_suffix}" class="ty-control-group__title cm-cc-date cc-date_{$id_suffix} cm-cc-exp-month cm-required">{__("valid_thru")}</label>
                    <label for="credit_card_year_{$id_suffix}" class="cm-required cm-cc-date cm-cc-exp-year cc-year_{$id_suffix} hidden"></label>
                    <input{if !$is_active} disabled="disabled"{/if} type="number" id="credit_card_month_{$id_suffix}" name="payment_data[aps][{$card_id}][expiry_month]" value="" size="2" maxlength="2" class="fld{if $have_tokens} fld_dsbl{/if} ty-credit-card__input-short ty-inputmask-bdi ty-cc-number-validation"/>&nbsp;&nbsp;/&nbsp;&nbsp;<input{if !$is_active} disabled="disabled"{/if} type="number" id="credit_card_year_{$id_suffix}"  name="payment_data[aps][{$card_id}][expiry_year]" value="" size="2" maxlength="2" class="fld{if $have_tokens} fld_dsbl{/if} ty-credit-card__input-short ty-inputmask-bdi ty-cc-number-validation"/>&nbsp;
                </div>
                <div class="ty-control-group ty-credit-card__cvv-field cvv-field">
                    <label for="credit_card_cvv2_{$id_suffix}" class="ty-control-group__title cm-required cm-cc-cvv2 cc-cvv2_{$id_suffix} cm-autocomplete-off">{__("cvv2")}</label>
                    <input{if !$is_active} disabled="disabled"{/if} type="number" id="credit_card_cvv2_{$id_suffix}" name="payment_data[aps][{$card_id}][card_security_code]" value="" size="4" maxlength="3" class="fld{if $have_tokens} fld_dsbl{/if} ty-credit-card__cvv-field-input ty-inputmask-bdi ty-cc-number-validation"/>
                </div>
            </div>

            {if $gateway->getConfig('enable_tokenization') eq "Y"}
            <div class="ty-credit-card__control-group ty-control-group">
                <input type="hidden" class="fld{if $have_tokens} fld_dsbl{/if}"{if !$is_active} disabled="disabled"{/if} name="payment_data[aps][{$card_id}][remember_me]" value="NO"/>
                <label for="credit_save_card_{$id_suffix}">
                	<input{if !$is_active} disabled="disabled"{/if} type="checkbox" name="payment_data[aps][{$card_id}][remember_me]" id="credit_save_card_{$id_suffix}" class="fld{if $have_tokens} fld_dsbl{/if}" checked="checked" value="YES"/> {__("aps_save_my_card")}
                </label>
            </div>
            {/if}
    </div>
    
</div>
</div>

<script type="text/javascript">

(function(_, $) {

    
    $.ceEvent('on', 'ce.commoninit', function() {
        
        var isChromeOnOldAndroid = function() {
            var ua = navigator.userAgent;
            return (/Android/.test(ua) && /Chrome/.test(ua));
        };
                    
        var ccFormId = '{$id_suffix}';
        
        var icons           = $('.cc-icons_' + ccFormId + ' li');
        
        var ccNumber        = $(".cc-number_" + ccFormId);
        var ccNumberInput   = $("#" + ccNumber.attr("for"));
        
        var ccCv2           = $(".cc-cvv2_" + ccFormId);
        var ccCv2Input      = $("#" + ccCv2.attr("for"));
        
        var ccMonth         = $(".cc-date_" + ccFormId);
        var ccMonthInput    = $("#" + ccMonth.attr("for"));
        
        var ccYear          = $(".cc-year_" + ccFormId);
        var ccYearInput     = $("#" + ccYear.attr("for"));
        
        if(_.isTouch === false && jQuery.isEmptyObject(ccNumberInput.data("_inputmask")) == true) {
            
            if (!isChromeOnOldAndroid()) {
                ccNumberInput.inputmask("9999 9999 9999 9[9][9][9]", {
                    placeholder: '',
                    showMaskOnHover: false,
                    showMaskOnFocus: false
                });
            }

            $.ceFormValidator('registerValidator', {
                class_name: 'cc-number_' + ccFormId,
                message: '',
                func: function(id) {
                    return isChromeOnOldAndroid() || ccNumberInput.inputmask("isComplete");
                }
            });

            if (!isChromeOnOldAndroid()) {
                ccCv2Input.inputmask("999[9]", {
                    placeholder: '',
                    showMaskOnHover: false,
                    showMaskOnFocus: false
                });
            }

            $.ceFormValidator('registerValidator', {
                class_name: 'cc-cvv2_' + ccFormId,
                message: '{__("error_validator_ccv")|escape:javascript}',
                func: function(id) {
                    if( ccCv2Input.val().trim().length < ccCv2Input.attr('maxlength') )
                        return false; 
                    else
                        return isChromeOnOldAndroid() || ccNumberInput.inputmask("isComplete");
                }
            });

            $.ceFormValidator('registerValidator', {
                class_name: 'cc-cvv2_' + ccFormId,
                message: '{__("error_validator_ccv")|escape:javascript}',
                func: function(id) {
                    if( ccCv2Input.val().trim().length < ccCv2Input.attr('maxlength') )
                        return false; 
                    else
                        return isChromeOnOldAndroid() || ccNumberInput.inputmask("isComplete");
                }
            });
            
            if (!isChromeOnOldAndroid()) {
                ccMonthInput.inputmask("99", {
                    placeholder: '',
                    showMaskOnHover: false,
                    showMaskOnFocus: false
                });

                ccYearInput.inputmask("99", {
                    placeholder: '',
                    showMaskOnHover: false,
                    showMaskOnFocus: false
                });
            }

            $.ceFormValidator('registerValidator', {
                class_name: 'cc-date_' + ccFormId,
                message: '',
                func: function(id){
                    _mn = ccMonthInput.val().trim();
                    _yr = ccYearInput.val().trim();
                    _my = '20'+_yr+_mn;

                    _cmn= (new Date).getMonth()+1;
                    if( _cmn < 10 ) _cmn = "0"+_cmn;
                    _cmy = (new Date).getFullYear()+''+_cmn;
                    
                    if( parseInt(_mn) > 12 || _mn.length < 2 || _yr.length < 2 || _my < _cmy)
                        return false;
                    else
                        return isChromeOnOldAndroid() || (ccYearInput.inputmask("isComplete") && ccMonthInput.inputmask("isComplete"));
                }
            });

            $.ceFormValidator('registerValidator', {
                class_name: 'cc-year_' + ccFormId,
                message: '',
                func: function(id){
                    _mn = ccMonthInput.val().trim();
                    _yr = ccYearInput.val().trim();
                    _my = '20'+_yr+_mn;

                    _cmn= (new Date).getMonth()+1;
                    if( _cmn < 10 ) _cmn = "0"+_cmn;
                    _cmy = (new Date).getFullYear()+''+_cmn;
                    
                    if( parseInt(_mn) > 12 || _mn.length < 2 || _yr.length < 2 || _my < _cmy)
                        return false;
                    else
                        return isChromeOnOldAndroid() || (ccYearInput.inputmask("isComplete") && ccMonthInput.inputmask("isComplete"));
                }
            });
        }

        if (ccNumber.length && ccNumberInput.length) {
            ccNumberInput.validatePFCreditCard(function (result) {
                icons.removeClass('active');
                
                ccv2MaxLenth = 3;
                                                   
                if (result.card_type) {
                    
                    if( result.card_type.inputMask )
                        ccNumberInput.inputmask(result.card_type.inputMask, { placeholder: '',
                    showMaskOnHover: false,
                    showMaskOnFocus: false });

                    if( result.length_valid ) 
                        ccNumberInput.addClass('is-valid');
                    else
                        ccNumberInput.removeClass('is-valid');
                        
                    icons.filter(' .cm-cc-' + result.card_type.name).addClass('active');
                    if (['visa_electron', 'maestro', 'laser'].indexOf(result.card_type.name) != -1)
                        ccCv2.removeClass("cm-required");
                    else 
                        ccCv2.addClass("cm-required");
                    
                    if( ['amex'].indexOf(result.card_type.name) != -1 )  
                        ccv2MaxLenth = 4;
                }
                
                ccCv2Input.attr('maxlength',ccv2MaxLenth);
                ccCv2Val = ccCv2Input.val().trim();

                if (!result.length_valid) {
                    ccNumberInput.removeClass('is-valid');
                }
                if( ccCv2Val != '' && ccCv2Val > ccv2MaxLenth) 
                    ccCv2Input.val(ccCv2Val.slice(0,ccv2MaxLenth));
            });
        }
    });

})(Tygh, Tygh.$);
</script>
var payfortFort = (function () {
   return {
        translate: function(key, category, replacments) {
            if(!this.isDefined(category)) {
                category = 'payfort_fort';
            }
            var message = (arr_messages[category + '.' + key]) ? arr_messages[category + '.' + key] : key;
            if (this.isDefined(replacments)) {
                $.each(replacments, function (obj, callback) {
                    message = message.replace(obj, callback);
                });
            }
            return message;
        },
        isDefined: function(variable) {
            if (typeof (variable) === 'undefined' || typeof (variable) === null) {
                return false;
            }
            return true;
        },
        isTouchDevice: function() {
            return 'ontouchstart' in window        // works on most browsers 
                || navigator.maxTouchPoints;       // works on IE10/11 and Surface
        },
        trimString: function(str){
            return str.trim();
        },
        isPosInteger: function(data) {
            var objRegExp  = /(^\d*$)/;
            return objRegExp.test( data );
        }
   };
})();

var payfortFortMerchantPage = (function () {
    return {
        showMerchantPage: function(gatewayUrl) {
            if($("#payfort_merchant_page").size()) {
                $( "#payfort_merchant_page" ).remove();
            }
            $('<iframe  name="payfort_merchant_page" id="payfort_merchant_page"height="550px" frameborder="0" scrolling="no" onload="payfortFortMerchantPage.iframeLoaded(this)" style="display:none"></iframe>').appendTo('#pf_iframe_content');
            $('.pf-iframe-spin').show();
            $('.pf-iframe-close').hide();
            $( "#payfort_merchant_page" ).attr("src", gatewayUrl);
            $( "#payfort_payment_form" ).attr("action",gatewayUrl);
            $( "#payfort_payment_form" ).attr("target","payfort_merchant_page");
            $( "#payfort_payment_form" ).children('.cm-no-hide-input').remove();
            $( "#payfort_payment_form" ).submit();
            //fix for touch devices
            if (payfortFort.isTouchDevice()) {
                setTimeout(function() {
                    $("html, body").animate({ scrollTop: 0 }, "slow");
                }, 1);
            }
            $( "#div-pf-iframe" ).show();
        },
        closePopup: function() {
            $( "#div-pf-iframe" ).hide();
            $( "#payfort_merchant_page" ).remove();
            //window.location = 'index.php?route=payment/payfort_fort_cc/merchantPageCancel';
        },
        iframeLoaded: function(){
            $('.pf-iframe-spin').hide();
            $('.pf-iframe-close').show();
            $('#payfort_merchant_page').show();
        },
    };
})();
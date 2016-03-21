function showMerchantPage(merchantPageUrl) {
    if($("#payfort_merchant_page").size()) {
        $( "#payfort_merchant_page" ).remove();
    }
    $("#review-buttons-container .btn-checkout").hide();
    $("#review-please-wait").show();
    
    $('<iframe  name="payfort_merchant_page" id="payfort_merchant_page"height="550px" frameborder="0" scrolling="no" onload="pfIframeLoaded(this)" style="display:none"></iframe>').appendTo('#pf_iframe_content');
    $('.pf-iframe-spin').show();
    $('.pf-iframe-close').hide();
    $( "#payfort_merchant_page" ).attr("src", merchantPageUrl);
    $( "#payfort_payment_form" ).attr("action", merchantPageUrl);
    $( "#payfort_payment_form" ).attr("target","payfort_merchant_page");
    $( "#payfort_payment_form" ).children('.cm-no-hide-input').remove();
    $( "#payfort_payment_form" ).submit();
    $( "#div-pf-iframe" ).show();
}

function pfClosePopup() {
    $( "#div-pf-iframe" ).hide();
    $( "#payfort_merchant_page" ).remove();
    //window.location = $( "#payfort_cancel_url" ).val();
}
function pfIframeLoaded(ele) {
    $('.pf-iframe-spin').hide();
    $('.pf-iframe-close').show();
    $('#payfort_merchant_page').show();
}
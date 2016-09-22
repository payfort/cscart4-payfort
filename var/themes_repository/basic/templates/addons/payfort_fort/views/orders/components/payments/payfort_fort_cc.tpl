{if isset($payment_method.processor_params.integration_type) and $payment_method.processor_params.integration_type eq 'merchantPage'}
{script src="js/addons/payfort_fort/payfort_fort.js"}
{style src="addons/payfort_fort/checkout.css"}
<div class="pf-iframe-background" id="div-pf-iframe" style="display:none">
    <div class="pf-iframe-container">
        <span class="pf-close-container">/
        <i class="ty-icon-cancel-circle  pf-iframe-close" onclick="payfortFortMerchantPage.closePopup()"></i>
        </span>
        <!--<i class="fa fa-spinner fa-spin pf-iframe-spin"></i>-->
        <div class="ty-ajax-loading-box pf-iframe-spin"></div>
        <div class="pf-iframe" id="pf_iframe_content">
        </div>
    </div>
</div>
<script type="text/javascript">
    $('#place_order_{$tab_id}').click(function(e){
        e.preventDefault();
        $.ceAjax("request", "{'payfort_fort_cc.get_merchant_page_data'|fn_url}", {
            method: "post",
            data: {
                payment_id: "{$payment_id}"
               
            },
            callback: function(response) {
                var data = JSON.parse(response.text);
                if (data.params) {
                    $('#payfort_payment_form').remove();
                    $('<form name="payfort_payment_form" id="payfort_payment_form" style="display:none" method="post"></form>').appendTo('body');
                    $.each(data.params, function(k, v){
                        $('<input>').attr({
                            type: 'hidden',
                            id: k,
                            name: k,
                            value: v
                        }).appendTo('#payfort_payment_form'); 
                    });
                    payfortFortMerchantPage.showMerchantPage(data.url);
                }
                else {
                    location.reload();
                }
            }
        });
    });
</script>
{elseif isset($payment_method.processor_params.integration_type) and $payment_method.processor_params.integration_type eq 'merchantPage2'}
    {include file="addons/payfort_fort/views/orders/components/payments/merchant_page2.tpl" payment_id=$payment_id}
{/if}

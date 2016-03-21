{if isset($payment_method.processor_params.integration_type) and $payment_method.processor_params.integration_type eq 'merchantPage'}
{script src="js/addons/payfort_fort/checkout.js"}
{style src="addons/payfort_fort/checkout.css"}
<div class="pf-iframe-background" id="div-pf-iframe" style="display:none">
    <div class="pf-iframe-container">
        <span class="pf-close-container">
        <i class="ty-icon-cancel-circle  pf-iframe-close" onclick="pfClosePopup()"></i>
        </span>
        <!--<i class="fa fa-spinner fa-spin pf-iframe-spin"></i>-->
        <div class="ty-ajax-loading-box pf-iframe-spin"></div>
        <div class="pf-iframe" id="pf_iframe_content">
        </div>
    </div>
</div>
<script type="text/javascript">
    (function(_, $) {
        $(_.doc).on('click', '#place_order_{$tab_id}', function(e) {
            e.preventDefault();
            var jelm = $(this),
            form = jelm.parents('form');

            if (!form.ceFormValidator('check')) {
                return;
            }
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
                        showMerchantPage(data.url);
                    }
                    else {
                        location.reload();
                    }
                }
            });
            return false;
        });
        /*$('#place_order_{$tab_id}').on('click',function(e){
           
        });*/
    })(Tygh, Tygh.$);
    
</script>
{/if}

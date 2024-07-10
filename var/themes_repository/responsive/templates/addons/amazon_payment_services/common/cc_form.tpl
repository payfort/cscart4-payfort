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
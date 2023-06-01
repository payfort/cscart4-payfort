{$sections = fn_amazon_payment_services_get_configuration_fields()}
<style type="text/css">
    .select2-selection.select2-selection--multiple{ height: 25px; }
    .select2-container .select2-search--inline{ width:100% !important; }
</style>
{foreach from=$sections item="fields" key="title"}

    {include file="common/subheader.tpl" title=$title target="#aps_`$title|md5`"}
    <div id="{"aps_`$title|md5`"}" class="collapse in">
        
        {foreach from=$fields item="field" key="field_name"}
        <div class="control-group">

            <label class="control-label cm-trim" for="aps_field_{$field_name}">{$field.label}:</label>
            <div class="controls">
                {if empty($field.default)}{$field.default = ''}{/if}
                {if $field.default === true}{$field.default = 'Y'}{/if}

                {$value = $processor_params.$field_name|default:$field.default}
                {$type = $field.type|default:'text'|strtolower}

                {if $type eq 'dropdown'}

                    <select name="payment_data[processor_params][{$field_name}]{if $field.multiple}[]" class="cm-object-picker ffcontrol" style="max-width:400px"  multiple="multiple{/if}" id="aps_field_{$field_name}">
                        {foreach from=$field.values|default:[] item="val" key="vk"}
                            <option value="{$vk}"{if $vk eq $value} selected="selected"{/if}>{$val}</option>
                        {/foreach}
                    </select>
                {elseif $type eq 'checkboxes'}
                    <input type="hidden" name="payment_data[processor_params][{$field_name}]" value="N"/>
                    {if empty($value)}{$value = []}{/if}
                    {foreach from=$field.values|default:[] item="val" key="vk"}
                    <label class="checkbox inline" for="elm_chb_{$field_name}_{$vk}">
                        <input type="checkbox" name="payment_data[processor_params][{$field_name}][{$vk}]" id="elm_chb_{$field_name}_{$vk}"{if is_array($value) && isset($value[$vk]) && $value[$vk] eq "Y"} checked="checked"{/if} value="Y"/>
                        {$val}
                    </label>
                    {/foreach}
                {elseif $type eq 'checkbox'}
                    <input type="hidden" name="payment_data[processor_params][{$field_name}]" value="N"/>
                    <label class="checkbox inline" for="elm_chb_{$field_name}">
                        <input type="checkbox" name="payment_data[processor_params][{$field_name}]" id="elm_chb_{$field_name}"{if $value eq "Y"} checked="checked"{/if} value="Y"/>
                    </label>
                {elseif $type eq 'file'}
                    <input type="hidden" name="payment_data[processor_params][{$field_name}]" value="{$value}" />
                    {if !empty($value)}<b>{$value|pathinfo:$smarty.const.PATHINFO_BASENAME}</b><br>{/if}
                    {include file="common/fileuploader.tpl" var_name="payment_gw_certi_files[{$field_name}]" is_image=false}
                {elseif $type eq 'textarea'}
                    <textarea name="payment_data[processor_params][{$field_name}]" id="aps_field_{$field_name}" col="55" rows="3" style="width:99%">{$value}</textarea>
                
                {elseif $type eq 'html'}
                    <div style="padding-top: 5px;">{$field.html|default:'' nofilter}</div>
                {else}
                    <input type="{$type}" name="payment_data[processor_params][{$field_name}]" id="aps_field_{$field_name}"  value="{$value}"{if $type eq 'number'} onchange="if(this.value < 0){ this.value = 0; } if(this.value > 99999){ this.value = 99999; }"{/if}/>
                {/if}
                {if !empty($field.hint)}<p class="muted description"{if $type eq 'checkbox'} style="display: inline-block;padding: 6px 0 0 0; vertical-align:top;"{/if}>{$field.hint nofilter}</p>{/if}
            </div>

        </div>
        {/foreach}

    </div>

{/foreach}
{capture name="mainbox"}

{if empty($content)}
    <p class="no-items">{__("no_data")}</p>
{else}
    <textarea style="width:100%; max-width: 100%; padding: 0px; border: none; margin: 10px;min-height: 600px">{$content}</textarea>
{/if}

{capture name="adv_buttons"}
{if !empty($files)}
<select style="width: 150px;margin: 0;" onchange="window.location.href='{"amazon_payment_services.logs"|fn_url}&date='+this.value;">
    {foreach from=$files item="file"}
        <option value="{$file}"{if $file_slc eq $file} selected="selected"{/if}>{$file}</option>
    {/foreach}
</select>
{/if}
{/capture}

{capture name="buttons"}{/capture}

{/capture}

{include file="common/mainbox.tpl" title="{__("amazon_payment_services")} - {__("logs")}" content=$smarty.capture.mainbox  buttons=$smarty.capture.buttons adv_buttons=$smarty.capture.adv_buttons sidebar="" content_id="view_logs"}
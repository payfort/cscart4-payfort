<div class="control-group">
    <label class="control-label" for="language">{__("language")}:</label>
    <div class="controls">
        <select name="payfort_fort_settings[payment_settings][language]" id="language">
            <option value="store"{if $payfort_fort_settings.payment_settings.language eq "store"} selected="selected"{/if}>{__("store_language")}</option>
            <option value="en"{if $payfort_fort_settings.payment_settings.language eq "en"} selected="selected"{/if}>{__("english")}</option>
            <option value="ar"{if $payfort_fort_settings.payment_settings.language eq "ar"} selected="selected"{/if}>{__("arabic")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="merchant_identifier">{__("merchant_identifier")}:</label>
    <div class="controls">
        <input type="text" name="payfort_fort_settings[payment_settings][merchant_identifier]" id="merchant_identifier" size="60" value="{$payfort_fort_settings.payment_settings.merchant_identifier}" >
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="access_code">{__("access_code")}:</label>
    <div class="controls">
        <input type="text" name="payfort_fort_settings[payment_settings][access_code]" id="access_code" size="60" value="{$payfort_fort_settings.payment_settings.access_code}" >
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="command">{__("command")}:</label>
    <div class="controls">
        <select name="payfort_fort_settings[payment_settings][command]" id="command">
            <option value="PURCHASE"{if $payfort_fort_settings.payment_settings.command eq "PURCHASE"} selected="selected"{/if}>{__("purchase")}</option>
            <option value="AUTHORIZATION"{if $payfort_fort_settings.payment_settings.command eq "AUTHORIZATION"} selected="selected"{/if}>{__("authorization")}</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="hash_algorithm">{__("hash_algorithm")}:</label>
    <div class="controls">
        <select name="payfort_fort_settings[payment_settings][hash_algorithm]" id="hash_algorithm">
            <option value="sha1"{if $payfort_fort_settings.payment_settings.hash_algorithm eq "sha1"} selected="selected"{/if}>sha-1</option>
            <option value="sha256"{if $payfort_fort_settings.payment_settings.hash_algorithm eq "sha256"} selected="selected"{/if}>sha-256</option>
            <option value="sha512"{if $payfort_fort_settings.payment_settings.hash_algorithm eq "sha512"} selected="selected"{/if}>sha-512</option>
        </select>
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="sha_in_pass_phrase">{__("request_sah_phrase")}:</label>
    <div class="controls">
        <input type="text" name="payfort_fort_settings[payment_settings][sha_in_pass_phrase]" id="sha_in_pass_phrase" size="60" value="{$payfort_fort_settings.payment_settings.sha_in_pass_phrase}" >
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="sha_out_pass_phrase">{__("response_sah_phrase")}:</label>
    <div class="controls">
        <input type="text" name="payfort_fort_settings[payment_settings][sha_out_pass_phrase]" id="order_sha_out_pass_phrase" size="60" value="{$payfort_fort_settings.payment_settings.sha_out_pass_phrase}" >
    </div>
</div>

<div class="control-group">
    <label class="control-label" for="mode">{__("mode")}:</label>
    <div class="controls">
        <select name="payfort_fort_settings[payment_settings][mode]" id="mode">
            <option value="sandbox"{if $payfort_fort_settings.payment_settings.mode eq "sandbox"} selected="selected"{/if}>{__("sandbox")}</option>
            <option value="live"{if $payfort_fort_settings.payment_settings.mode eq "live"} selected="selected"{/if}>{__("live")}</option>
        </select>
    </div>
</div>
{assign var="payfort_fort_host_url" value=fn_payfort_fort_get_host_to_host_url()}
<div class="control-group">
    <strong class="control-label">{__('host_to_host_url')}:</strong>
    <div class="controls">
        <strong style="float: left; padding-top: 5px;">{$payfort_fort_host_url}</strong>
    </div>
</div>
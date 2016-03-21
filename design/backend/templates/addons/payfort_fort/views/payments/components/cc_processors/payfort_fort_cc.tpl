<div class="control-group">
    <label class="control-label" for="integration_type">{__("integration_type")}:</label>
    <div class="controls">
        <select name="payment_data[processor_params][integration_type]" id="language">
            <option value="redirection"{if $processor_params.integration_type eq "redirection"} selected="selected"{/if}>{__("redirection")}</option>
            <option value="merchantPage"{if $processor_params.integration_type eq "merchantPage"} selected="selected"{/if}>{__("merchant_page")}</option>
        </select>
    </div>
</div>
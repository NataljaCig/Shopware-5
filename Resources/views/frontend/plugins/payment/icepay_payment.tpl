<div class="payment--form-group">
    {if $sIssuers && $sIssuers[$payment_mean.name]}
        <div class="select-field">
            <select class="issuer--selection" id="sIcepayIssuer{$payment_mean.id}" name="sIcepayIssuer{$payment_mean.id}" {if $payment_mean.id == $form_data.payment}required="required" aria-required="true"{/if}>
                <option value="">{s name="IcepayIssuerSelect"}{/s}</option>
                {foreach from=$sIssuers[$payment_mean.name] item=issuer}
                    <option value="{$issuer.key}">{$issuer.description}</option>
                {/foreach}
            </select>
        </div>
    {/if}
</div>
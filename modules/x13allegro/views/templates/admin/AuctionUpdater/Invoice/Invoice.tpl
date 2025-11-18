<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Opcja faktury' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select name="allegro_invoice">
            {foreach $data.invoiceOptions as $value => $name}
                <option value="{$value}" {if $value == $data.defaultInvoiceOption}selected="selected"{/if}>{$name}</option>
            {/foreach}
        </select>
    </div>
</div>

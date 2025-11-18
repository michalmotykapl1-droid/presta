<div class="row">
    <div class="col-md-4">
        <label for="return_policy" class="control-label t">Warunki zwrotów</label>
        <select id="return_policy" name="return_policy" class="" {if empty($afterSales['returnPolicies'])}disabled="disabled"{/if}>
            <option value="">-- Wybierz --</option>
            {foreach $afterSales['returnPolicies'] as $returnPolicy}
                <option value="{$returnPolicy->id}" {if $returnPolicy->id == $account->return_policy}selected="selected"{/if}>{$returnPolicy->name}</option>
            {/foreach}
        </select>
    </div>

    <div class="col-md-4">
        <label for="implied_warranty" class="control-label t">Reklamacja</label>
        <select id="implied_warranty" name="implied_warranty" class="" {if empty($afterSales['impliedWarranties'])}disabled="disabled"{/if}>
            <option value="">-- Wybierz --</option>
            {foreach $afterSales['impliedWarranties'] as $impliedWarranty}
                <option value="{$impliedWarranty->id}" {if $impliedWarranty->id == $account->implied_warranty}selected="selected"{/if}>{$impliedWarranty->name}</option>
            {/foreach}
        </select>
    </div>

    <div class="col-md-4">
        <label for="warranty" class="control-label t">Gwarancja (opcjonalnie)</label>
        <select id="warranty" name="warranty" class="" {if empty($afterSales['warranties'])}disabled="disabled"{/if}>
            <option value="">-- Wybierz --</option>
            {foreach $afterSales['warranties'] as $warranty}
                <option value="{$warranty->id}" {if $warranty->id == $account->warranty}selected="selected"{/if}>{$warranty->name}</option>
            {/foreach}
        </select>
    </div>
</div>

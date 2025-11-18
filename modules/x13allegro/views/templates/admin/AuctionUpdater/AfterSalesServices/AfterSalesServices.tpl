<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Warunki zwrotów' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select name="allegro_return_policy">
            <option value="0">{l s='nie zmieniaj' mod='x13allegro'}</option>
            {foreach $data.returnPolicies as $returnPolicies}
                <option value="{$returnPolicies->id}" {if $returnPolicies->id == $data.defaultReturnPolicies}selected="selected"{/if}>{$returnPolicies->name}</option>
            {/foreach}
        </select>
    </div>
</div>

<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Reklamacje' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select name="allegro_implied_warranty">
            <option value="0">{l s='nie zmieniaj' mod='x13allegro'}</option>
            {foreach $data.impliedWarranties as $impliedWarranties}
                <option value="{$impliedWarranties->id}" {if $impliedWarranties->id == $data.defaultImpliedWarranties}selected="selected"{/if}>{$impliedWarranties->name}</option>
            {/foreach}
        </select>
    </div>
</div>

<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Gwarancje' mod='x13allegro'} ({l s='opcjonalnie' mod='x13allegro'})
    </label>
    <div class="col-lg-9">
        <select name="allegro_warranty">
            <option value="0">{l s='nie zmieniaj' mod='x13allegro'}</option>
            {foreach $data.warranties as $warranties}
                <option value="{$warranties->id}" {if $warranties->id == $data.defaultWarranties}selected="selected"{/if}>{$warranties->name}</option>
            {/foreach}
            <option value="-1">{l s='usuń gwarancję' mod='x13allegro'}</option>
        </select>
    </div>
</div>

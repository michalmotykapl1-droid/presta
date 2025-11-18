<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Metoda aktualizacji' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select name="allegro_auction_additional_marketplace_method">
            <option value="allegro_auction_additional_marketplace_add">{l s='Aktywuj widoczność na rynkach zagranicznych' mod='x13allegro'}</option>
            <option value="allegro_auction_additional_marketplace_delete">{l s='Usuń widoczność z rynków zagranicznych' mod='x13allegro'}</option>
        </select>
    </div>
</div>
<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Rynki zagraniczne' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        {foreach $data.marketplaces as $marketplace}
            {if $marketplace.id == $data.baseMarketplace}
                {continue}
            {/if}

            <p class="checkbox clearfix">
                <label for="allegro_auction_additional_marketplace_{$marketplace.id}">
                    <input type="checkbox" id="allegro_auction_additional_marketplace_{$marketplace.id}" name="allegro_auction_additional_marketplace[]" value="{$marketplace.id}">
                    {$marketplace.name|regex_replace:"/^(\w+)/u":"<b>$1</b>"}{if $marketplace.id != $data.baseMarketplace}&nbsp;&nbsp;
                    <i>({l s='kurs wymiany waluty' mod='x13allegro'}: {$marketplace.currencyConversionRate} {$data.marketplaces[$data.baseMarketplace].currencyIso})</i>{/if}
                </label>
            </p>
        {/foreach}
    </div>
</div>

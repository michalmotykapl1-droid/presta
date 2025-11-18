<div class="form-group clearfix">
    <label for="xallegro_sync_price" class="control-label form-control-label col-lg-3">
        <span>{l s='Aktualizuj cenę na Allegro' mod='x13allegro'}</span>
    </label>
    <div class="col-lg-3">
        <select class="custom-select" name="xallegro_sync_price" id="xallegro_sync_price">
            <option value="" {if $syncPrice === null}selected="selected"{/if}>{l s='użyj ustawienia z wszystkich kont (jeśli istnieje) lub globalnej opcji konta' mod='x13allegro'}</option>
            {foreach $syncPriceList as $key => $value}
                <option value="{$value.id}" {if $syncPrice !== null && $syncPrice == $value.id}selected="selected"{/if}>{$value.name}</option>
            {/foreach}
        </select>
        <div class="help-block form-text">
            {l s='Domyśle ustawienie dla wybranego konta' mod='x13allegro'}: {$syncPriceList[$syncPriceDefault]['name']}
        </div>
    </div>

</div>

<div class="form-group clearfix">
    <div class="col-lg-7">
        <table class="table">
            <thead>
                <tr>
                    <th scope="col">{l s='Ceny indywidualne' mod='x13allegro'}</th>
                    <th scope="col" style="width: 100px;">{l s='Cena/wpływ' mod='x13allegro'}</th>
                    <th scope="col" style="width: 225px;">{l s='Typ modyfikacji' mod='x13allegro'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach $prices as $row}
                    <tr>
                        <td>
                            {if !$row.id_product_attribute}
                                <a title="{l s='Zmiana ceny dla produktu głównego spowoduje narzucenie wpływu na wszystkie jego kombinacje (jeśli istnieją)' mod='x13allegro'}" class="xallegro-custom-price-helper">
                                    {if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>=')}<i class="material-icons">help_outline</i>{else}<i class="icon icon-question-circle" aria-hidden="true"></i>{/if}
                                </a>
                            {/if}

                            {$row.label}

                            {if !$row.id_product_attribute}
                                (produkt główny)
                            {/if}
                        </td>
                        <td>
                            <input {if $row.disabled}readonly{/if} type="text" class="form-control width-xs allegro-price-input{if $row.id_product_attribute} with-combinations{else} wo-combinations{/if}" name="xallegro_custom_price[{$row.id_product_attribute}][value]" value="{$row.value}" data-cast="float" data-cast-unsigned="{if $row.method == $impactMethodPrice}false{else}true{/if}">
                        </td>
                        <td>
                            <select name="xallegro_custom_price[{$row.id_product_attribute}][method]" class="form-control allegro-price-method{if $row.id_product_attribute} with-combinations{else} wo-combinations{/if} {if $row.disabled}readonly{/if}">
                                <option {if $row.method == $impactMethodPrice}selected{/if} value="{$impactMethodPrice}">{l s='Cena końcowa' mod='x13allegro'}</option>
                                <option {if $row.method == $impactMethodAmount}selected{/if} value="{$impactMethodAmount}">{l s='Wpływ na cenę (wartość)' mod='x13allegro'}</option>
                                <option {if $row.method == $impactMethodPercentage}selected{/if} value="{$impactMethodPercentage}">{l s='Wpływ na cenę (procent)' mod='x13allegro'}</option>
                            </select>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>

        <p class="text-muted">
            <button class="btn btn-default" id="xallegro_custom_prices_delete">
                {l s='Usuń wszystkie ceny indywidualne dla tego produktu' mod='x13allegro'}
            </button>
        </p>
    </div>
</div>

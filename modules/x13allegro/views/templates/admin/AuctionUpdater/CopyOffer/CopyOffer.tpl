{if empty($data.accounts)}
    <div class="alert alert-info">
        {l s='Nie posiadasz kont Allegro na które można skopiować wybrane oferty' mod='x13allegro'}
    </div>
{else}
    <div class="form-group row">
        <label for="allegro_copy_to_account" class="control-label col-lg-3">
            {l s='Wybierz konto docelowe' mod='x13allegro'}
        </label>
        <div class="col-lg-9">
            <select id="allegro_copy_to_account" name="allegro_copy_to_account">
                <option value="0">{l s='-- wybierz konto --' mod='x13allegro'}</option>
                {foreach $data.accounts as $account}
                    <option value="{$account.accountId}">{$account.accountName}</option>
                {/foreach}
            </select>
        </div>
    </div>
    <div class="form-group row" style="display: none;">
        <label for="allegro_copy_delivery_profile" class="control-label col-lg-3">
            {l s='Profil dostawy' mod='x13allegro'}
        </label>
        <div class="col-lg-9">
            <select id="allegro_copy_delivery_profile" name="allegro_copy_delivery_profile">
                <option value="0">{l s='zostaw ustawiony profil dostawy' mod='x13allegro'}</option>
                {foreach $data.deliveryProfiles as $deliveryProfile}
                    <option value="{$deliveryProfile.id}">{$deliveryProfile.name}</option>
                {/foreach}
            </select>
        </div>
    </div>
    <div class="form-group row" style="display: none;">
        <label for="allegro_copy_prices" class="control-label col-lg-3">
            {l s='Cena Kup teraz' mod='x13allegro'}
        </label>
        <div class="col-lg-9">
            <select id="allegro_copy_prices" name="allegro_copy_prices">
                <option value="0">{l s='zostaw aktualną cenę' mod='x13allegro'}</option>
                <option value="1">{l s='przelicz cenę na podstawie powiązanego produktu i ustawień konta' mod='x13allegro'}</option>
            </select>
            <div class="help-block">{l s='Jeśli oferta nie jest powiązana z produktem w sklepie, zostanie przeniesiona zawsze aktualna cena' mod='x13allegro'}</div>
        </div>
    </div>
    <div class="form-group row" style="display: none;">
        <label for="allegro_copy_keep_promotion" class="control-label col-lg-3">
            {l s='Opcje promowania' mod='x13allegro'}
        </label>
        <div class="col-lg-9">
            <select id="allegro_copy_keep_promotion" name="allegro_copy_keep_promotion">
                <option value="1">{l s='zostaw ustawione opcje promowania' mod='x13allegro'}</option>
                <option value="0">{l s='usuń opcje promowania' mod='x13allegro'}</option>
            </select>
        </div>
    </div>

    {foreach $data.accounts as $accountId => $account}
        <div class="allegro-copy-account-settings" data-id="{$accountId}" style="display: none;">
            <div class="form-group row">
                <div class="col-lg-4">
                    <label for="allegro_copy_shipping_rate_{$accountId}" class="control-label required">
                        {l s='Cennik dostawy' mod='x13allegro'}
                    </label>
                    <select id="allegro_copy_shipping_rate_{$accountId}" name="allegro_copy_shipping_rate[{$accountId}]">
                        <option value="0">{l s='-- wybierz --' mod='x13allegro'}</option>
                        {if $account.shippingRateMapAvailable}
                            <option value="shipping_rate_map">{l s='-- skopiuj cennik według mapowania --' mod='x13allegro'}</option>
                        {/if}
                        {foreach $account.shippingRates as $shippingRate}
                            <option value="{$shippingRate->id}" {if $shippingRate->id == $account.shippingRateDefaultId}selected="selected"{/if}>{$shippingRate->name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-lg-4">
                    <label for="allegro_copy_return_policy_{$accountId}" class="control-label required">
                        {l s='Warunki zwrotów' mod='x13allegro'}
                    </label>
                    <select id="allegro_copy_return_policy_{$accountId}" name="allegro_copy_return_policy[{$accountId}]">
                        <option value="0">{l s='-- wybierz --' mod='x13allegro'}</option>
                        {foreach $account.afterSaleServices.returnPolicies as $returnPolicy}
                            <option value="{$returnPolicy->id}" {if $returnPolicy->id == $account.returnPolicyDefaultId}selected="selected"{/if}>{$returnPolicy->name}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-lg-4">
                    <label for="allegro_copy_implied_warranty_{$accountId}" class="control-label required">
                        {l s='Reklamacje' mod='x13allegro'}
                    </label>
                    <select id="allegro_copy_implied_warranty_{$accountId}" name="allegro_copy_implied_warranty[{$accountId}]">
                        <option value="0">{l s='-- wybierz --' mod='x13allegro'}</option>
                        {foreach $account.afterSaleServices.impliedWarranties as $impliedWarranty}
                            <option value="{$impliedWarranty->id}" {if $impliedWarranty->id == $account.impliedWarrantyDefaultId}selected="selected"{/if}>{$impliedWarranty->name}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-lg-4">
                    <label for="allegro_copy_warranty_{$accountId}" class="control-label">
                        {l s='Gwarancje' mod='x13allegro'} ({l s='opcjonalnie' mod='x13allegro'})
                    </label>
                    <select id="allegro_copy_warranty_{$accountId}" name="allegro_copy_warranty[{$accountId}]">
                        <option value="0">{l s='-- wybierz --' mod='x13allegro'}</option>
                        {foreach $account.afterSaleServices.warranties as $warranty}
                            <option value="{$warranty->id}" {if $warranty->id == $account.warrantyDefaultId}selected="selected"{/if}>{$warranty->name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
            <div class="form-group row">
                <div class="col-lg-4">
                    <label for="allegro_copy_responsible_producer_{$accountId}" class="control-label" style="margin-top: 20px;">
                        {l s='Dane producenta - GPSR' mod='x13allegro'}
                    </label>
                    <select id="allegro_copy_responsible_producer_{$accountId}" name="allegro_copy_responsible_producer[{$accountId}]">
                        <option value="0">{l s='-- wybierz --' mod='x13allegro'}</option>
                        {if $data.x13gpsrInstalled}<option value="x13gpsr">{l s='-- przypisz według powiązań z modułu X13 GPSR --' mod='x13allegro'}</option>{/if}
                        {foreach $account.responsibleProducers as $responsibleProducer}
                            <option value="{$responsibleProducer->id}">{$responsibleProducer->name}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-lg-4">
                    <label for="allegro_copy_responsible_person_{$accountId}" class="control-label">
                        {l s='Osoba odpowiedzialna za zgodność produktu - GPSR' mod='x13allegro'}
                    </label>
                    <select id="allegro_copy_responsible_person_{$accountId}" name="allegro_copy_responsible_person[{$accountId}]">
                        <option value="0">{l s='-- wybierz --' mod='x13allegro'}</option>
                        {if $data.x13gpsrInstalled}<option value="x13gpsr">{l s='-- przypisz według powiązań z modułu X13 GPSR --' mod='x13allegro'}</option>{/if}
                        {foreach $account.responsiblePersons as $responsiblePerson}
                            <option value="{$responsiblePerson->id}">{$responsiblePerson->name}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-lg-12 help-block">
                    {l s='Jeśli na kopiowanej ofercie została uzupełniona deklaracja "Produkt wprowadzony do obrotu na terenie Unii Europejskiej przed 13 grudnia 2024 r.", dane producenta i osoba odpowiedzialna nie są wymagane' mod='x13allegro'}
                </div>
            </div>
            <div class="form-group row">
                <div class="col-lg-4">
                    <label for="allegro_copy_wholesale_price_{$accountId}" class="control-label">
                        {l s='Cennik hurtowy' mod='x13allegro'}
                    </label>
                    <select id="allegro_copy_wholesale_price_{$accountId}" name="allegro_copy_wholesale_price[{$accountId}]">
                        <option value="0">{l s='-- wybierz --' mod='x13allegro'}</option>
                        {foreach $account.wholesalePriceList as $wholesalePrice}
                            <option value="{$wholesalePrice->id}">{$wholesalePrice->benefits[0]->specification->name}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-lg-4">
                    <label for="allegro_copy_size_table_{$accountId}" class="control-label">
                        {l s='Tabela rozmiarów' mod='x13allegro'}
                    </label>
                    <select id="allegro_copy_size_table_{$accountId}" name="allegro_copy_size_table[{$accountId}]">
                        <option value="0">{l s='-- wybierz --' mod='x13allegro'}</option>
                        {foreach $account.sizeTables as $sizeTable}
                            <option value="{$sizeTable->id}">{$sizeTable->name}</option>
                        {/foreach}
                    </select>
                </div>
                <div class="col-lg-4">
                    <label for="allegro_copy_additional_services_{$accountId}" class="control-label">
                        {l s='Grupa dodatkowych usług' mod='x13allegro'}
                    </label>
                    <select id="allegro_copy_additional_services_{$accountId}" name="allegro_copy_additional_services[{$accountId}]">
                        <option value="0">{l s='-- wybierz --' mod='x13allegro'}</option>
                        {foreach $account.additionalServices as $additionalService}
                            <option value="{$additionalService->id}">{$additionalService->name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
        </div>
    {/foreach}
{/if}

<script>
    $(document).on('change', '#allegro_copy_to_account', function () {
        var accountId = parseInt($(this).val());
        var $deliveryProfiles = $('#allegro_copy_delivery_profile').closest('.form-group');
        var $prices = $('#allegro_copy_prices').closest('.form-group');
        var $keepPromotion = $('#allegro_copy_keep_promotion').closest('.form-group');

        if (accountId) {
            $deliveryProfiles.slideDown();
            $prices.slideDown();
            $keepPromotion.slideDown();

            $('.allegro-copy-account-settings').slideUp({
                complete: function () {
                    $('.allegro-copy-account-settings[data-id="' + accountId + '"]').slideDown();
                }
            });
        } else {
            $deliveryProfiles.slideUp();
            $prices.slideUp();
            $keepPromotion.slideUp();
            $('.allegro-copy-account-settings').slideUp();
        }
    });
</script>

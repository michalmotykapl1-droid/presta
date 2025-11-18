<form action="{$formAction}" method="post" class="form-horizontal">
    <input type="hidden" name="id_xallegro_account" value="{$accountId}">
    <input type="hidden" name="submitAddxallegro_account" value="1">

    <div class="form-group row">
        <label for="configuration_id_language" class="control-label required col-lg-5">{l s='Język wystawianych ofert' mod='x13allegro'}</label>
        <div class="col-lg-7">
            <select id="configuration_id_language" name="id_language" class="fixed-width-xxl">
                {foreach $shopLanguages as $language}
                    <option value="{$language.id}" {if isset($language.isMarketplaceLanguage) && $language.isMarketplaceLanguage}selected="selected"{/if}>{$language.name}</option>
                {/foreach}
            </select>
        </div>
    </div>

    {if count($afterSaleServices.returnPolicies) > 1}
        <div class="form-group row">
            <label for="configuration_return_policy" class="control-label col-lg-5">{l s='Warunki zwrotów' mod='x13allegro'}</label>
            <div class="col-lg-7">
                <select id="configuration_return_policy" name="return_policy" class="fixed-width-xxl">
                    {foreach $afterSaleServices.returnPolicies as $returnPolicy}
                        <option value="{$returnPolicy.id}">{$returnPolicy.name}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    {/if}

    {if count($afterSaleServices.impliedWarranties) > 1}
        <div class="form-group row">
            <label for="configuration_implied_warranty" class="control-label col-lg-5">{l s='Reklamacje' mod='x13allegro'}</label>
            <div class="col-lg-7">
                <select id="configuration_implied_warranty" name="implied_warranty" class="fixed-width-xxl">
                    {foreach $afterSaleServices.impliedWarranties as $impliedWarranty}
                        <option value="{$impliedWarranty.id}">{$impliedWarranty.name}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    {/if}

    {if count($afterSaleServices.warranties) > 1}
        <div class="form-group row">
            <label for="configuration_warranty" class="control-label col-lg-5">{l s='Gwarancje' mod='x13allegro'} ({l s='opcjonalnie' mod='x13allegro'})</label>
            <div class="col-lg-7">
                <select id="configuration_warranty" name="warranty" class="fixed-width-xxl">
                    {foreach $afterSaleServices.warranties as $warranty}
                        <option value="{$warranty.id}">{$warranty.name}</option>
                    {/foreach}
                </select>
            </div>
        </div>
    {/if}

    <div class="form-group row">
        <div class="col-lg-12">
            <a href="{$cancelAction}" class="button btn btn-default">{l s='Anuluj autoryzacje' mod='x13allegro'}</a>
            <button type="submit" id="allegroAuthButtonSave" class="button btn btn-primary disabled" disabled="disabled">{l s='Zapisz i zakończ' mod='x13allegro'}</button>
        </div>
    </div>
</form>

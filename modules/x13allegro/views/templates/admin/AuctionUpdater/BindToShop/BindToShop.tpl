{if !$data.isShopContext}
    <div class="form-group row">
        <div class="col-lg-12">
            <div class="alert alert-warning">
                {l s='Wybierz konkretny kontekst sklepu aby powiązać ofertę z PrestaShop' mod='x13allegro'}
            </div>
        </div>
    </div>
{else}
    <div class="form-group row">
        <label class="control-label col-lg-3">
            {l s='Metoda wyszukiwania powiązania w PrestaShop' mod='x13allegro'}
        </label>
        <div class="col-lg-9">
            <select name="allegro_bind_method">
                <option value="auto">{l s='szukaj automatycznie (kolejno po wszystkich dostępnych metodach powiązania)' mod='x13allegro'}</option>
                <option value="select">{l s='wybierz metody powiązania ręcznie' mod='x13allegro'}</option>
            </select>
        </div>
    </div>
    <div id="allegro_bind_method_auto" class="form-group row">
        <div class="col-lg-12">
            <div class="alert alert-info">
                {l s='Wyszukuje powiązania kolejno według' mod='x13allegro'}:
                <ul>
                    <li>
                        {l s='parametr EAN' mod='x13allegro'}
                        <br><small><i>- {l s='EAN-13 w PrestaShop' mod='x13allegro'}</i></small>
                    </li>
                    <li>
                        {l s='parametr ISBN/ISSN' mod='x13allegro'}
                        <br><small><i>- {l s='ISBN w PrestaShop' mod='x13allegro'}</i></small>
                    </li>
                    <li>
                        {l s='parametr MPN' mod='x13allegro'}
                        <br><small><i>- {l s='bazuje na parametrach "Kod producenta", "Numer katalogowy", "Numer katalogowy producenta", "Numer katalogowy części"' mod='x13allegro'}</i></small>
                        <br><small><i>- {l s='Kod referencyjnny (indeks) i MPN w PrestaShop' mod='x13allegro'}</i></small>
                    </li>
                    <li>
                        {l s='sygnatura oferty (external.id)' mod='x13allegro'}
                        {if $data.externalId}
                            <br><small><i>- {l s='%s w PrestaShop' sprintf=[$data.externalId] mod='x13allegro'}</i></small>
                        {else}
                            <br><small><i>- {l s='brak wybranego powiązania sygnatury oferty w konfiguracji modułu' mod='x13allegro'}</i></small>
                        {/if}
                    </li>
                </ul>
            </div>
        </div>
    </div>
    <div id="allegro_bind_method_select" class="form-group row" style="display: none;">
        <label class="control-label col-lg-3">
            {l s='Wyszukaj powiązania w PrestaShop według' mod='x13allegro'}
        </label>
        <div class="col-lg-9">
            <p class="checkbox clearfix">
                <label for="allegro_bind_ean13">
                    <input type="checkbox" id="allegro_bind_ean13" name="allegro_bind_field[]" value="ean13">
                    {l s='parametr EAN' mod='x13allegro'}
                    <span class="help-block" style="margin: 0;">{l s='wyszukuje według EAN-13 w PrestaShop' mod='x13allegro'}</span>
                </label>
            </p>
            <p class="checkbox clearfix">
                <label for="allegro_bind_isbn">
                    <input type="checkbox" id="allegro_bind_isbn" name="allegro_bind_field[]" value="isbn" {if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '<')}disabled="disabled"{/if}>
                    {l s='parametr ISBN/ISSN' mod='x13allegro'}
                    <span class="help-block" style="margin: 0;">
                        {l s='wyszukuje według ISBN w PrestaShop' mod='x13allegro'}
                        {if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '<')}({l s='dostępne od PrestaShop 1.7' mod='x13allegro'}){/if}
                    </span>
                </label>
            </p>
            <p class="checkbox clearfix">
                <label for="allegro_bind_reference">
                    <input type="checkbox" id="allegro_bind_reference" name="allegro_bind_field[]" value="mpn">
                    {l s='parametr MPN' mod='x13allegro'}
                    <span class="help-block" style="margin: 0;">{l s='bazuje na parametrach "Kod producenta", "Numer katalogowy", "Numer katalogowy producenta", "Numer katalogowy części"' mod='x13allegro'}</span>
                    <span class="help-block" style="margin: 0;">{l s='wyszukuje według Kodu referencyjnego (indeks) i MPN w PrestaShop' mod='x13allegro'}</span>
                </label>
            </p>
            <p class="checkbox clearfix">
                <label for="allegro_bind_external">
                    <input type="checkbox" id="allegro_bind_external" name="allegro_bind_field[]" value="external" {if !$data.externalId}disabled="disabled"{/if}>
                    {l s='sygnatura oferty (external.id)' mod='x13allegro'}
                    {if $data.externalId}
                        <span class="help-block" style="margin: 0;">{l s='wyszukuje według %s w PrestaShop' sprintf=[$data.externalId] mod='x13allegro'}</span>
                    {else}
                        <span class="help-block" style="margin: 0;">{l s='brak wybranego powiązania sygnatury oferty w konfiguracji modułu' mod='x13allegro'}</span>
                    {/if}
                </label>
            </p>
        </div>
    </div>
{/if}

<script>
    $(document).on('change', '[name="allegro_bind_method"]', function () {
        if ($(this).val() === 'select') {
            $('#allegro_bind_method_auto').hide();
            $('#allegro_bind_method_select').show();
        }
        else {
            $('#allegro_bind_method_auto').show();
            $('#allegro_bind_method_select').hide();
        }
    });
</script>

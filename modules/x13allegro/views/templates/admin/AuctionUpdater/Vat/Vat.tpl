<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Metoda aktualizacji' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select name="allegro_auction_tax_method">
            <option value="allegro_auction_tax_autoupdate">{l s='Zaktualizuj według ustawień produktu (Polska)' mod='x13allegro'}</option>
            <option value="allegro_auction_tax_update_pl">{l s='Wybierz nową stawkę VAT (Polska)' mod='x13allegro'}</option>
            <option value="allegro_auction_tax_update_cz">{l s='Wybierz nową stawkę VAT (Czechy)' mod='x13allegro'}</option>
            <option value="allegro_auction_tax_update_sk">{l s='Wybierz nową stawkę VAT (Słowacja)' mod='x13allegro'}</option>
            <option value="allegro_auction_tax_update_hu">{l s='Wybierz nową stawkę VAT (Węgry)' mod='x13allegro'}</option>
            <option value="allegro_auction_tax_delete_pl">{l s='Usuń stawkę VAT (Polska)' mod='x13allegro'}</option>
            <option value="allegro_auction_tax_delete_cz">{l s='Usuń stawkę VAT (Czechy)' mod='x13allegro'}</option>
            <option value="allegro_auction_tax_delete_sk">{l s='Usuń stawkę VAT (Słowacja)' mod='x13allegro'}</option>
            <option value="allegro_auction_tax_delete_hu">{l s='Usuń stawkę VAT (Węgry)' mod='x13allegro'}</option>
        </select>
    </div>
</div>

<div id="allegro_auction_tax_update_pl" class="form-group row" style="display: none;">
    <label class="control-label col-lg-3">
        {l s='Nowa stawka VAT (Polska)' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select name="allegro_auction_tax_pl">
            {foreach $data.taxPL as $tax}
                <option value="{$tax.id}">{$tax.name}</option>
            {/foreach}
        </select>
    </div>
</div>
<div id="allegro_auction_tax_update_cz" class="form-group row" style="display: none;">
    <label class="control-label col-lg-3">
        {l s='Nowa stawka VAT (Czechy)' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select name="allegro_auction_tax_cz">
            {foreach $data.taxCZ as $tax}
                <option value="{$tax.id}">{$tax.name}</option>
            {/foreach}
        </select>
    </div>
</div>

<div id="allegro_auction_tax_update_sk" class="form-group row" style="display: none;">
    <label class="control-label col-lg-3">
        {l s='Nowa stawka VAT (Słowacja)' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select name="allegro_auction_tax_sk">
            {foreach $data.taxSK as $tax}
                <option value="{$tax.id}">{$tax.name}</option>
            {/foreach}
        </select>
    </div>
</div>

<div id="allegro_auction_tax_update_hu" class="form-group row" style="display: none;">
    <label class="control-label col-lg-3">
        {l s='Nowa stawka VAT (Węgry)' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select name="allegro_auction_tax_hu">
            {foreach $data.taxHU as $tax}
                <option value="{$tax.id}">{$tax.name}</option>
            {/foreach}
        </select>
    </div>
</div>

<script>
    $(document).on('change', '[name="allegro_auction_tax_method"]', function () {
        var value = $(this).val();

        if (value == 'allegro_auction_tax_update_pl') {
            $('#allegro_auction_tax_update_cz').hide();
            $('#allegro_auction_tax_update_sk').hide();
            $('#allegro_auction_tax_update_hu').hide();
            $('#allegro_auction_tax_update_pl').show();
        }
        else if (value == 'allegro_auction_tax_update_cz') {
            $('#allegro_auction_tax_update_pl').hide();
            $('#allegro_auction_tax_update_sk').hide();
            $('#allegro_auction_tax_update_hu').hide();
            $('#allegro_auction_tax_update_cz').show();
        }
        else if (value == 'allegro_auction_tax_update_sk') {
            $('#allegro_auction_tax_update_pl').hide();
            $('#allegro_auction_tax_update_cz').hide();
            $('#allegro_auction_tax_update_hu').hide();
            $('#allegro_auction_tax_update_sk').show();
        }
        else if (value == 'allegro_auction_tax_update_hu') {
            $('#allegro_auction_tax_update_pl').hide();
            $('#allegro_auction_tax_update_cz').hide();
            $('#allegro_auction_tax_update_sk').hide();
            $('#allegro_auction_tax_update_hu').show();
        }
        else {
            $('#allegro_auction_tax_update_pl').hide();
            $('#allegro_auction_tax_update_cz').hide();
            $('#allegro_auction_tax_update_sk').hide();
            $('#allegro_auction_tax_update_hu').hide();
        }
    });
</script>

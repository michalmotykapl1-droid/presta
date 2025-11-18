<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Metoda aktualizacji' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select name="allegro_wholesale_price_mode">
            <option value="1" selected="selected">{l s='przypisz cennik hurtowy' mod='x13allegro'}</option>
            <option value="0">{l s='usuń cennik hurtowy' mod='x13allegro'}</option>
        </select>
    </div>
</div>
<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Cennik hurtowy' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        {if !empty($data.wholesalePriceList)}
            <input type="hidden" name="allegro_wholesale_price_name" value="{$data.wholesalePriceList[0]->benefits[0]->specification->name}">
            <select name="allegro_wholesale_price">
                {foreach $data.wholesalePriceList as $wholesalePrice}
                    <option value="{$wholesalePrice->id}">{$wholesalePrice->benefits[0]->specification->name}</option>
                {/foreach}
            </select>
        {else}
            {l s='Brak skonfigurowanych cenników hurtowych' mod='x13allegro'}
        {/if}
    </div>
</div>

<script>
    $(document).on('change', '[name="allegro_wholesale_price_mode"]', function () {
        if (parseInt($(this).val()) === 1) {
            $('select[name="allegro_wholesale_price"]').parents('.form-group').show();
        } else {
            $('select[name="allegro_wholesale_price"]').parents('.form-group').hide();
        }
    });

    $(document).on('change', '[name="allegro_wholesale_price"]', function () {
        $('input[name="allegro_wholesale_price_name"]').val($(this).find('option:selected').text());
    });
</script>

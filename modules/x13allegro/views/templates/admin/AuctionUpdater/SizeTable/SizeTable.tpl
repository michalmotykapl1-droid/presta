<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Metoda aktualizacji' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select name="allegro_size_table_mode">
            <option value="1" selected="selected">{l s='przypisz tabele rozmiarów' mod='x13allegro'}</option>
            <option value="0">{l s='usuń tabele rozmiarów' mod='x13allegro'}</option>
        </select>
    </div>
</div>
<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Tabela rozmiarów' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        {if !empty($data.sizeTables)}
            <input type="hidden" name="allegro_size_table_name" value="{$data.sizeTables[0]->name}">
            <select name="allegro_size_table">
                {foreach $data.sizeTables as $sizeTable}
                    <option value="{$sizeTable->id}">{$sizeTable->name}</option>
                {/foreach}
            </select>
        {else}
            {l s='Brak skonfigurowanych tabel rozmiarów' mod='x13allegro'}
        {/if}
    </div>
</div>

<script>
    $(document).on('change', '[name="allegro_size_table_mode"]', function () {
        if (parseInt($(this).val()) === 1) {
            $('select[name="allegro_size_table"]').parents('.form-group').show();
        } else {
            $('select[name="allegro_size_table"]').parents('.form-group').hide();
        }
    });

    $(document).on('change', '[name="allegro_size_table"]', function () {
        $('input[name="allegro_size_table_name"]').val($(this).find('option:selected').text());
    });
</script>

<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Metoda aktualizacji' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select name="allegro_additional_service_mode">
            <option value="1" selected="selected">{l s='przypisz dodatkowe usługi' mod='x13allegro'}</option>
            <option value="0">{l s='usuń dodatkowe usługi' mod='x13allegro'}</option>
        </select>
    </div>
</div>
<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Grupa dodatkowych usług' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        {if !empty($data.additionalServices)}
            <input type="hidden" name="allegro_additional_service_name" value="{$data.additionalServices[0]->name}">
            <select name="allegro_additional_service">
                {foreach $data.additionalServices as $additionalService}
                    <option value="{$additionalService->id}">{$additionalService->name}</option>
                {/foreach}
            </select>
        {else}
            {l s='Brak skonfigurowanych dodatkowych usług' mod='x13allegro'}
        {/if}
    </div>
</div>

<script>
    $(document).on('change', '[name="allegro_additional_service_mode"]', function () {
        if (parseInt($(this).val()) === 1) {
            $('select[name="allegro_additional_service"]').parents('.form-group').show();
        } else {
            $('select[name="allegro_additional_service"]').parents('.form-group').hide();
        }
    });

    $(document).on('change', '[name="allegro_additional_service"]', function () {
        $('input[name="allegro_additional_service_name"]').val($(this).find('option:selected').text());
    });
</script>

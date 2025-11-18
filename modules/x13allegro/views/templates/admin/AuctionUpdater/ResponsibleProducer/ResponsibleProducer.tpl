{if empty($data.responsibleProducers)}
    <div class="alert alert-info">
        {l s='Brak skonfigurowanych danych producentów - GPSR' mod='x13allegro'}
    </div>
{else}
    <div class="alert alert-info">
        Jeśli producent jest <b>spoza Unii Europejskiej</b>, musisz też wskazać osobę odpowiedzialną.
    </div>

    {if $data.x13gpsr}
        <div class="form-group row">
            <label for="allegro_responsible_producer_mode" class="control-label col-lg-4">
                {l s='Metoda aktualizacji' mod='x13allegro'}
            </label>
            <div class="col-lg-8">
                <select id="allegro_responsible_producer_mode" name="allegro_responsible_producer_mode">
                    <option value="0" selected="selected">{l s='przypisz producenta' mod='x13allegro'}</option>
                    <option value="1">{l s='przypisz producenta według powiązań z modułu X13 GPSR' mod='x13allegro'}</option>
                </select>
            </div>
        </div>
    {else}
        <input type="hidden" name="allegro_responsible_producer_mode" value="0">
    {/if}

    <div class="form-group row">
        <label for="allegro_responsible_producer" class="control-label col-lg-4">
            {l s='Dane producenta - GPSR' mod='x13allegro'}
        </label>
        <div class="col-lg-8">
            <input type="hidden" name="allegro_responsible_producer_name" value="{$data.responsibleProducers[0]->name}">
            <select id="allegro_responsible_producer" name="allegro_responsible_producer">
                {foreach $data.responsibleProducers as $responsibleProducer}
                    <option value="{$responsibleProducer->id}">{$responsibleProducer->name}</option>
                {/foreach}
            </select>
        </div>
    </div>
{/if}

<script>
    $('select[name="allegro_responsible_producer"]').chosen({
        width: '100%'
    });

    $(document).on('change', '[name="allegro_responsible_producer_mode"]', function () {
        if (parseInt($(this).val()) === 1) {
            $('select[name="allegro_responsible_producer"]').parents('.form-group').hide();
        } else {
            $('select[name="allegro_responsible_producer"]').parents('.form-group').show();
        }
    });

    $(document).on('change', '[name="allegro_responsible_producer"]', function () {
        $('input[name="allegro_responsible_producer_name"]').val($(this).find('option:selected').text());
    });
</script>

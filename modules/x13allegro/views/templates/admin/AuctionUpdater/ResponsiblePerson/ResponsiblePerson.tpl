{if empty($data.responsiblePersons)}
    <div class="alert alert-info">
        {l s='Brak skonfigurowanych osób odpowiedzialnych - GPSR' mod='x13allegro'}
    </div>
{else}
    <div class="alert alert-info">
        Jeśli producent <b>jest z Unii Europejskiej</b>, wskazanie osoby odpowiedzialnej jest opcjonalne.
    </div>

    <div class="form-group row">
        <label for="allegro_responsible_person_mode" class="control-label col-lg-4">
            {l s='Metoda aktualizacji' mod='x13allegro'}
        </label>
        <div class="col-lg-8">
            <select id="allegro_responsible_person_mode" name="allegro_responsible_person_mode">
                <option value="1" selected="selected">{l s='przypisz osobę odpowiedzialną' mod='x13allegro'}</option>
                {if $data.x13gpsr}<option value="2">{l s='przypisz osobę odpowiedzialną według powiązań z modułu X13 GPSR' mod='x13allegro'}</option>{/if}
                <option value="0">{l s='usuń osobę odpowiedzialną' mod='x13allegro'}</option>
            </select>
        </div>
    </div>
    <div class="form-group row">
        <label for="allegro_responsible_person" class="control-label col-lg-4">
            {l s='Osoba odpowiedzialna za zgodność produktu - GPSR' mod='x13allegro'}
        </label>
        <div class="col-lg-8">
            <input type="hidden" name="allegro_responsible_person_name" value="{$data.responsiblePersons[0]->name}">
            <select id="allegro_responsible_person" name="allegro_responsible_person">
                {foreach $data.responsiblePersons as $responsiblePerson}
                    <option value="{$responsiblePerson->id}">{$responsiblePerson->name}</option>
                {/foreach}
            </select>
        </div>
    </div>
{/if}

<script>
    $('select[name="allegro_responsible_person"]').chosen({
        width: '100%'
    });

    $(document).on('change', '[name="allegro_responsible_person_mode"]', function () {
        if (parseInt($(this).val()) === 1) {
            $('select[name="allegro_responsible_person"]').parents('.form-group').show();
        } else {
            $('select[name="allegro_responsible_person"]').parents('.form-group').hide();
        }
    });

    $(document).on('change', '[name="allegro_responsible_person"]', function () {
        $('input[name="allegro_responsible_person_name"]').val($(this).find('option:selected').text());
    });
</script>

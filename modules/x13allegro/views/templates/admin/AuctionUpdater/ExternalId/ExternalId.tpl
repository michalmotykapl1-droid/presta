<div class="form-group row">
    <label for="allegro_external_mode" class="control-label col-lg-3">
        {l s='Metoda aktualizacji' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select id="allegro_external_mode" name="allegro_external_mode">
            <option value="1" {if $data.externalIdDefault}selected="selected"{/if}>{l s='aktualizacja według danych produktu' mod='x13allegro'}</option>
            <option value="2">{l s='ręczna aktualizacja' mod='x13allegro'}</option>
            <option value="0" {if !$data.externalIdDefault}selected="selected"{/if}>{l s='usuń sygnaturę' mod='x13allegro'}</option>
        </select>
    </div>
</div>
<div class="form-group row" {if !$data.externalIdDefault}style="display: none;"{/if}>
    <label for="allegro_external_auto" class="control-label col-lg-3">
        {l s='Sygnatura' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select id="allegro_external_auto" name="allegro_external_auto">
            {foreach $data.externalIdList as $externalIdOption}
                <option value="{$externalIdOption.id}" {if $externalIdOption.id == $data.externalIdDefault}selected="selected"{/if}>{$externalIdOption.name}</option>
            {/foreach}
        </select>
    </div>
</div>
<div class="form-group row" style="display: none;">
    <label for="allegro_external_value" class="control-label col-lg-3">
        {l s='Sygnatura' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <input type="text" id="allegro_external_value" name="allegro_external_value" value="">
    </div>
</div>

<script>
    $(document).on('change', '[name="allegro_external_mode"]', function () {
        if (parseInt($(this).val()) === 1) {
            $('select[name="allegro_external_auto"]').parents('.form-group').show();
            $('input[name="allegro_external_value"]').parents('.form-group').hide();
        }
        else if (parseInt($(this).val()) === 2) {
            $('select[name="allegro_external_auto"]').parents('.form-group').hide();
            $('input[name="allegro_external_value"]').parents('.form-group').show();
        }
        else {
            $('select[name="allegro_external_auto"]').parents('.form-group').hide();
            $('input[name="allegro_external_value"]').parents('.form-group').hide();
        }
    });
</script>

<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Metoda aktualizacji' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        <select name="allegro_promotion_packages_mode">
            <option value="base_change" selected="selected">{l s='przypisz podstawową opcje promowania' mod='x13allegro'}</option>
            <option value="extra_change">{l s='przypisz dodatkowe opcje promowania' mod='x13allegro'}</option>
            <option value="base_remove">{l s='usuń podstawową opcje promowania' mod='x13allegro'}</option>
            <option value="extra_remove">{l s='usuń dodatkowe opcje promowania' mod='x13allegro'}</option>
        </select>
    </div>
</div>
<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Podstawowe opcje promowania' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        {if isset($data.promotionPackages->basePackages) && !empty($data.promotionPackages->basePackages)}
            <input type="hidden" name="allegro_base_promotion_package_name" value="{$data.promotionPackages->basePackages[0]->name}">
            <select name="allegro_base_promotion_package">
                {foreach $data.promotionPackages->basePackages as $package}
                    <option value="{$package->id}">{$package->name}</option>
                {/foreach}
            </select>
        {/if}
    </div>
</div>
<div class="form-group row" style="display: none;">
    <label class="control-label col-lg-3">
        {l s='Dodatkowe opcje promowania' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        {if isset($data.promotionPackages->extraPackages) && !empty($data.promotionPackages->extraPackages)}
            <input type="hidden" name="allegro_extra_promotion_package_name" value="{$data.promotionPackages->extraPackages[0]->name}">
            <select name="allegro_extra_promotion_package">
                {foreach $data.promotionPackages->extraPackages as $package}
                    <option value="{$package->id}">{$package->name}</option>
                {/foreach}
            </select>
        {/if}
    </div>
</div>

<script>
    $(document).on('change', '[name="allegro_promotion_packages_mode"]', function () {
        if ($(this).val() === 'base_change') {
            $('select[name="allegro_extra_promotion_package"]').parents('.form-group').hide();
            $('select[name="allegro_base_promotion_package"]').parents('.form-group').show();
        }
        else if ($(this).val() === 'extra_change') {
            $('select[name="allegro_base_promotion_package"]').parents('.form-group').hide();
            $('select[name="allegro_extra_promotion_package"]').parents('.form-group').show();
        }
        else {
            $('select[name="allegro_base_promotion_package"]').parents('.form-group').hide();
            $('select[name="allegro_extra_promotion_package"]').parents('.form-group').hide();
        }
    });

    $(document).on('change', '[name="allegro_base_promotion_package"]', function () {
        $('input[name="allegro_base_promotion_package_name"]').val($(this).find('option:selected').text());
    });

    $(document).on('change', '[name="allegro_extra_promotion_package"]', function () {
        $('input[name="allegro_extra_promotion_package_name"]').val($(this).find('option:selected').text());
    });
</script>

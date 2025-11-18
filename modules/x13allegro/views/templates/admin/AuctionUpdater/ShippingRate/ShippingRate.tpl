<div class="form-group row">
    <label class="control-label col-lg-3">
        {l s='Cennik dostawy' mod='x13allegro'}
    </label>
    <div class="col-lg-9">
        {if !empty($data.shippingRates)}
            <input type="hidden" name="allegro_shipping_rate_name" value="">
            <select name="allegro_shipping_rate">
                {foreach $data.shippingRates as $shippingRate}
                    <option value="{$shippingRate.id}" {if $shippingRate.id == $data.shippingRateSelectedId}selected="selected"{/if}>{$shippingRate.name}</option>
                {/foreach}
            </select>

            <br>
            {include file='../../marketplace-list.tpl' marketplaces=$data.marketplaces availableMarketplaces=[]}
        {else}
            {l s='Brak cenników dostawy' mod='x13allegro'}
        {/if}
    </div>
</div>

<script>
    var shippingRateMarketplaces = {$data.shippingRateMarketplaces|json_encode};

    $(document).on('change', '[name="allegro_shipping_rate"]', function () {
        var $selector = $(this);

        $('input[name="allegro_shipping_rate_name"]').val($selector.find('option:selected').text());

        $('.delivery-marketplace').each(function (index, element) {
            if (shippingRateMarketplaces[$selector.val()].includes($(element).data('marketplace-id'))) {
                $(element).show();
            } else {
                $(element).hide();
            }
        });
    });

    $('[name="allegro_shipping_rate"]').trigger('change');
</script>

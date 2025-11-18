{extends file="helpers/form/form.tpl"}

{block name="other_input"}
    {if $key == 'delivery_methods'}
        <div class="clearfix">
            {include file="../../../shipments.tpl" shipments=$field}
        </div>
    {/if}
{/block}

{block name="after"}
    <script>
        var XAllegro = new X13Allegro();
        XAllegro.pasShippingRatesForm();
    </script>
{/block}

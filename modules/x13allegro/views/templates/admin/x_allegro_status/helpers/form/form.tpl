{extends file="helpers/form/form.tpl"}

{block name="input"}
    {if $input.type == 'select'}
        {if isset($input.allegro_fulfillment)}
            <div class="select-fulfillment-pattern" style="display: none;">
                <div class="clearfix select-fulfillment">
                    {$smarty.block.parent}
                    <button class="btn btn-default fulfillment-remove"><i class="icon-remove"></i></button>
                </div>
            </div>
            <button class="btn btn-primary fulfillment-create" {if !empty($fields_value[$input.name])}style="display: none;"{/if}>{l s='Powiąż ze statusem PrestaShop' mod='x13allegro'}</button>

            <div class="select-fulfillment-area">
                {if !empty($fields_value[$input.name])}
                    {foreach $fields_value[$input.name] as $statusAssignment}
                        {$fields_value[$input.name] = $statusAssignment}
                        <div class="clearfix select-fulfillment">
                            {$smarty.block.parent}
                            <button class="btn btn-default fulfillment-remove"><i class="icon-remove"></i></button>
                        </div>
                    {/foreach}
                {/if}
            </div>

            <button class="btn btn-default fulfillment-add" {if empty($fields_value[$input.name])}style="display: none;"{/if}><i class="icon-plus-sign"></i>&nbsp;{l s='Dodaj kolejne powiązanie' mod='x13allegro'}</button>
        {elseif isset($input.allegro_status) && isset($input.allegro_marketplace)}
            {if $input.allegro_marketplace != 'default'}
                {$defaultInputName = $input.name}
                {$input.name = "`$input.name`[id_order_state]"}

                <div class="select-order-state" {if !$fields_value[$input.name]}style="display: none;"{/if}>
                    {$smarty.block.parent}
                    <button class="btn btn-default default-order-state">{l s='Przywróć domyślny' mod='x13allegro'}</button>
                </div>

                <span class="switch prestashop-switch fixed-width-lg switch-order-state" {if $fields_value[$input.name]}style="display: none;"{/if}>
                    <input type="radio" name="{$defaultInputName}[default]" id="{$input.name}_default_on" value="1" {if !$fields_value[$input.name]}checked="checked"{/if}>
                    {strip}<label for="{$input.name}_default_on">{if version_compare($smarty.const._PS_VERSION_, '1.7.7.0', '>=')}{l s='Domyślny status' mod='x13allegro'}{else}{l s='Domyślny' mod='x13allegro'}{/if}</label>{/strip}

                    <input type="radio" name="{$defaultInputName}[default]" id="{$input.name}_default_off" value="0" {if $fields_value[$input.name]}checked="checked"{/if}>
                    {strip}<label for="{$input.name}_default_off">{if version_compare($smarty.const._PS_VERSION_, '1.7.7.0', '>=')}{l s='Domyślny status' mod='x13allegro'}{else}{l s='Nie' mod='x13allegro'}{/if}</label>{/strip}

                    <a class="slide-button btn"></a>
                </span>
            {else}
                {$input.name = "`$input.name`[id_order_state]"}
                {$smarty.block.parent}
            {/if}
        {else}
            {$smarty.block.parent}
        {/if}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{block name="after"}
    <style>
        #fieldset_1_1 select {
            float: left;
            margin-right: 5px;
        }
        #fieldset_1_1 .select-fulfillment + .select-fulfillment {
            margin-top: 2px;
        }
        #fieldset_1_1 .fulfillment-add {
            margin-top: 5px;
        }
    </style>
    <script>
        $(document).ready(function() {
            $('#fieldset_1_1').find('.select-fulfillment-pattern select').each(function(index, element) {
                $(element).prop('disabled', 'disabled');
            });

            $('#fieldset_0').find('select').chosen({
                width: '350px'
            });
            $('#fieldset_1_1').find('.select-fulfillment-area select').chosen({
                width: '350px'
            });
        });

        $(document).on('change', '.switch-order-state input', function() {
            if (parseInt($(this).val()) === 0) {
                var $formGroup = $(this).closest('.form-group');

                $formGroup.find('.switch-order-state').hide();
                $formGroup.find('.select-order-state').show();
            }
        });

        $(document).on('click', '.default-order-state', function(e) {
            e.preventDefault();

            var $formGroup = $(this).closest('.form-group');

            $formGroup.find('.select-order-state').hide();
            $formGroup.find('.switch-order-state input[value="1"]').prop('checked', true);
            $formGroup.find('.switch-order-state').show();
        });

        $(document).on('click', '.fulfillment-create', function(e) {
            e.preventDefault();

            var selectDiv = $(this).parent().find('.select-fulfillment-pattern').html();

            $(this).hide().parent().find('.fulfillment-add').show();
            $(this).parent().find('.select-fulfillment-area').append(selectDiv);

            $(this).parent().find('.select-fulfillment-area select').each(function(index, element) {
                $(element).removeAttr('disabled');
            });

            $(this).parent().find('.select-fulfillment-area select').chosen({
                width: '350px'
            });
        });

        $(document).on('click', '.fulfillment-add', function(e) {
            e.preventDefault();

            $(this).parent().find('.select-fulfillment-area').append($(this).parent().find('.select-fulfillment-pattern').html());
            $(this).parent().find('.select-fulfillment-area select').each(function(index, element) {
                $(element).removeAttr('disabled');
            });

            $(this).parent().find('.select-fulfillment-area select').chosen({
                width: '350px'
            });
        });

        $(document).on('click', '.fulfillment-remove', function(e) {
            e.preventDefault();

            var formGroup = $(this).parents('.form-group');

            $(this).parent().remove();

            if (!formGroup.find('.select-fulfillment-area select').length) {
                formGroup.find('.fulfillment-add').hide();
                formGroup.find('.fulfillment-create').show();
            }
        });

        var XAllegro = new X13Allegro();
        XAllegro._configurationDependencies();
    </script>
{/block}

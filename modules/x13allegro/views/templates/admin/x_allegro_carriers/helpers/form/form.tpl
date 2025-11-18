{extends file="helpers/form/form.tpl"}

{$isModernLayout = version_compare($smarty.const._PS_VERSION_, '1.7.7.0', '>=')}

{block name="field"}
    {if $input.type == 'separator'}
        <div class="col-lg-10 col-lg-push-1">
            <h4 style="font-size: 18px; font-weight: 600; margin: 20px 0 0 0;">{$input['heading']}</h4>
            <hr style="margin: 15px 0 15px 0;">
        </div>

        <div class="{if $isModernLayout}col-lg-4{else}col-lg-3{/if}"></div>
        <div class="{if $isModernLayout}col-lg-8{else}col-lg-9{/if}">
            <div class="{if $isModernLayout}col-lg-4{else}col-lg-3{/if}"><h4>{l s='Przewoźnik w Twoim sklepie' mod='x13allegro'}</h4></div>
            {if isset($input.delivery_type) && $input.delivery_type != 'free'}<div class="{if $isModernLayout}col-lg-4{else}col-lg-3{/if}"><h4>{l s='Operator numeru śledzenia w Allegro' mod='x13allegro'}</h4></div>{/if}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{block name="input"}
    <div class="{if $isModernLayout}col-lg-4{else}col-lg-3{/if}">
        <select name="{$input.name|escape:'html':'utf-8'}"
            class="{if isset($input.class)}{$input.class|escape:'html':'utf-8'}{/if}"
            id="{if isset($input.id)}{$input.id|escape:'html':'utf-8'}{else}{$input.name|escape:'html':'utf-8'}{/if}"
            {if isset($input.disabled) && $input.disabled} disabled="disabled"{/if}
        >
            {if isset($input.options.default)}
                <option value="{$input.options.default.value|escape:'html':'utf-8'}">{$input.options.default.label|escape:'html':'utf-8'}</option>
            {/if}
            {foreach $input.options.query AS $option}
                <option value="{$option[$input.options.id]}" {if $fields_value[$input.name] == $option[$input.options.id]}selected="selected"{/if}>
                    {$option[$input.options.name]}
                </option>
            {/foreach}
        </select>
    </div>

    {if isset($input.delivery_type) && $input.delivery_type != 'free'}
        <div class="{if $isModernLayout}col-lg-4{else}col-lg-3{/if}">
            <select id="{if isset($input.id)}{$input.id}{else}{$input.name_operator}[id_operator]{/if}" class="allegro-operator-select" name="{$input.name_operator|escape:'html':'utf-8'}[id_operator]">
                {if isset($input.options_operators.default)}
                    <option value="{$input.options_operators.default.value|escape:'html':'utf-8'}">{$input.options_operators.default.label|escape:'html':'utf-8'}</option>
                {/if}
                {foreach $input.options_operators.query as $option}
                    {capture name='select_name'}{$input.name_operator}[id_operator]{/capture}
                    <option value="{$option.id|escape:'html':'utf-8'}" {if $fields_value[$smarty.capture.select_name] == $option.id}selected="selected"{/if}>{$option.name|escape:'html':'utf-8'}</option>
                {/foreach}
            </select>

            <div class="allegro-operator-other" style="display: none; margin-top: 3px;">
                {capture name='input_name'}{$input.name_operator}[operator_name]{/capture}
                <input type="text" id="{if isset($input.id)}{$input.id}{else}{$input.name_operator}[operator_name]{/if}" name="{$input.name_operator|escape:'html':'utf-8'}[operator_name]" value="{$fields_value[$smarty.capture.input_name]|escape:'html':'UTF-8'}">
                <p class="help-block">{l s='Wybierając operatora "Inny" musisz podać jego nazwę' mod='x13allegro'}</p>
            </div>
        </div>
    {/if}
{/block}

{block name="after"}
    <script>
        var XAllegro = new X13Allegro();
        XAllegro.carriersForm();
    </script>
{/block}

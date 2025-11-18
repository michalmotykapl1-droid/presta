{extends file="helpers/form/form.tpl"}

{block name="input"}
    {if $input.type == 'checkbox'}
        <div class="x13allegro-list-checkbox" data-id="{$input.map_id}">
            {foreach $input.values.query as $value}
                <div class="checkbox x13allegro-checkbox">
                    {strip}
                        <label for="{$input.name}[{$value[$input.values.id]}]">
                            <input type="checkbox"
                                   name="{$input.name}[{$value[$input.values.id]}]"
                                   id="{$input.name}[{$value[$input.values.id]}]"
                                   class="{if isset($input.class)}{$input.class}{/if}"
                                   data-id="{$input.map_id}"
                                   data-value="{$value[$input.values.id]}"
                                   {if isset($value.val)}value="{$value.val|escape:'html':'UTF-8'}"{/if}
                                   {if isset($fields_value[$input.name][$value[$input.values.id]])}checked="checked"{/if}>
                            <span class="checkmark"></span>
                            {$value[$input.values.name]}
                        </label>
                    {/strip}
                </div>
            {/foreach}
        </div>
        {include './category-parameters-map-button.tpl'}
    {elseif $input.type == 'select'}
        <select name="{$input.name}"
                id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}"
                class="{if isset($input.class)}{$input.class}{/if}"
                data-id="{$input.map_id}"
                data-ambiguous-value="{$input.ambiguous_value}"
                {if isset($input.disabled) && $input.disabled}disabled="disabled"{/if}>
            {foreach $input.options.query as $option}
                <option value="{$option[$input.options.id]}"
                        {*data-value="{$option[$input.options.id]}"*}
                        {if $fields_value[$input.name] == $option[$input.options.id]}selected="selected"{/if}>
                    {$option[$input.options.name]}
                </option>
            {/foreach}
        </select>
        {include './category-parameters-map-button.tpl'}
    {elseif $input.type == 'text'}
        {assign var='baseInputId' value=$input.id}
        {assign var='baseInputName' value=$input.name}

        {if $input.restrictions.range}
            <div class="range-values-list" data-id="{$input.map_id}">
                {$input.id = "{$baseInputId}_0"}
                {$input.name = "{$baseInputName}[0]"}
                {include './category-parameters-text-input.tpl'}

                <div class="allegro-feature-inline"><span style="line-height: 2;padding: 0 8px;">_</span></div>

                {$input.id = "{$baseInputId}_1"}
                {$input.name = "{$baseInputName}[1]"}
                {include './category-parameters-text-input.tpl'}
            </div>
            {include './category-parameters-map-button.tpl'}
        {elseif $input.restrictions.number_of_values > 1}
            {assign var='inputShownNb' value=$input.restrictions.number_of_values}

            <div class="multiple-values-list" data-id="{$input.map_id}">
                {for $iterator = 1 to $input.restrictions.number_of_values}
                    {if !empty($fields_value[$input.name])}
                        {$inputShownNb = $inputShownNb -1}
                    {/if}
                    {$input.id = "{$baseInputId}_{$iterator-1}"}
                    {$input.name = "{$baseInputName}[{$iterator-1}]"}
                    <div class="multiple-values-group {if empty($fields_value[$input.name]) && $iterator > 1}hide{/if}">
                        {include './category-parameters-text-input.tpl'}
                        {include './category-parameters-text-counter.tpl'}
                        <a href="#" class="multiple-values-delete" style="display: none;"><i class="icon-times"></i></a>
                    </div>
                {/for}
            </div>

            {include './category-parameters-map-button.tpl'}
            <a href="#" class="multiple-values-show" {if $inputShownNb <= 0}style="display: none;"{/if}>{l s='Dodaj kolejną wartość' mod='x13allegro'}</a>
        {else}
            {include './category-parameters-text-input.tpl'}
            {include './category-parameters-map-button.tpl'}
            {include './category-parameters-text-counter.tpl'}
        {/if}
    {else}
        {$smarty.block.parent}
        {include './category-parameters-map-button.tpl'}
    {/if}
{/block}

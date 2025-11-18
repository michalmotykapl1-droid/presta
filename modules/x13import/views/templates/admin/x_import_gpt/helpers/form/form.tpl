{extends file="helpers/form/form.tpl"}

{block name="field"}
    {if $input.type == 'range'}
        <div class="x13import-option-slider-input">
            <input type="text" name="{$input.name}" value="{$fields_value[$input.name]}" class="x-cast" data-cast="{$input.cast.type}" {if isset($input.cast.precision)}data-cast-precision="{$input.cast.precision}"{/if}>
        </div>
        <div class="col-lg-3">
            <div class="x13import-option-slider" data-min="{$input.range.min}" data-max="{$input.range.max}" data-step="{$input.range.step}" data-value="{$fields_value[$input.name]}"></div>
        </div>

        {if isset($input.desc) && !empty($input.desc)}
            <div class="col-lg-6 col-lg-offset-4">
                <div class="help-block">{$input.desc}</div>
            </div>
        {/if}
    {elseif $input.type == 'checkbox'}
        <div class="col-lg-8">
            {foreach $input.values.query as $value}
                {assign var=id_checkbox value=$input.name|cat:'_'|cat:$value[$input.values.id]}
                <div class="checkbox">
                    {strip}
                        <label for="{$id_checkbox}">
                            <input type="checkbox" name="{$input.name}[]" id="{$id_checkbox}" class="{if isset($input.class)}{$input.class}{/if}" value="{$value[$input.values.id]|escape:'html':'UTF-8'}" {if isset($fields_value[$id_checkbox]) && $fields_value[$id_checkbox]}checked="checked"{/if} />
                            {$value[$input.values.name]}
                        </label>
                    {/strip}
                </div>
            {/foreach}
        </div>

        {if isset($input.desc) && !empty($input.desc)}
            <div class="col-lg-6 col-lg-offset-4">
                <div class="help-block">{$input.desc}</div>
            </div>
        {/if}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{extends file="helpers/options/options.tpl"}

{block name="input"}
    {if $field.type == 'range'}
        <div class="x13import-option-slider-input">
            <input type="text" name="{$key}" value="{$field.value}" class="x-cast" data-cast="{$field.cast.type}" {if isset($field.cast.precision)}data-cast-precision="{$field.cast.precision}"{/if}>
        </div>
        <div class="col-lg-3">
            <div class="x13import-option-slider" data-min="{$field.range.min}" data-max="{$field.range.max}" data-step="{$field.range.step}" data-value="{$field.value}"></div>
        </div>

        {if isset($field.desc) && !empty($field.desc)}
            <div class="col-lg-9 col-lg-offset-3">
                <div class="help-block">{$field.desc}</div>
            </div>
        {/if}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

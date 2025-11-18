{extends file="helpers/form/form.tpl"}

{block name="input"}
    {if $input.type == 'checkbox'}
        {foreach $input.values.query as $value}
            <div class="checkbox">
                {strip}
                    <label for="{$input.name}[{$value[$input.values.id]}]">
                        <input type="checkbox" name="{$input.name}[{$value[$input.values.id]}]" id="{$input.name}[{$value[$input.values.id]}]" class="{if isset($input.class)}{$input.class}{/if}"{if isset($value.val)} value="{$value.val|escape:'html':'UTF-8'}"{/if} {if isset($fields_value[$input.name][$value[$input.values.id]])}checked="checked"{/if} />
                        {$value[$input.values.name]}
                    </label>
                {/strip}
            </div>
        {/foreach}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

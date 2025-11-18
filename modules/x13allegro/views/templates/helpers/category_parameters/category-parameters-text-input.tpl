{assign var='value_text' value=$fields_value[$input.name]}

{if isset($input.prefix) || isset($input.suffix)}
<div class="input-group{if isset($input.class)} {$input.class}{/if}">
    {/if}
    {if isset($input.prefix)}
        <span class="input-group-addon">
            {$input.prefix}
        </span>
    {/if}
    <input type="text"
           name="{$input.name}"
           id="{if isset($input.id)}{$input.id}{else}{$input.name}{/if}"
           class="{if isset($input.class)}{$input.class}{/if}"
           value="{if isset($input.string_format) && $input.string_format}{$value_text|default|string_format:$input.string_format|escape:'html':'UTF-8'}{else}{$value_text|default|escape:'html':'UTF-8'}{/if}"
           data-id="{$input.map_id}"
           {if isset($input.is_ambiguous_input) && $input.is_ambiguous_input}data-ambiguous-input="{$input.map_id}"{/if}
           {if isset($input.readonly) && $input.readonly}readonly="readonly"{/if}
           {if isset($input.disabled) && $input.disabled}disabled="disabled"{/if}>
    {if isset($input.suffix)}
        <span class="input-group-addon">
            {$input.suffix}
        </span>
    {/if}
    {if isset($input.prefix) || isset($input.suffix)}
</div>
{/if}

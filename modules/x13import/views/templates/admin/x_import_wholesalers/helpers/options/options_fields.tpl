<div class="form-wrapper">
    {* backport for older wholesalers versions with individual attributes/features options *}
    {foreach $optionsFields as $field}
        {$hiddenFeatureDisplay = false}
        {if isset($field['hidden_feature']) && $field['hidden_feature'] && $field['value']}
            {$hiddenFeatureDisplay = true}
            {break}
        {/if}
    {/foreach}

    {foreach $optionsFields as $key => $field}
        {if isset($field['hidden_feature']) && $field['hidden_feature'] && !$hiddenFeatureDisplay}
            {continue}
        {/if}

        {if $field['type'] == 'hidden'}
            <input type="hidden" name="{$key}" value="{$field['value']}" />
        {else}
            <div class="form-group{if isset($field.form_group_class)} {$field.form_group_class}{/if} clearfix"{if isset($tabs) && isset($field.tab)} data-tab-id="{$field.tab}"{/if}>
                <div id="conf_id_{$key}">
                    {if isset($field['title']) && isset($field['hint'])}
                        <label class="control-label col-lg-4{if isset($field['required']) && $field['required']} required{/if}">
                            <span title="" data-toggle="tooltip" class="label-tooltip" data-original-title="
                                {if is_array($field['hint'])}
                                    {foreach $field['hint'] as $hint}
                                        {if is_array($hint)}
                                            {$hint.text}
                                        {else}
                                            {$hint}
                                        {/if}
                                    {/foreach}
                                {else}
                                    {$field['hint']}
                                {/if}
                                " data-html="true">
                                {$field['title']}
                            </span>
                        </label>
                    {elseif isset($field['title'])}
                        <label class="control-label col-lg-4{if isset($field['required']) && $field['required']} required{/if}">{$field['title']}</label>
                    {/if}

                    {if $field['type'] == 'select'}
                        <div class="col-lg-8">
                            {if $field['list']}
                                <select class="form-control fixed-width-xxl {if isset($field['class'])}{$field['class']}{/if}" name="{$key}{if isset($field['multiple']) && $field['multiple']}[]{/if}"{if isset($field['multiple']) && $field['multiple']} multiple style="float: left; margin-right: 5px;"{/if}{if isset($field['js'])} onchange="{$field['js']}"{/if} id="{$key}" {if isset($field['size'])} size="{$field['size']}"{/if} {if isset($field['disabled']) && $field['disabled']} disabled="disabled"{/if}>
                                    {if isset($field['multiple']) && $field['multiple']}<option value="" class="multiple-empty" style="display: none;"></option>{/if}
                                    {foreach $field['list'] AS $k => $option}
                                        <option value="{$option[$field['identifier']]}"{if (isset($field['multiple']) && $field['multiple'] && is_array($field['value']) && in_array($option[$field['identifier']], $field['value'])) || (!is_array($field['value']) && $field['value'] == $option[$field['identifier']])} selected="selected"{/if}>{$option['name']}</option>
                                    {/foreach}
                                </select>
                                {if isset($field['multiple']) && $field['multiple']}
                                    <button class="btn btn-default multiple-clear" {if isset($field['disabled']) && $field['disabled']} disabled="disabled"{/if}>Odznacz wszystko</button>
                                {/if}
                            {elseif isset($input.empty_message)}
                                {$input.empty_message}
                            {/if}
                        </div>
                    {elseif $field['type'] == 'bool'}
                        <div class="col-lg-8">
                            <span class="switch prestashop-switch fixed-width-lg">
                                {strip}
                                    <input type="radio" data-wholesaler="{$wholesale.name}" class="{if isset($field['class'])}{$field['class']}{/if}" name="{$key}" id="{$key}_on" value="1" {if $field['value']} checked="checked"{/if}{if isset($field['js']['on'])} {$field['js']['on']}{/if}{if isset($field['disabled']) && $field['disabled']} disabled="disabled"{/if}/>
                                    <label for="{$key}_on" class="radioCheck">
                                    {l s='Yes'}
                                </label>
                                    <input type="radio" data-wholesaler="{$wholesale.name}" class="{if isset($field['class'])}{$field['class']}{/if}" name="{$key}" id="{$key}_off" value="0" {if !$field['value']} checked="checked"{/if}{if isset($field['js']['off'])} {$field['js']['off']}{/if}{if isset($field['disabled']) && $field['disabled']} disabled="disabled"{/if}/>
                                    <label for="{$key}_off" class="radioCheck">
                                    {l s='No'}
                                </label>
                                {/strip}
                                <a class="slide-button btn"></a>
                            </span>
                        </div>
                    {elseif $field['type'] == 'text'}
                        <div class="col-lg-8">{if isset($field['suffix'])}<div class="input-group{if isset($field.class)} {$field.class}{/if}">{/if}
                            <input class="form-control {if isset($field['class'])}{$field['class']}{/if}" type="{$field['type']}"{if isset($field['id'])} id="{$field['id']}"{/if} size="{if isset($field['size'])}{$field['size']|intval}{else}75{/if}" name="{$key}" value="{if isset($field['no_escape']) && $field['no_escape']}{$field['value']|escape:'UTF-8'}{else}{$field['value']|escape:'html':'UTF-8'}{/if}" {if isset($field['autocomplete']) && !$field['autocomplete']}autocomplete="off"{/if} {if isset($field['disabled']) && $field['disabled']} disabled="disabled"{/if} />
                            {if isset($field['suffix'])}
                                <span class="input-group-addon">
                                    {$field['suffix']|strval}
                                </span>
                            {/if}
                            {if isset($field['suffix'])}</div>{/if}
                        </div>
                    {elseif $field['type'] == 'textarea'}
                        <div class="col-lg-8">
                            <textarea class="{if isset($field['class'])}{$field['class']}{/if}{if isset($field['autoload_rte']) && $field['autoload_rte']} rte autoload_rte{else} textarea-autosize{/if}"{if isset($field['id'])} id="{$field['id']}"{/if} name={$key}{if isset($field['cols'])} cols="{$field['cols']}"{/if}{if isset($field['rows'])} rows="{$field['rows']}"{/if} {if isset($field['disabled']) && $field['disabled']} disabled="disabled"{/if}>{$field['value']|escape:'html':'UTF-8'}</textarea>
                        </div>
                    {elseif $field['type'] == 'password'}
                        <div class="col-lg-8">{if isset($field['suffix'])}<div class="input-group{if isset($field.class)} {$field.class}{/if}">{/if}
                            <input class="form-control {if isset($field['class'])}{$field['class']}{/if}" type="{$field['type']}"{if isset($field['id'])} id="{$field['id']}"{/if} size="{if isset($field['size'])}{$field['size']|intval}{else}75{/if}" name="{$key}" value="{if isset($field['no_escape']) && $field['no_escape']}{$field['value']|escape:'UTF-8'}{else}{$field['value']|escape:'html':'UTF-8'}{/if}"{if isset($field['autocomplete']) && !$field['autocomplete']} autocomplete="off"{/if} {if isset($field['disabled']) && $field['disabled']} disabled="disabled"{/if} />
                            {if isset($field['suffix'])}
                                <span class="input-group-addon">
                                    {$field['suffix']|strval}
                                </span>
                            {/if}
                            {if isset($field['suffix'])}</div>{/if}
                        </div>
                    {elseif $field['type'] == 'hr'}
                        <hr />
                    {/if}

                    {if isset($field['desc']) && !empty($field['desc'])}
                        <div class="col-lg-8 col-lg-offset-4">
                            <div class="help-block">
                                {if is_array($field['desc'])}
                                    {foreach $field['desc'] as $p}
                                        {if is_array($p)}
                                            <span id="{$p.id}">{$p.text}</span><br />
                                        {else}
                                            {$p}<br />
                                        {/if}
                                    {/foreach}
                                {else}
                                    {$field['desc']}
                                {/if}
                            </div>
                        </div>
                    {/if}
                </div>
            </div>
        {/if}
    {/foreach}
</div>

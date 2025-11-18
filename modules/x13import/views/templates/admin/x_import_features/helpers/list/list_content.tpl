{extends file="helpers/list/list_content.tpl"}

{$has_bulk_actions = true}
{$bulk_actions = true}

{block name="open_td"}
    <td
        class="{strip}{if $key == 'name'}pointer{/if}
            {if isset($key)} column-{$key|lower}{/if}
            {if isset($params.class)} {$params.class}{/if}
            {if isset($params.align)} {$params.align}{/if}{/strip}"
        data-x-feature-id="{$tr.$identifier}"
    >
{/block}

{block name="td_content"}
    {if $key == 'name'}
        <i class="icon-list"></i>
        <span>{$tr.$key}</span>
        <i class="icon-chevron-down"></i>
    {elseif $key == 'import_option'}
        <select class="fixed-width-xxl" data-x-feature-id="{$tr.$identifier}" data-value="{$tr.$key}">
            {foreach $featureImportOption as $option}
                <option value="{$option.value}" {if $tr.$key == $option.value}selected="selected"{/if}>{$option.name}</option>
            {/foreach}
        </select>
    {elseif $key == 'id_feature'}
        <select class="ximport-select2" data-x-feature-id="{$tr.$identifier}" data-value="{$tr.id_feature}" style="width: 100%; {if $tr.import_option != $featureImportOption['ASSIGN_VALUE'].value}display: none;{/if}">
            <option value="{$tr.id_feature}" selected="selected">{$tr.feature_name}</option>
        </select>
    {elseif $key == 'import_as_custom'}
        <label>
            <input type="checkbox" value="1" data-x-feature-id="{$tr.$identifier}" data-checked="{if $tr.$key}1{else}0{/if}" {if $tr.$key}checked="checked"{/if}> {l s='Dodaj jako cechy indywidualne' mod='x13import'}
        </label>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{extends file="helpers/list/list_content.tpl"}

{$has_bulk_actions = true}
{$bulk_actions = true}

{block name="open_td"}
    <td
        class="{strip}{if isset($key)} column-{$key|lower}{/if}
            {if isset($params.class)} {$params.class}{/if}
            {if isset($params.align)} {$params.align}{/if}{/strip}"
        data-x-manufacturer-id="{$tr.$identifier}"
    >
{/block}

{block name="td_content"}
    {if $key == 'import_option'}
        <select class="fixed-width-xxl" data-x-manufacturer-id="{$tr.$identifier}" data-value="{$tr.$key}">
            {foreach $manufacturerImportOption as $option}
                <option value="{$option.value}" {if $tr.$key == $option.value}selected="selected"{/if}>{$option.name}</option>
            {/foreach}
        </select>
    {elseif $key == 'id_manufacturer'}
        <select class="ximport-select2" data-x-manufacturer-id="{$tr.$identifier}" data-value="{$tr.id_manufacturer}" style="width: 100%; {if $tr.import_option != $manufacturerImportOption['ASSIGN_VALUE'].value}display: none;{/if}">
            <option value="{$tr.id_manufacturer}" selected="selected">{$tr.manufacturer_name}</option>
        </select>
    {elseif $key == 'import_products_option'}
        <select class="fixed-width-xxl" data-x-manufacturer-id="{$tr.$identifier}" data-value="{$tr.$key}">
            {foreach $manufacturerImportProductsOption as $option}
                <option value="{$option.value}" {if $tr.$key == $option.value}selected="selected"{/if}>{$option.name}</option>
            {/foreach}
        </select>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

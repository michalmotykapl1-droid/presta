{extends file="helpers/list/list_content.tpl"}

{$has_bulk_actions = true}
{$bulk_actions = true}

{block name="open_td"}
    <td
        class="{strip}{if $key == 'group_name'}pointer{/if}
            {if isset($key)} column-{$key|lower}{/if}
            {if isset($params.class)} {$params.class}{/if}
            {if isset($params.align)} {$params.align}{/if}{/strip}"
        data-x-attribute-group-id="{$tr.$identifier}"
    >
{/block}

{block name="td_content"}
    {if $key == 'group_name'}
        <i class="icon-list"></i>
        <span>{$tr.$key}</span>
        <i class="icon-chevron-down"></i>
    {elseif $key == 'import_option'}
        <select class="fixed-width-xxl" data-x-attribute-group-id="{$tr.$identifier}" data-value="{$tr.$key}">
            {foreach $attributeGroupImportOption as $option}
                <option value="{$option.value}" {if $tr.$key == $option.value}selected="selected"{/if}>{$option.name}</option>
            {/foreach}
        </select>
    {elseif $key == 'id_attribute_group'}
        <select class="ximport-select2" data-x-attribute-group-id="{$tr.$identifier}" data-value="{$tr.id_attribute_group}" style="width: 100%; {if $tr.import_option != $attributeGroupImportOption['ASSIGN_VALUE'].value}display: none;{/if}">
            <option value="{$tr.id_attribute_group}" selected="selected">{$tr.attribute_group_name}</option>
        </select>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

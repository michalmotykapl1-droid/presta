{extends file="helpers/list/list_content.tpl"}

{$has_bulk_actions = true}
{$bulk_actions = true}

{block name="td_content"}
    {if $key == 'markup'}
        <div class="markup_container" style="float: left;">
            <span style="display: block; height: 100%; float: left; cursor: pointer; margin-right: 3px;" class="markup_edit">{$tr.$key|escape:'htmlall':'UTF-8'|number_format:2:".":""}</span>
            <input style="width: 60px; display: none;" type="text" value="{$tr.$key|escape:'htmlall':'UTF-8'|number_format:2:".":""}" x-id="{$tr.id_ximport_category}" class="markup_edit x-cast x-cast-float x-cast-unsigned" />

            <span class="markup_type_edit" style="cursor: pointer;">{if $tr.markup_type == 'percent'}%{else}zł{/if}</span>
            <select name="markup_type" class="markup_type_edit" style="width: 70px; display: none;">
                <option value="percent" {if $tr.markup_type == 'percent'}selected="selected"{/if}>%</option>
                <option value="amount" {if $tr.markup_type == 'amount'}selected="selected"{/if}>zł</option>
            </select>
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

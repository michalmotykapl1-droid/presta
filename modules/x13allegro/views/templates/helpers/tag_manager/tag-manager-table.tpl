
<table class="table xallegro-tags-table" data-id="{$user_id}" cellspacing="0" cellpadding="0" style="width: 100%; display: none;">
    <thead>
    <tr>
        <th style="width: 20px;"></th>
        <th colspan="2"></th>
    </tr>
    </thead>
    <tbody>
    {if isset($tags->tags) && !empty($tags->tags)}
        {foreach $tags->tags as $tag}
            <tr data-id="{$tag->id}" data-hidden="{$tag->hidden|intval}">
                <td><input type="checkbox" name="xallegro_tag[{$user_id}][{$tag->id}]" value="1" class="xallegro-tag-map" {if isset($tag_manager_map[$user_id][$tag->id])}checked="checked"{/if}></td>
                <td class="form-inline" style="flex: 1 1 auto;">
                    <span class="xallegro-tag-view">{$tag->name}</span>
                    {if $tag_manager_editable}
                        <a class="btn btn-primary xallegro-tag-save {if $tag_manager_17 && $tag_manager_map_type == 'product'}btn-sm{/if}" title="{l s='Zapisz' mod='x13allegro'}" style="margin-left: 5px; display: none;">
                            {if $tag_manager_17 && $tag_manager_map_type == 'product'}
                                <i class="material-icons">save</i>
                            {elseif version_compare($smarty.const._PS_VERSION_, '1.6', '<')}
                                <img src="../img/admin/enabled.gif" alt="{l s='Zapisz' mod='x13allegro'}">
                            {else}
                                <i class="icon-save"></i>
                            {/if}
                        </a>
                    {/if}
                </td>
                <td style="text-align: right;">
                    {if $tag_manager_editable}
                        <a data-tag-name="{$tag->name}" data-tag-id="{$tag->id}" href="#" class="xallegro-tag-edit btn btn-sm" style="padding: 0;" title="{l s='Edytuj' mod='x13allegro'}">
                            {if $tag_manager_17 && $tag_manager_map_type == 'product'}
                                <i class="material-icons">edit</i>
                            {elseif version_compare($smarty.const._PS_VERSION_, '1.6', '<')}
                                <img src="../img/admin/edit.gif" alt="{l s='Edytuj' mod='x13allegro'}">
                            {else}
                                <i class="icon-edit"></i>
                            {/if}
                        </a>&nbsp;&nbsp;
                        <a href="#" class="xallegro-tag-delete btn btn-sm" style="padding: 0;" title="{l s='Usuń' mod='x13allegro'}">
                            {if $tag_manager_17 && $tag_manager_map_type == 'product'}
                                <i class="material-icons">delete</i>
                            {elseif version_compare($smarty.const._PS_VERSION_, '1.6', '<')}
                                <img src="../img/admin/delete.gif" alt="{l s='Usuń' mod='x13allegro'}">
                            {else}
                                <i class="icon-trash"></i>
                            {/if}
                        </a>
                    {/if}
                </td>
            </tr>
        {/foreach}
    {else}
        <tr>
            <td colspan="3" style="text-align: center;">{l s='Brak tagów' mod='x13allegro'}</td>
        </tr>
    {/if}
    </tbody>
</table>

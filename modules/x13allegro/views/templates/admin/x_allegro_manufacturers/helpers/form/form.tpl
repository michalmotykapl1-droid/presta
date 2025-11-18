{extends file="helpers/form/form.tpl"}
{include file="module:x13allegro/views/templates/admin/manufacturer_automap_snippet.tpl"}
{block name="field"}
    {if $input.name == 'tag-manager'}
        {if empty($input.content)}
            <div class="alert alert-info">
                <p>{l s='Zapisz tego producenta aby umożliwić mapowanie tagów.' mod='x13allegro'}</p>
            </div>
        {else}
            {$input.content}
        {/if}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

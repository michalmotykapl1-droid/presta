{extends file="helpers/form/form.tpl"}

{block name=input}
    {if $input.type == 'new_content'}
        <div class="bootstrap">
            <div id="new_content"></div>
            <textarea{if isset($input.readonly) && $input.readonly} readonly="readonly"{/if} name="{$input.name}" id="new_content_textarea" class="{if isset($input.autoload_rte) && $input.autoload_rte}rte autoload_rte{else}textarea-autosize{/if}{if isset($input.class)} {$input.class}{/if}"{if isset($input.maxlength) && $input.maxlength} maxlength="{$input.maxlength|intval}" data-maxchar="{$input.maxchar|intval}"{/if} style="display: none !important;">{$new_content|escape:'html':'UTF-8'}</textarea>
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{block name="after"}
    <div class="bootstrap">
        <div class="new-content-block row">
            {include file="../../variables.tpl" variables=$template_variables}
        </div>
    </div>
    <script>
        var additionalImages = '{$template_additional_images}';
        var XAllegro = new X13Allegro();
        XAllegro.templateForm();
    </script>
{/block}

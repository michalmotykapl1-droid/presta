{extends file="page_header_toolbar.tpl"}

{block name="pageTitle"}
    {$isModernLayout = version_compare($smarty.const._PS_VERSION_, '1.7.4.0', '>=')}
    {if $isModernLayout}<h1 class="page-title">{else}<h2 class="page-title">{/if}
    {if is_array($title)}{$title|end|strip_tags}{else}{$title|strip_tags}{/if}
    <small>{$toolbar_subtitle}</small>
    {if $isModernLayout}</h1>{else}</h2>{/if}
{/block}

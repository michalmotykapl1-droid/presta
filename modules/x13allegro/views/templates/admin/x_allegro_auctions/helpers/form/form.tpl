{extends file="helpers/form/form.tpl"}

{block name="input"}
	{if $input.name == 'name'}
		{$smarty.block.parent}
		{if $fields_value.id_product}
            {if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '<')}
                {capture name='product_link'}{$link->getAdminLink('AdminProducts')}&updateproduct&id_product={$fields_value.id_product}{/capture}
            {else}
                {capture name='product_link'}{$link->getAdminLink('AdminProducts', true, ['id_product' => {$fields_value.id_product}])}{/capture}
            {/if}
            <br />
            <a href="{$smarty.capture.product_link}" target="_blank" class="btn btn-default button bt-icon">
                <i class="icon-edit"></i> <span>{l s='Edytuj produkt' mod='x13allegro'}</span>
            </a>
            <a href="{$link->getProductLink($fields_value.id_product, null, null, null, $current_id_lang, $fields_value.id_shop, $fields_value.id_product_attribute)}" target="_blank" class="btn btn-default button bt-icon">
                <i class="icon-search"></i> <span>{l s='Zobacz produkt w sklepie' mod='x13allegro'}</span>
            </a>
		{/if}
	{elseif $input.name == 'title'}
        {$smarty.block.parent}
        <br />
        {foreach $offerMarketplaces as $marketplace}
            <a href="{$marketplace.offerUrl}" target="_blank" title="{l s='Zobacz na' mod='x13allegro'} {$marketplace.name}" class="btn btn-default button bt-icon">
                <img src="../modules/x13allegro/img/AdminXAllegroMain.png" width="14px" height="14px" alt="{l s='Zobacz na' mod='x13allegro'} {$marketplace.name}"> {$marketplace.name}
            </a>
        {/foreach}
	{else}
		{$smarty.block.parent}

        {if isset($input.auctionDbInfo) && $input.auctionDbInfo}
            <div class="alert alert-info" style="margin: 10px 0 0;">
                {$input.auctionDbInfo}
            </div>
        {/if}
	{/if}
{/block}

{block name="script"}
    var token = '{$token}';
    var XAllegro = new X13Allegro();
    XAllegro.auctionBind();
{/block}

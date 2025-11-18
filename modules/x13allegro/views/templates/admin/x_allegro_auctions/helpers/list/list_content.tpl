{extends file="helpers/list/list_content.tpl"}

{$has_bulk_actions = true}
{$bulk_actions = true}

{block name="td_content"}
    {if $key == 'image'}
        {if $tr.image}
            <img width="50" src="{$tr.image}" alt="{$tr.name}" class="label-tooltip" data-toggle="tooltip" data-html="true" data-placement="right" data-animation="true" data-original-title="&lt;img src=&quot;{$tr.image_large}&quot; &gt;">
        {else}
            <i class="icon-picture-o fa fa-picture-o" style="font-size: 50px; color: lightgrey"></i>
        {/if}
    {elseif $key == 'auto_renew'}
        {if $tr.id_product}
            <select name="auto_renew[{$tr.id_auction}]" data-id="{$tr.id_auction}" style="padding-left: 4px; padding-right: 4px;">
                <option value="" {if !is_numeric($tr.auto_renew)}selected="selected"{/if}>{l s='domyślnie' mod='x13allegro'}</option>
                <option value="1" {if is_numeric($tr.auto_renew) && $tr.auto_renew == 1}selected="selected"{/if}>{l s='tak' mod='x13allegro'}</option>
                <option value="0" {if is_numeric($tr.auto_renew) && ($tr.auto_renew == 0 || $tr.auto_renew == -1)}selected="selected"{/if}>{l s='nie' mod='x13allegro'}</option>
            </select>
            {if is_numeric($tr.auto_renew) && $tr.auto_renew == -1}
                <div class="label-tooltip auction-auto-renew-error" data-toggle="tooltip" data-placement="left" data-animation="true" data-original-title="Wystąpił błąd podczas automatycznego wznowienia, popraw błędy oferty i włącz automatyczne wznawianie ponownie.">
                    <i class="icon-exclamation-triangle"></i>&nbsp;{l s='błąd wznawiania' mod='x13allegro'}
                </div>
            {/if}
        {else}
            --
        {/if}
    {elseif $key == 'binded'}
        {if $tr.binded && $tr.binded_details}
            <i class="icon-check color"></i>
            {if $tr.binded_details.current_context && $tr.binded_details.current_context != $tr.binded_details.id_shop}
                <span class="label-tooltip" data-toggle="tooltip" data-original-title="Powiązana ze sklepem: {$tr.binded_details.shop_name}">S {$tr.binded_details.id_shop}</span>
            {/if}
        {else}
            <i class="icon-remove color"></i>
        {/if}
    {elseif $key == 'price'}
        {if $tr.format == 'AUCTION'}
            {if $tr.price_current > $tr.price_starting}
                {l s='aktualna' mod='x13allegro'}: {$tr.price_current|string_format:"%.2f"} zł
            {else}
                {l s='wywoławcza' mod='x13allegro'}: {$tr.price_starting|string_format:"%.2f"} zł
            {/if}

            {if $tr.price_minimal}<br>{l s='minimalna' mod='x13allegro'}: {$tr.price_minimal|string_format:"%.2f"} zł{/if}
            {if $tr.price}<br>{l s='kup teraz' mod='x13allegro'}: {$tr.price|string_format:"%.2f"} zł{/if}
        {else}
            {if $xallegroFilterMarketplace == 'all'}
                {$tr.marketplaces[$tr.base_marketplace].priceBuyNow} {$tr.marketplaces[$tr.base_marketplace].currencySign}

                {if !empty($tr.marketplaces)}
                    {foreach $tr.marketplaces as $marketplaceId => $marketplace}
                        {if $marketplaceId == $tr.base_marketplace}
                            {continue}
                        {/if}

                        <hr>{if $marketplace.priceBuyNow !== null}{$marketplace.priceBuyNow}{else}--{/if} {$marketplace.currencySign}
                    {/foreach}
                {/if}
            {else}
                {if $tr.marketplaces[$xallegroFilterMarketplace].priceBuyNow !== null}{$tr.marketplaces[$xallegroFilterMarketplace].priceBuyNow}{else}--{/if} {$tr.marketplaces[$xallegroFilterMarketplace].currencySign}

                {$priceTooltipContent = []}
                {if $tr.marketplaces|count > 1}
                    {$priceTooltipContent[] = "{$tr.marketplaces[$xallegroFilterMarketplace]['name']}: <b>{if $tr.marketplaces[$xallegroFilterMarketplace].priceBuyNow !== null}{$tr.marketplaces[$xallegroFilterMarketplace].priceBuyNow}{else}--{/if} {$tr.marketplaces[$xallegroFilterMarketplace].currencySign}</b>"}

                    {foreach $tr.marketplaces as $marketplaceId => $marketplace}
                        {if $marketplaceId == $xallegroFilterMarketplace}
                            {continue}
                        {/if}

                        {$priceTooltipContent[] = "{$marketplace.name}: <b>{if $marketplace.priceBuyNow !== null}{$marketplace.priceBuyNow}{else}--{/if} {$marketplace.currencySign}</b>"}
                    {/foreach}
                {/if}

                {if !empty($priceTooltipContent)}
                    <i class="icon-globe label-tooltip auction-marketplace-label" data-toggle="tooltip" data-html="true" data-original-title="{'<br>'|implode:$priceTooltipContent}"></i>
                {/if}
            {/if}
        {/if}
    {elseif $key == 'status' && $tr.marketplaces[$tr.base_marketplace]['status'] == 'ENDED'}
        {$tr.marketplaces[$tr.base_marketplace]['statusTranslated']}
    {elseif $key == 'sold' || $key == 'visits' || $key == 'status' || $key == 'marketplace'}
        {if $key == 'status'}
            {$columnKeyView = "statusTranslated"}
        {elseif $key == 'marketplace'}
            {$columnKeyView = "name"}
        {else}
            {$columnKeyView = $key}
        {/if}

        {if $xallegroFilterMarketplace == 'all'}
            {$tr.marketplaces[$tr.base_marketplace][$columnKeyView]}

            {if !empty($tr.marketplaces)}
                {foreach $tr.marketplaces as $marketplaceId => $marketplace}
                    {if $marketplaceId == $tr.base_marketplace}
                        {continue}
                    {/if}

                    {if $key == 'status' && $marketplace['status'] != 'APPROVED' && !empty($marketplace['statusDetails'])}
                        {call printTooltipStatusContent statusDetails=$marketplace['statusDetails'] assign='tooltipStatusContent'}
                    {else}
                        {$tooltipStatusContent = ""}
                    {/if}

                    <hr><span {if !empty($tooltipStatusContent)}class="badge badge-warning label-tooltip auction-marketplace-status-label" data-toggle="tooltip" data-html="true" data-original-title="{$tooltipStatusContent}"{/if}>{$marketplace[$columnKeyView]}</span>
                {/foreach}
            {/if}
        {else}
            {if $key == 'status' && $tr.marketplaces[$xallegroFilterMarketplace]['status'] != 'APPROVED' && !empty($tr.marketplaces[$xallegroFilterMarketplace]['statusDetails'])}
                {call printTooltipStatusContent statusDetails=$tr.marketplaces[$xallegroFilterMarketplace]['statusDetails'] assign='tooltipStatusContent'}
            {else}
                {$tooltipStatusContent = ""}
            {/if}

            <span {if !empty($tooltipStatusContent)}class="badge badge-warning label-tooltip auction-marketplace-status-label" data-toggle="tooltip" data-html="true" data-original-title="{$tooltipStatusContent}"{/if}>{$tr.marketplaces[$xallegroFilterMarketplace][$columnKeyView]}</span>

            {$tooltipContent = []}
            {if $tr.marketplaces|count > 1}
                {if $key == 'marketplace'}
                    {$tooltipContent[] = "{$tr.marketplaces[$xallegroFilterMarketplace][$columnKeyView]}"}
                {else}
                    {$tooltipContent[] = "{$tr.marketplaces[$xallegroFilterMarketplace]['name']}: <b>{$tr.marketplaces[$xallegroFilterMarketplace][$columnKeyView]}</b>"}
                {/if}

                {foreach $tr.marketplaces as $marketplaceId => $marketplace}
                    {if $marketplaceId == $xallegroFilterMarketplace}
                        {continue}
                    {/if}

                    {if $key == 'marketplace'}
                        {$tooltipContent[] = "{$marketplace[$columnKeyView]}"}
                    {else}
                        {$tooltipContent[] = "{$marketplace.name}: <b>{$marketplace[$columnKeyView]}</b>"}
                    {/if}
                {/foreach}
            {/if}

            {if !empty($tooltipContent)}
                <i class="icon-globe label-tooltip auction-marketplace-label" data-toggle="tooltip" data-html="true" data-original-title="{'<br>'|implode:$tooltipContent}"></i>
            {/if}
        {/if}
    {elseif $key == 'status_cz' || $key == 'status_sk' || $key == 'status_hu'}
        {if $tr.$key}
            {if $tr.marketplaces[$params.marketplace]['status'] != 'APPROVED' && !empty($tr.marketplaces[$params.marketplace]['statusDetails'])}
                {call printTooltipStatusContent statusDetails=$tr.marketplaces[$params.marketplace]['statusDetails'] assign='tooltipStatusContent'}
            {else}
                {$tooltipStatusContent = ""}
            {/if}

            <span {if !empty($tooltipStatusContent)}class="badge badge-warning label-tooltip auction-marketplace-status-label" data-toggle="tooltip" data-html="true" data-original-title="{$tooltipStatusContent}"{/if}>{$tr.$key}</span>
        {else}
            --
        {/if}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{function name=printTooltipStatusContent}
    {$content = "Ostatni odczytany status: <b>{$statusDetails['status']}</b> {if $statusDetails['statusDate']}<i>({$statusDetails['statusDate']})</i>{/if}"}

    {if !empty($statusDetails['statusRefusalReasons'])}
        {$content = $content|cat:"<br><br>Dodatkowe informacje:"}
        {foreach $statusDetails['statusRefusalReasons'] as $reason}
            {$content = $content|cat:"<br>$reason"}
        {/foreach}
    {/if}

    {$content}
{/function}

{extends file="helpers/list/list_content.tpl"}

{block name="td_content"}
    {if isset($params.type) && $params.type == 'auction_info'}
        {$tr.$key|escape:'html':'UTF-8'}
        {if isset($tr.auction_info)}
            <span class="xauction-info">
                {if $tr.auction_info.planned_nb}
                    {l s='Zaplanowano' mod='x13allegro'}: <b>{$tr.auction_info.planned_qty}</b> {l s='szt. na' mod='x13allegro'} <b>{$tr.auction_info.planned_nb}</b> {if $tr.auction_info.planned_nb == 1}{l s='ofertę' mod='x13allegro'}{elseif $tr.auction_info.planned_nb < 5}{l s='ofertach' mod='x13allegro'}{else}{l s='ofert' mod='x13allegro'}{/if}<br />
                {/if}
                {if $tr.auction_info.auctions_nb > 0}
                    {l s='Wystawiono' mod='x13allegro'}: <b>{$tr.auction_info.qty}</b> {l s='szt. na' mod='x13allegro'} <b>{$tr.auction_info.auctions_nb}</b> {if $tr.auction_info.auctions_nb == 1}{l s='ofertę' mod='x13allegro'}{elseif $tr.auction_info.planned_nb < 5}{l s='ofertach' mod='x13allegro'}{else}{l s='ofert' mod='x13allegro'}{/if}<br />
                {/if}
                {if $tr.auction_info.combinations_total > 0 && $tr.auction_info.combinations_nb > 0}{l s='Wystawionych kombinacji' mod='x13allegro'}: <b>{$tr.auction_info.combinations_nb}/{$tr.auction_info.combinations_total}</b>{/if}
            </span>
        {/if}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

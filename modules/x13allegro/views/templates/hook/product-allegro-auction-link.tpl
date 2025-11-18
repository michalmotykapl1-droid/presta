{if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '>')}
    {if isset($href) && !empty($href)}
        <div class="x13allegro-auction-link" style="margin-top: 10px;">
            <img src="{$allegro_img}" style="float: left; padding: 2px 4px 0 0;"><a href="{$href}" target="_blank" rel="nofollow">{l s='Zobacz na Allegro' mod='x13allegro'}</a>
        </div>
    {/if}
{else}
    <style>
        .x13allegro-auction-link a:before {
            content: url({$allegro_img});
        }
    </style>
    <li class="x13allegro-auction-link" style="display: none;">
        <a href="#" target="_blank" rel="nofollow" data-controller="{$link->getModuleLink('x13allegro', 'ajax')}">{l s='Zobacz na Allegro' mod='x13allegro'}</a>
    </li>
{/if}

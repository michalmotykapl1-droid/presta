{extends file="helpers/list/list_header.tpl"}

{$has_bulk_actions = true}
{$bulk_actions = true}

{block name="startForm"}
    <form method="post" action="{$override_action}" class="clearfix" id="form-{$list_id}">
{/block}

{block name="preTable"}
    <div class="row">
        <div class="col-sm-6">
            <div class="xallegro-helper-filters">
                <div class="form-group">
                    <label for="xallegroFilterStatus" class="control-label">{l s='Status oferty' mod='x13allegro'}</label>
                    <select id="xallegroFilterStatus" class="fixed-width-xl" name="xallegroFilterStatus">
                        <option value="all" {if $xallegroFilterStatus == 'all'}selected="selected"{/if}>{l s='wszystkie' mod='x13allegro'}</option>
                        <option value="inactive" {if $xallegroFilterStatus == 'inactive'}selected="selected"{/if}>{l s='szkic' mod='x13allegro'}</option>
                        <option value="active" {if $xallegroFilterStatus == 'active'}selected="selected"{/if}>{l s='aktywna' mod='x13allegro'}</option>
                        <option value="ended" {if $xallegroFilterStatus == 'ended'}selected="selected"{/if}>{l s='zakończona' mod='x13allegro'}</option>
                        <option value="activating" {if $xallegroFilterStatus == 'activating'}selected="selected"{/if}>{l s='zaplanowana' mod='x13allegro'}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="xallegroFilterMarketplace" class="control-label">{l s='Rynek' mod='x13allegro'}</label>
                    <select id="xallegroFilterMarketplace" class="fixed-width-xl" name="xallegroFilterMarketplace">
                        <option value="all" {if $xallegroFilterMarketplace == 'all'}selected="selected"{/if}>{l s='wszystkie' mod='x13allegro'}</option>
                        {foreach $marketplaceFilters as $marketplace}
                            <option value="{$marketplace.id}" {if $xallegroFilterMarketplace == $marketplace.id}selected="selected"{/if}>{$marketplace.name}</option>
                        {/foreach}
                    </select>
                </div>
            </div>
        </div>

        <div class="col-sm-6 text-right">
            <div class="xallegro-helper-buttons">
                <a class="btn btn-info" href="#" id="showProductizationTools">
                    <i class="icon-cogs"></i> {l s='Produktyzacja' mod='x13allegro'}
                </a>
                <a class="btn btn-warning" id="bulkUpdateAuctionsTrigger">
                    <i class="icon-cogs"></i> {l s='Aktualizuj zaznaczone' mod='x13allegro'}
                </a>
                <a class="btn btn-default" id="auctionListSettings">
                    <i class="icon-cogs"></i> {l s='Ustawienia listy' mod='x13allegro'}
                </a>
            </div>
        </div>
    </div>
    <div class="row" id="productizationTools" {if $filterByProductization eq false}style="display: none;"{/if}>
        <div class="col-sm-6">
            
            <h4>{l s='Katalog Allegro (produktyzacja)' mod='x13allegro'}</h4>
            <div class="alert alert-info">
                {l s='W celu ułatwienia spełnienia wymogów Allegro w ramach połączenia Państwa ofert z ich Katalogiem Allegro, umożliwiamy pokazanie tych ofert, które znajdują się w kategoriach gdzie jest to wymagane, a jeszcze nie są z nim połączone. Więcej informacji:' mod='x13allegro'} <a href="https://allegro.pl/dla-sprzedajacych/kategoria/produktyzacja">https://allegro.pl/dla-sprzedajacych/kategoria/produktyzacja</a>
            </div>
            
            <div>
                {if $filterByProductization eq false}
                <a class="btn btn-primary" href="{$currentIndex}&filterByProductization=1">
                    {l s='Pokaż oferty, które mogą wymagać powiązania z Katalogiem Allegro' mod='x13allegro'}
                </a>
                {else}
                <a class="btn btn-warning" href="{$currentIndex}&resetFilterByProductization=1">
                    <i class="icon-eraser"></i> {l s='Wyczyść filtrowanie dotyczące Katalogu Allegro' mod='x13allegro'}
                </a>
                {/if}
            </div>
            <hr>
        </div>
    </div>
{/block}

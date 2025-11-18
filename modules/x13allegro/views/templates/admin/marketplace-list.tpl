<div class="x13allegro-delivery-marketplace-list delivery-marketplace-list">
    <p>Ofertę z wybranym cennikiem udostępnisz na rynkach:</p>

    {foreach $marketplaces as $marketplace}
        <div class="delivery-marketplace" data-marketplace-id="{$marketplace.id}" {if !in_array($marketplace.id, $availableMarketplaces)}style="display: none;"{/if}>
            <img alt="" src="../modules/x13allegro/img/flag-{$marketplace.countryCode|lower}.svg">
            {$marketplace.name|regex_replace:"/^(\w+)/u":"<b>$1</b>"}
        </div>
    {/foreach}
    <p class="x13allegro-delivery-marketplace-list-small">Przed pojawieniem się oferty na tych rynkach, sprawdzimy, czy spełnia wszystkie warunki sprzedaży. <br/> Wynik weryfikacji znajdziesz w zakładce "Mój asortyment" w ciągu 30 minut od aktywacji oferty.</p>
</div>

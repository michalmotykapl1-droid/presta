<p>{$level}&nbsp;<strong>{$type}</strong></p>
<p>
    <span class="x13allegro_black">{l s='Środowisko' mod='x13allegro'}:</span> {$env}<br/>

    {if isset($allegroAccount)}
        <span class="x13allegro_black">{l s='Konto Allegro' mod='x13allegro'}:</span> {$allegroAccount}<br/>
    {/if}

    {if isset($employee)}
        <span class="x13allegro_black">{l s='Przypisany pracownik' mod='x13allegro'}:</span> {$employee}<br/>
    {/if}

    {if isset($product)}
        <span class="x13allegro_black">{l s='Produkt' mod='x13allegro'}:</span> {$product}<br/>
    {/if}

    {if isset($order)}
        <span class="x13allegro_black">{l s='Zamówienie' mod='x13allegro'}:</span> {$order}<br/>
    {/if}

    <span class="x13allegro_black">{l s='Data ostatniego wystąpienia' mod='x13allegro'}:</span> {$last_occurrence}
</p>

<hr>
{if isset($isJson) && $isJson}<pre>{$message}</pre>{else}<p>{$message}</p>{/if}

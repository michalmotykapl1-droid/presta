<div class="panel">
    <h3><i class="icon-list-alt"></i> {l s='Zmienione produkty' mod='geminicontent'}</h3>
    
    <div class="alert alert-info">
        {l s='Na tej liście znajdują się produkty, dla których wygenerowano przynajmniej jeden element (opis lub metadane SEO). Możesz stąd zarządzać brakującymi treściami.' mod='geminicontent'}
    </div>

    <table class="table table-bordered table-hover">
        <thead>
            <tr class="nodrag nodrop">
                <th class="fixed-width-xs text-center">{l s='ID' mod='geminicontent'}</th>
                <th>{l s='Nazwa produktu' mod='geminicontent'}</th>
                <th class="fixed-width-lg">{l s='SKU (Indeks)' mod='geminicontent'}</th>
                <th class="fixed-width-lg text-center">{l s='Status Opisu' mod='geminicontent'}</th>
                <th class="fixed-width-lg text-center">{l s='Status SEO' mod='geminicontent'}</th>
                <th class="fixed-width-xl text-center">{l s='Akcje' mod='geminicontent'}</th>
            </tr>
        </thead>
        <tbody>
            {foreach from=$logs item=log}
                <tr id="product-row-{$log.id_product}">
                    <td class="text-center">{$log.id_product}</td>
                    <td><a href="{$link->getAdminLink('AdminProducts', true, ['id_product' => $log.id_product, 'updateproduct' => '1'])|escape:'html':'UTF-8'}" target="_blank">{$log.name}</a></td>
                    <td>{$log.reference}</td>
                    <td class="text-center">
                        {if $log.description_generated}
                            <span class="label color_field" style="background-color:#389938;">
                                <i class="icon-check"></i> {l s='OK' mod='geminicontent'}
                            </span>
                            {if $log.description_date_add && $log.description_date_add != '0000-00-00 00:00:00'}<br><small>{$log.description_date_add|date_format:"%Y-%m-%d"}</small>{/if}
                        {else}
                            <span class="label color_field" style="background-color:#E53935;">
                                <i class="icon-times"></i> {l s='Brak' mod='geminicontent'}
                            </span>
                        {/if}
                    </td>
                    <td class="text-center">
                        {if $log.seo_generated}
                            <span class="label color_field" style="background-color:#389938;">
                                <i class="icon-check"></i> {l s='OK' mod='geminicontent'}
                            </span>
                            {if $log.seo_date_add && $log.seo_date_add != '0000-00-00 00:00:00'}<br><small>{$log.seo_date_add|date_format:"%Y-%m-%d"}</small>{/if}
                        {else}
                            <span class="label color_field" style="background-color:#E53935;">
                                <i class="icon-times"></i> {l s='Brak' mod='geminicontent'}
                            </span>
                        {/if}
                    </td>
                    <td class="text-center">
                        {if !$log.description_generated}
                            <button type="button" class="btn btn-sm btn-default js-generate-description" data-id-product="{$log.id_product}"><i class="icon-magic"></i> {l s='Generuj Opis' mod='geminicontent'}</button>
                        {/if}
                        {if !$log.seo_generated}
                             <button type="button" class="btn btn-sm btn-default js-generate-seo" data-id-product="{$log.id_product}"><i class="icon-search"></i> {l s='Generuj SEO' mod='geminicontent'}</button>
                        {/if}
                        {if $log.description_generated && $log.seo_generated}
                             <span class="text-success">{l s='Ukończono' mod='geminicontent'}</span>
                        {/if}
                    </td>
                </tr>
            {foreachelse}
                <tr>
                    <td colspan="6" class="text-center" style="padding: 20px;">
                        {l s='Dziennik jest pusty. Żaden opis ani metadane nie zostały jeszcze zapisane.' mod='geminicontent'}
                    </td>
                </tr>
            {/foreach}
        </tbody>
    </table>
</div>

{$isModernLayout = version_compare($smarty.const._PS_VERSION_, '1.7.7', '>=')}
<div id="xallegro_order_details" class="{if $isModernLayout}card mt-2 d-print-none{else}panel bootstrap{/if}">
    {if $isModernLayout}
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h3 class="card-header-title">
                        {l s='Szczegóły zamówienia Allegro' mod='x13allegro'}
                    </h3>
                </div>
            </div>
         </div>
    {else}
        <h3>
            <img alt="" src="../modules/x13allegro/img/AdminXAllegroMain.png" style="width: 14px; position: relative; top: -1px;"> {l s='Szczegóły zamówienia Allegro' mod='x13allegro'}
        </h3>
    {/if}

    {if $isModernLayout}<div class="card-body">{/if}

    {if $allegroOrder->event_type == 'FILLED_IN'}
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-warning">
                    <ul class="list-unstyled">
                        <li>
                            <b>{l s='Zamówienie jest w trakcie przetwarzania przez serwis Allegro!' mod='x13allegro'}</b><br />
                            {l s='Wszelkie dane zamawiającego mogą ulec zmianie.' mod='x13allegro'}
                        </li>
                    </ul>
                </div>
                {if $orderStateId == $ALLEGRO_STATUS_FILLED_IN}
                    <script>
                        alert("{l s='Zamówienie jest w trakcie przetwarzania przez serwis Allegro!' mod='x13allegro'}\n{l s='Prosimy go jeszcze nie obsługiwać!' mod='x13allegro'}");
                    </script>
                {/if}
            </div>
        </div>
    {/if}

    <div class="panel info-block">
        <div class="row">
            <div class="col-lg-12">
                <div class="row">
                    <label class="col-lg-2"><strong>{l s='Status zamówienia' mod='x13allegro'}:</strong></label>

                    {if !in_array($allegroOrder->event_type, $unsupportedEvents)}
                        <div class="col-lg-2">
                            <select name="xallegro_fulfillment_status" class="custom-select">
                                <option value="NEW" {if $allegroOrder->fulfillment_status == 'NEW'}{/if}>{l s='Nowe' mod='x13allegro'}</option>
                                {foreach $fulfilmentStatuses as $fulfilmentStatus}
                                    <option value="{$fulfilmentStatus.id}" {if $allegroOrder->fulfillment_status == $fulfilmentStatus.id}selected="selected"{/if}>{$fulfilmentStatus.name}</option>
                                {/foreach}
                            </select>
                        </div>
                        <div class="col-lg-2">
                            <a href="#" class="xallegro-fulfillment-status btn btn-primary" data-orderId="{$allegroOrder->id_order}">{l s='Aktualizuj status' mod='x13allegro'}</a>
                        </div>
                    {else}
                        <div class="col-lg-2">
                            {if array_key_exists($allegroOrder->fulfillment_status, $fulfilmentStatuses)}
                                {$fulfilmentStatuses[$allegroOrder->fulfillment_status].name}
                            {else}
                                {l s='Nowe' mod='x13allegro'}
                            {/if}
                        </div>
                    {/if}
                </div>
            </div>
        </div>
    </div>

    <div class="panel info-block mt-2">
        <div class="row">
            <div class="col-lg-6">
                <div class="row">
                    <h4 class="col-lg-4">{l s='Konto' mod='x13allegro'}:</h4>
                    <h4 class="col-lg-8">{$allegroAccount->username} <small><i>({$allegroAccount->id})</i></small></h4>
                </div>
                <div class="row">
                    <h4 class="col-lg-4">{l s='Nr zamówienia' mod='x13allegro'}:</h4>
                    <h4 class="col-lg-8"><a href="{$salesCenterOrderUrl}" target="_blank" rel="nofollow">{$allegroOrder->checkout_form_content->id}</a></h4>
                </div>
                {if isset($allegroOrder->checkout_form_content->marketplace)}
                    <div class="row">
                        <h4 class="col-lg-4">{l s='Rynek' mod='x13allegro'}:</h4>
                        <h4 class="col-lg-8">{$allegroOrder->checkout_form_content->marketplace->id}</h4>
                    </div>
                {/if}
                {if !empty($allegroOrder->checkout_form_content->lineItems->items)}
                    {foreach $allegroOrder->checkout_form_content->lineItems->items as $item}
                        {if $item@first}
                            {assign var="saleDate" value=$item->boughtAt}
                            {break}
                        {/if}
                    {/foreach}

                    <div class="row">
                        <h4 class="col-lg-4">{l s='Data sprzedaży' mod='x13allegro'}:</h4>
                        <h4 class="col-lg-8">
                            {$saleDate|date_format:"%Y-%m-%d %H:%M"}<br />
                            <small><i>{l s='ostatnia zmiana' mod='x13allegro'}: {$allegroOrder->checkout_form_content->updatedAt|date_format:"%Y-%m-%d %H:%M"}</i></small>
                        </h4>
                    </div>
                {/if}
            </div>

            <div class="col-lg-6">
                <h3>{l s='Kupujący' mod='x13allegro'}</h3>

                <div class="row">
                    <div class="col-lg-5">{l s='Login' mod='x13allegro'}:</div>
                    <div class="col-lg-7">{$allegroOrder->checkout_form_content->buyer->login} <small><i>({$allegroOrder->checkout_form_content->buyer->id})</i></small></div>
                </div>
                <div class="row mt-2">
                    <div class="col-lg-5">{l s='E-mail' mod='x13allegro'}:</div>
                    <div class="col-lg-7"><small>{$allegroOrder->checkout_form_content->buyer->email}</small></div>
                </div>
                <div class="row mt-2">
                    <div class="col-lg-5">{l s='Faktura' mod='x13allegro'}:</div>
                    <div class="col-lg-7">
                        {if $allegroOrder->checkout_form_content->invoice->required}
                            {l s='Tak' mod='x13allegro'}{if $allegroOrder->checkout_form_content->invoice->address && $allegroOrder->checkout_form_content->invoice->address->naturalPerson} - {l s='imienna' mod='x13allegro'}{/if}
                        {else}
                            {l s='Nie' mod='x13allegro'}
                        {/if}
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-lg-5">{l s='Sposób płatności' mod='x13allegro'}:</div>
                    <div class="col-lg-7">{$allegroOrder->checkout_form_content->payment->type}</div>
                </div>

                {if $allegroOrder->checkout_form_content->messageToSeller}
                    <div class="row mt-2">
                        <div class="col-lg-5"><b>{l s='Wiadomość od kupującego' mod='x13allegro'}:</b></div>
                        <div class="col-lg-7">{$allegroOrder->checkout_form_content->messageToSeller|nl2br}</div>
                    </div>
                {/if}
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="panel info-block mt-2">
                <div class="clearfix">
                    <h3 class="float-left">
                        {l s='Dowód zakupu' mod='x13allegro'}

                        {if $allegroOrder->checkout_form_content->invoice->required}
                            <small class="badge badge-info">{l s='faktura' mod='x13allegro'}{if $allegroOrder->checkout_form_content->invoice->address && $allegroOrder->checkout_form_content->invoice->address->naturalPerson} {l s='imienna' mod='x13allegro'}{/if}</small>
                        {else}
                            <small>{l s='paragon' mod='x13allegro'}</small>
                        {/if}
                    </h3>

                    <a href="#" id="xallegro_order_invoice_add" class="btn btn-primary btn-sm float-right">
                        {if $allegroOrder->checkout_form_content->invoice->required}
                            {l s='Dodaj fakturę' mod='x13allegro'}
                        {else}
                            {l s='Dodaj dowód zakupu' mod='x13allegro'}
                        {/if}
                    </a>
                </div>

                {if !empty($allegroInvoices)}
                    <div class="row">
                        <div class="col-lg-12">
                            <table class="table mt-2">
                                {foreach $allegroInvoices as $allegroInvoice}
                                    <tr>
                                        <td>{$allegroInvoice->file->name}</td>
                                        <td>{$allegroInvoice->invoiceNumber}</td>
                                        <td>
                                            {if !$allegroInvoice->file->uploadedAt}
                                                <span class="badge badge-danger">{l s='nie przesłano pliku' mod='x13allegro'}</span>
                                            {elseif $allegroInvoice->file->securityVerification->status == 'WAITING'}
                                                <span class="badge badge-warning">{l s='trwa sprawdzanie' mod='x13allegro'}</span>
                                            {elseif $allegroInvoice->file->securityVerification->status == 'REJECTED'}
                                                <span class="badge badge-danger">{l s='plik odrzucony' mod='x13allegro'}</span>
                                            {/if}
                                        </td>
                                    </tr>
                                {/foreach}
                            </table>
                        </div>
                    </div>
                {/if}
            </div>

            {if !empty($allegroOrder->checkout_form_content->lineItems->items)}
                {assign var="itemsNotMapped" value=[]}

                <div class="panel info-block mt-2">
                    <h3>{l s='Lista ofert' mod='x13allegro'}</h3>

                    <table class="table">
                        <thead>
                            <tr>
                                <th colspan="2">{l s='Oferty w zamówieniu' mod='x13allegro'}</th>
                                <th>{l s='cena jedn.' mod='x13allegro'}</th>
                                <th>{l s='szt.' mod='x13allegro'}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {foreach $allegroOrder->checkout_form_content->lineItems->items as $item}
                                {if $item->offer->id|in_array:$allegroOrder->checkout_form_content->lineItems->itemsNotMapped}
                                    {$itemsNotMapped[] = $item}
                                {/if}

                                <tr>
                                    <td width="100"><a href="{$marketplaceProvider->getMarketplaceOfferUrl($item->offer->id, $allegroAccount->sandbox)}" target="_blank" rel="nofollow">{$item->offer->id}</a></td>
                                    <td>{$item->offer->name}</td>
                                    <td>{$item->price} {$marketplaceProvider->getMarketplaceCurrency()->sign}</td>
                                    <td>{$item->quantity}</td>
                                </tr>

                                {if !empty($item->selectedAdditionalServices)}
                                    {foreach $item->selectedAdditionalServices as $additionalService}
                                        <tr>
                                            <td width="100"></td>
                                            <td>{$additionalService->name}</td>
                                            <td>{$additionalService->price->amount} {$marketplaceProvider->getMarketplaceCurrency()->sign}</td>
                                            <td>{$additionalService->quantity}</td>
                                        </tr>
                                    {/foreach}
                                {/if}
                            {/foreach}
                        </tbody>
                    </table>

                    {if !empty($itemsNotMapped)}
                        <h4>{l s='Nieodnalezione powiązania' mod='x13allegro'}</h4>
                        <table class="table">
                            <thead>
                                <tr>
                                    <th colspan="2">{l s='Oferta' mod='x13allegro'}</th>
                                    <th>{l s='cena jedn.' mod='x13allegro'}</th>
                                    <th>{l s='szt.' mod='x13allegro'}</th>
                                </tr>
                            </thead>
                            <tbody>
                                {foreach $itemsNotMapped as $item}
                                    <tr>
                                        <td width="100"><a href="{$marketplaceProvider->getMarketplaceOfferUrl($item->offer->id, $allegroAccount->sandbox)}" target="_blank" rel="nofollow">{$item->offer->id}</a></td>
                                        <td>{$item->offer->name}</td>
                                        <td>{$item->price} {$marketplaceProvider->getMarketplaceCurrency()->sign}</td>
                                        <td>{$item->quantity}</td>
                                    </tr>

                                    {if !empty($item->selectedAdditionalServices)}
                                        {foreach $item->selectedAdditionalServices as $additionalService}
                                            <tr>
                                                <td width="100"></td>
                                                <td>{$additionalService->name}</td>
                                                <td>{$additionalService->price->amount} {$marketplaceProvider->getMarketplaceCurrency()->sign}</td>
                                                <td>{$additionalService->quantity}</td>
                                            </tr>
                                        {/foreach}
                                    {/if}
                                {/foreach}
                            </tbody>
                        </table>
                    {/if}
                </div>
            {/if}
        </div>

        <div class="col-lg-6">
            <div class="panel info-block mt-2">
                <h3>{l s='Dane dostawy' mod='x13allegro'}</h3>

                <div class="row">
                    <div class="col-lg-5">{l s='Metoda dostawy' mod='x13allegro'}:</div>
                    <div class="col-lg-7">{$allegroOrder->checkout_form_content->delivery->method->name}<br /><small><i>({$allegroOrder->checkout_form_content->delivery->method->id})</i></small></div>
                </div>
                {if $allegroOrder->checkout_form_content->delivery->pickupPoint}
                    <div class="row mt-2">
                        <div class="col-lg-5">{l s='Punkt odbioru' mod='x13allegro'}:</div>
                        <div class="col-lg-7">
                            {$allegroOrder->checkout_form_content->delivery->pickupPoint->name} <small><i>({$allegroOrder->checkout_form_content->delivery->pickupPoint->id})</i></small><br />
                            {if $allegroOrder->checkout_form_content->delivery->pickupPoint->description}<small><i>{$allegroOrder->checkout_form_content->delivery->pickupPoint->description}</i></small><br />{/if}
                            {$allegroOrder->checkout_form_content->delivery->pickupPoint->address->street}<br />
                            {$allegroOrder->checkout_form_content->delivery->pickupPoint->address->zipCode} {$allegroOrder->checkout_form_content->delivery->pickupPoint->address->city}
                            {if isset($allegroOrder->checkout_form_content->delivery->pickupPoint->address->countryCode)}<br />{$countries[$allegroOrder->checkout_form_content->delivery->pickupPoint->address->countryCode]}{/if}
                        </div>
                    </div>
                {/if}
                <div class="row mt-2">
                    <div class="col-lg-5">{l s='Opcja SMART' mod='x13allegro'}:</div>
                    <div class="col-lg-7">{if $allegroOrder->checkout_form_content->delivery->smart}Tak{else}Nie{/if}</div>
                </div>
                <div class="row mt-2">
                    <div class="col-lg-5">{l s='Liczba paczek' mod='x13allegro'}:</div>
                    <div class="col-lg-7">{$allegroOrder->checkout_form_content->delivery->calculatedNumberOfPackages}</div>
                </div>
                {if $allegroOrder->checkout_form_content->delivery->time->dispatch}
                    <div class="row mt-2">
                        <div class="col-lg-5">{l s='Czas na wysłanie' mod='x13allegro'}:</div>
                        <div class="col-lg-7">
                            {* {l s='od' mod='x13allegro'}: {$allegroOrder->checkout_form_content->delivery->time->dispatch->from|date_format:"%a., %d %b %Y, %H:%M"}<br /> *}
                            {l s='do' mod='x13allegro'}: {$allegroOrder->checkout_form_content->delivery->time->dispatch->to|date_format:"%a., %d %b %Y, %H:%M"}
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-lg-5">{l s='Przewidywany czas dostawy' mod='x13allegro'}:</div>
                        <div class="col-lg-7">
                            {* {l s='od' mod='x13allegro'}: {$allegroOrder->checkout_form_content->delivery->time->from|date_format:"%a., %d %b %Y, %H:%M"}<br /> *}
                            {$allegroOrder->checkout_form_content->delivery->time->to|date_format:"%a., %d %b %Y"}
                        </div>
                    </div>
                {/if}
            </div>
        </div>
    </div>

    {if $isModernLayout}</div>{/if}
</div>

<div class="modal" id="xallegro_order_invoice_modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="post" action="" class="form-horizontal bootstrap">
                <div class="modal-header x13allegro-modal-header">
                    <h4 class="x13allegro-modal-title">
                        {if $allegroOrder->checkout_form_content->invoice->required}
                            {l s='Dodaj fakturę' mod='x13allegro'}
                        {else}
                            {l s='Dodaj dowód zakupu' mod='x13allegro'}
                        {/if}
                    </h4>
                    <button type="button" class="close x13allegro-modal-close" data-dismiss="modal"><span>&times;</span></button>
                </div>

                <div class="modal-body x13allegro-modal-body">
                    <div class="alert alert-danger xallegro-order-invoice-error" style="display: none;"></div>

                    <div class="form-group">
                        <select name="xallegro_order_invoice_type" class="form-control">
                            <option value="file" selected="selected">
                                {if $allegroOrder->checkout_form_content->invoice->required}
                                    {l s='Prześlij fakturę z pliku' mod='x13allegro'}
                                {else}
                                    {l s='Prześlij dowód zakupu z pliku' mod='x13allegro'}
                                {/if}
                            </option>
                            <option value="prestashop">
                                {if $allegroOrder->checkout_form_content->invoice->required}
                                    {l s='Prześlij fakturę wygenerowaną przez PrestaShop' mod='x13allegro'}
                                {else}
                                    {l s='Prześlij dowód zakupu wygenerowany przez PrestaShop' mod='x13allegro'}
                                {/if}
                            </option>
                        </select>
                    </div>

                    <div id="xallegro_order_invoice_type_file">
                        <div class="form-group">
                            <label class="form-control-label">{l s='Załącz plik PDF, o rozmiarze maksymalnie 3MB' mod='x13allegro'}</label>

                            <button type="button" id="xallegro_order_invoice_file_button" class="btn btn-primary">{l s='Załącz plik' mod='x13allegro'}</button>
                            <span id="xallegro_order_invoice_file_desc"></span>
                            <input type="file" accept="application/pdf" name="xallegro_order_invoice_file" style="display: none;">
                        </div>
                        <div class="form-group">
                            <label for="xallegro_order_invoice_number">
                                {if $allegroOrder->checkout_form_content->invoice->required}
                                    {l s='Numer faktury' mod='x13allegro'}
                                {else}
                                    {l s='Numer dowodu zakupu' mod='x13allegro'}
                                {/if}
                                <small>({l s='opcjonalnie' mod='x13allegro'})</small>
                            </label>
                            <input type="text" name="xallegro_order_invoice_number" id="xallegro_order_invoice_number" class="form-control">
                        </div>
                    </div>

                    <div id="xallegro_order_invoice_type_prestashop" data-order-has-invoice="{$orderHasInvoice|intval}" style="display: none;">
                        <div class="form-group">
                            {if !$orderHasInvoice}
                                <div class="alert alert-warning">{l s='Brak dokumentów wygenerowanych przez PrestaShop' mod='x13allegro'}</div>
                            {/if}
                        </div>
                    </div>
                </div>

                <div class="modal-footer x13allegro-modal-footer">
                    <button type="button" id="xallegro_order_invoice_cancel" class="btn btn-left btn-default" data-dismiss="modal">{l s='Anuluj' mod='x13allegro'}</button>
                    <button type="button" id="xallegro_order_invoice_submit" class="btn btn-primary" data-order-id="{$allegroOrder->id_order}">
                        {if $allegroOrder->checkout_form_content->invoice->required}
                            {l s='Dodaj fakturę' mod='x13allegro'}
                        {else}
                            {l s='Dodaj dowod zakupu' mod='x13allegro'}
                        {/if}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="text/javascript">
    var XAllegro = new X13Allegro();
    XAllegro.ajaxUrl = "{$link->getAdminLink('AdminXAllegroOrderMain', false)}";
    XAllegro.ajaxToken = "{$orderMainToken}";
    XAllegro.orderFulfillmentStatus();
    XAllegro.orderInvoice();
</script>

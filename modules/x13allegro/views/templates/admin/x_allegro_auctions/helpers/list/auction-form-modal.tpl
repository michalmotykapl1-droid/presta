{*
* 2007-2023 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
* @author      PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2023 PrestaShop SA
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}

{* POPRAWKA: Dodano styl, aby okno modalne miało scrollbar, gdy zawartość się nie mieści *}
<style>
    .x13allegro-modal-body {
        max-height: 60vh;
        overflow-y: auto;
    }
</style>

{* POPRAWKA: Dodano klasę .x13allegro-modal do głównego kontenera *}
<div class="modal-dialog {if $formAction == 'update'}modal-lg{/if} x13allegro-modal" role="document">
    <form action="#" method="post">
        <input type="hidden" name="action" value="auction{$formAction|ucfirst}">
        <input type="hidden" name="id_xallegro_account" value="{$allegroAccountId}">

        <div class="modal-content">
            <div class="modal-header x13allegro-modal-header">
                <button type="button" class="close x13allegro-modal-close" data-dismiss="modal"><span>&times;</span></button>
            
                <h4 class="x13allegro-modal-title">
                    {if $formAction == 'finish'}
                        {l s='Zakończ oferty' mod='x13allegro'}
                    {elseif $formAction == 'redo'}
                        {l s='Wznów oferty' mod='x13allegro'}
                    {elseif $formAction == 'auto_renew'}
                        {l s='Ustaw opcje auto wznawiania' mod='x13allegro'}
                    {elseif $formAction == 'update'}
                        {l s='Masowa aktualizacja ofert' mod='x13allegro'}
                    {/if}
                </h4>

                {if $formAction == 'update'}
                    <h6 class="x13allegro-modal-title-small">{l s='Wybranych ofert do aktualizacji' mod='x13allegro'}: <span class="badge">{$auctions|count}</span></h6>
                {/if}
            </div>

            <div class="modal-body x13allegro-modal-body">
                <table class="table x-auction-form-list{if $formAction != 'redo'} x-auction-form-list-hidden{/if}" {if $formAction == 'update' && $auctions|count > 5}style="display: none;"{/if}>
                    <colgroup>
                        <col>
                        {if $formAction == 'redo'}
                            <col width="120px">
                            <col width="140px">
                        {/if}
                        <col width="25px">
                    </colgroup>
                    <thead>
                        <tr>
                            <th></th>
                            {if $formAction == 'redo'}
                                <th>{l s='Wznawianie' mod='x13allegro'}</th>
                                <th>{l s='Ilość po wznowieniu' mod='x13allegro'}</th>
                            {/if}
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach $auctions as $auction}
                            <tr data-id="{$auction.id_auction}" {if $formAction == 'redo' && isset($auction.redoData.auctionDisabled) && $auction.redoData.auctionDisabled} class="x-auction-form-list-disabled"{/if}>
                                <td>
                                    <input type="hidden" name="xallegro_auction_id[{$auction.id_auction}]" data-name="xAllegroAuctionId" value="1" {if $formAction == 'redo' && isset($auction.redoData.auctionDisabled) && $auction.redoData.auctionDisabled}disabled="disabled"{/if}>

                                    {if $formAction == 'redo' && isset($auction.redoData.auctionDisabled) && $auction.redoData.auctionDisabled}
                                        <span class="icon-warning text-danger label-tooltip" data-toggle="tooltip" data-original-title="{l s='Brak odpowiedniej ilości produktu w sklepie, lub produkt jest nieaktywny' mod='x13allegro'}"></span>
                                    {/if}

                                    <strong>{$auction.title}</strong>&nbsp;
                                    <small><i><a href="{$auction.href}" target="_blank" rel="nofollow">{$auction.id_auction}</a></i></small>

                                    {if isset($auction.redoData.status) && $auction.redoData.status}
                                        {if $auction.redoData.status == 1}
                                            {$activeOffersTxt = 'aktywną ofertę'}
                                        {elseif $auction.redoData.status < 5}
                                            {$activeOffersTxt = 'aktywne oferty'}
                                        {else}
                                            {$activeOffersTxt = 'aktywnych ofert'}
                                        {/if}

                                        <span class="badge badge-warning label-tooltip" data-toggle="tooltip" data-original-title="Powiązany produkt/kombinacja ma już {$auction.redoData.status} {$activeOffersTxt}">
                                            <span style="cursor: default"><i class="icon-warning"></i> {$auction.redoData.status}</span>
                                        </span>
                                    {/if}
                                </td>
                                {if $formAction == 'redo' && isset($auction.redoData)}
                                    <td>
                                        <select name="xallegro_auction_auto_renew[{$auction.id_auction}]" data-name="xAllegroAuctionAutoRenew" {if $auction.redoData.auctionDisabled}disabled="disabled"{/if}>
                                            <option value="">{l s='domyślnie' mod='x13allegro'}</option>
                                            <option value="1">{l s='tak' mod='x13allegro'}</option>
                                            <option value="0">{l s='nie' mod='x13allegro'}</option>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="text" name="xallegro_auction_quantity[{$auction.id_auction}]" data-name="xAllegroAuctionQuantity" value="{$auction.redoData.auctionQuantity}" data-max="{$auction.redoData.auctionQuantityMax}" data-oos="{$auction.redoData.productOOS}" data-cast="integer" {if $auction.redoData.auctionDisabled}disabled="disabled"{/if}>
                                        <small>/ {$auction.redoData.productQuantity}</small>
                                    </td>
                                {/if}
                                <td style="text-align: right;">
                                    {if $formAction != 'update'}<a class="x-auction-form-list-delete"><i class="icon-times"></i></a>{/if}
                                </td>
                            </tr>
                        {/foreach}
                    </tbody>
                </table>

                <div class="x-updater-progress">
                    <div class="clearfix">
                        <h4 class="x-updater-start-title pull-left">{l s='Trwa aktualizacja' mod='x13allegro'}...</h4>
                        <h4 class="x-updater-end-title pull-left">{l s='Aktualizacja zakończona' mod='x13allegro'}</h4>
                        <a class="btn btn-success pull-right x-updater-redo-btn" href="#">{l s='Aktualizuj inną opcję' mod='x13allegro'}</a>
                    </div>

                    <div class="x-updater-progress-bar">
                        <span class="x-updater-progress-bar-fill"></span>
                    </div>

                    <p class="x-updater-progress-bar-data">
                        {l s='Zaktualizowano' mod='x13allegro'}
                        <span class="x-updater-progress-from">0</span> z <span class="x-updater-progress-to">0</span>
                    </p>
                </div>

                <div class="x-updater-finish-message"></div>
                <div class="x-updater-error-message alert alert-danger"></div>

                <div class="x-updater-logger">
                    <hr>
                    <div class="clearfix">
                        <h4 class="pull-left">{l s='Dziennik zdarzeń' mod='x13allegro'}</h4>
                        <a class="btn btn-danger pull-right x-updater-logger-with-errors" href="#">{l s='Pokaż błędy' mod='x13allegro'}</a>
                        <a class="btn btn-warning pull-right x-updater-logger-with-warnings" href="#">{l s='Pokaż ostrzeżenia' mod='x13allegro'}</a>
                        <a class="btn btn-default pull-right x-updater-logger-all" href="#">{l s='Pokaż wszystko' mod='x13allegro'}</a>
                    </div>
                    <ul class="x-updater-logger-content"></ul>
                </div>

                {if $formAction == 'auto_renew'}
                    <div class="form-group row">
                        <label class="control-label col-lg-3">
                            {l s='Auto wznawianie' mod='x13allegro'}
                        </label>
                        <div class="col-lg-9">
                            <select name="allegro_auto_renew">
                                <option value="">{l s='domyślnie' mod='x13allegro'}</option>
                                <option value="1">{l s='tak' mod='x13allegro'}</option>
                                <option value="0">{l s='nie' mod='x13allegro'}</option>
                            </select>
                        </div>
                    </div>
                {elseif $formAction == 'update'}
                    <div class="x-updater-methods">
                        <h4>{l s='Wybierz akcje' mod='x13allegro'}</h4>

                        <div class="form-group">
                            <select x-name="update-auction-entity" id="x-auction-update-action" class="form-control">
                                <option value="0"> -- wybierz --</option>
                                {foreach $availableUpdateEntities as $entity}
                                    <option value="{$entity.name}">{$entity.desc}</option>
                                {/foreach}
                                <option value="bb_link_sku">Powiąż wg SKU (BB)</option>
                                <option value="bb_link_eanprefix">Powiąż wg EAN + prefiks SKU (BB)</option>
                                <option value="bbGetEanAndSaveToFile">Pobierz EAN z Allegro (zapis do pliku)</option>
                                <option value="bbFetchVatAndSaveToDb">Pobierz VAT z Allegro (do bazy)</option>
                            </select>
                        </div>

                        <div class="x-updater-extra-settings">
                            {foreach $availableUpdateEntities as $entity}
                                <div id="updater_entity_{$entity.name}" class="x-updater-entity">
                                    {$entity.additional_settings}
                                </div>
                            {/foreach}
                            
                            <div id="updater_entity_bb_link_sku" class="x-updater-entity" style="display:none">
                                <div class="form-group">
                                    <label class="control-label col-lg-3">Opcje</label>
                                    <div class="col-lg-9">
                                        <label class="checkbox-inline">
                                            <input type="checkbox" id="bb-verify-ean" checked> Weryfikuj EAN
                                        </label>
                                        <br>
                                        <label class="checkbox-inline">
                                            <input type="checkbox" id="bb-overwrite-links"> <strong>Nadpisz istniejące powiązania</strong>
                                        </label>
                                        <p class="help-block">Zaznacz, jeśli chcesz świadomie zaktualizować/poprawić istniejące już powiązania dla zaznaczonych ofert.</p>
                                    </div>
                                </div>
                            </div>
                            
                            <div id="updater_entity_bb_link_eanprefix" class="x-updater-entity" style="display:none">
                                <div class="form-group">
                                    <label class="control-label col-lg-3">Prefiksy SKU</label>
                                    <div class="col-lg-9">
                                        <input type="text" id="bb-prefixes" class="form-control"
                                               value="BP_,EKOWIT_,STEW_,NAT_" placeholder="CSV: BP_,EKOWIT_,...">
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                {/if}
            </div>

            <div class="modal-footer x13allegro-modal-footer">
                
                {if $formAction == 'update'}
                <p class="x13allegro-modal-footer-left text-muted">
                    {l s='Jak korzystać z aktualizacji ofert?' mod='x13allegro'}
                    <a href="https://x13.pl/doc/dokumentacja-integracja-allegro-z-prestashop#aktualizacja-aukcji" target="_blank">{l s='Zobacz tutaj.' mod='x13allegro'}</a><br/>
                    {l s='Pierwszą aktualizację prosimy przeprowadzić na mniejszej ilości ofert.' mod='x13allegro'}
                </p>
                {/if}
                <button type="button" class="btn btn-primary x-auction-form-submit">
                    {if $formAction == 'finish'}
                        {l s='Zakończ wybrane oferty' mod='x13allegro'}
                    {elseif $formAction == 'redo'}
                        {l s='Wznów wybrane oferty' mod='x13allegro'}
                    {elseif $formAction == 'auto_renew'}
                        {l s='Ustaw opcje auto wznawiania' mod='x13allegro'}
                    {elseif $formAction == 'update'}
                        {l s='Aktualizuj oferty' mod='x13allegro'}
                    {/if}
                </button>
                <button class="btn btn-default x-updater-action-close-popup" type="button">{l s='Zamknij' mod='x13allegro'}</button>
            </div>
        </div>
    </form>
</div>

<script>
    (function ($) {
        // === TRYB DEBUGOWANIA ===
        var DEBUG_MODE = true;
        function debugLog(message, data) {
            if (DEBUG_MODE) {
                console.log('[DEBUG BB Linker] ' + message, data || '');
            }
        }
        // ========================

        debugLog('Skrypt BB Linker załadowany.');
        var $modal = $('.x13allegro-modal').last();

        if (!$modal.length) {
            debugLog('Error: Modal container .x13allegro-modal not found.');
            return;
        }

        var controllerUrl = 'index.php?controller=AdminXAllegroAuctionsList&ajax=1&token={$token}';
        
        /************************************************************************************************
        * ZMIANA: Cały poniższy blok został zastąpiony nową logiką.
        * Stara logika wysyłała wszystkie oferty w jednym żądaniu.
        * Nowa logika implementuje sekwencyjne przetwarzanie z paskiem postępu dla funkcji "Powiąż wg SKU".
        ************************************************************************************************/
        $('.x-auction-form-submit').on('click.bb_linker', function (e) {
            var sel = $modal.find('[x-name="update-auction-entity"]').val();
            debugLog('Wybrana akcja: ' + sel);

            // Handler dla akcji pobierania EAN - pozostaje bez zmian
            if (sel === 'bbGetEanAndSaveToFile') {
                e.preventDefault();
                e.stopImmediatePropagation();

                var $btn = $(this);
                var selected = [];
                $modal.find('input[data-name="xAllegroAuctionId"]:enabled').each(function () {
                    selected.push(String($(this).closest('tr').data('id')));
                });

                if (!selected.length) {
                    alert('Nie zaznaczono żadnych ofert.');
                    return;
                }

                $btn.prop('disabled', true).prepend('<i class="icon-spinner icon-spin"></i> ');

                $.ajax({
                    url: controllerUrl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'bbGetEanAndSaveToFile',
                        auctions: selected,
                        token: '{$token}'
                    }
                }).done(function (resp) {
                    if (resp && resp.map) {
                        $.each(resp.map, function (id, ean) {
                            $('#allegro-ean-' + id).text(ean || '—');
                        });
                        alert('Pobieranie EAN zakończone. Zaktualizowano ' + Object.keys(resp.map).length + ' ofert.');
                    }
                    if (resp && resp.errors && resp.errors.length) {
                        console.warn('Błędy podczas pobierania EAN:', resp.errors);
                        alert('Wystąpiły błędy:\n' + resp.errors.join('\n'));
                    }
                }).fail(function (xhr) {
                    console.error('bbGetEanAndSaveToFile fail', xhr && xhr.responseText);
                    alert('Błąd podczas pobierania EAN.');
                }).always(function () {
                    $btn.prop('disabled', false).find('i.icon-spinner').remove();
                });
                return false;
            }

            // Handler dla akcji pobierania VAT i zapisu do DB (z debugiem w konsoli)
            if (sel === 'bbFetchVatAndSaveToDb') {
                e.preventDefault();
                e.stopImmediatePropagation();

                var $btn = $(this);
                var selected = [];
                $modal.find('input[data-name="xAllegroAuctionId"]:enabled').each(function () {
                    selected.push(String($(this).closest('tr').data('id')));
                });

                if (!selected.length) {
                    alert('Nie zaznaczono żadnych ofert.');
                    return;
                }

                $btn.prop('disabled', true).prepend('<i class="icon-spinner icon-spin"></i> ');

                $.ajax({
                    url: controllerUrl,
                    method: 'POST',
                    dataType: 'json',
                    data: {
                        action: 'bbFetchVatAndSaveToDb',
                        auctions: selected,
                        debug: 1,
                        token: '{$token}'
                    }
                }).done(function (resp) {
                    console.log('[BB VAT] response', resp);
                    var html = '';
                    if (resp && resp.details && resp.details.length) {
                        html += '<div class="alert alert-info"><b>Wynik:</b> zaktualizowano ' + (resp.updated||0) + ' / ' + selected.length + ' ofert.</div>';
                        html += '<div style="max-height:240px; overflow:auto;"><table class="table">';
                        html += '<thead><tr><th>ID oferty</th><th>Status</th><th>Wybrana stawka</th><th>Kraj</th><th>Źródło</th><th>Stawki</th><th>Błąd</th></tr></thead><tbody>';
                        resp.details.forEach(function(r){
                            var rates = (r.rates||[]).map(function(x){ return (x.country||'?')+': '+x.rate; }).join(', ');
                            html += '<tr>'
                                  + '<td>'+r.id+'</td>'
                                  + '<td>'+r.status+'</td>'
                                  + '<td>'+ (r.chosen_rate || '') +'</td>'
                                  + '<td>'+ (r.chosen_country || '') +'</td>'
                                  + '<td>'+ (r.source || '') +'</td>'
                                  + '<td style="white-space:normal;">'+ rates +'</td>'
                                  + '<td style="white-space:normal;">'+ (r.error||'') +'</td>'
                                  + '</tr>';
                        });
                        html += '</tbody></table></div>';
                    } else {
                        html += '<div class="alert alert-warning">Brak szczegółów w odpowiedzi.</div>';
                    }
                    // pokaż w modalu (pod przyciskami)
                    var $footer = $modal.find('.modal-footer');
                    $('<div class="x-vat-debug mt-2" style="text-align:left"></div>').html(html).insertBefore($footer);
                    if (!(resp && resp.success)) {
                        alert('Wystąpił błąd podczas pobierania VAT.');
                    }
                }).fail(function (xhr) {
                    console.error('bbFetchVatAndSaveToDb fail', xhr && xhr.responseText);
                    alert('Błąd podczas pobierania VAT.');
                }).always(function () {
                    $btn.prop('disabled', false).find('i.icon-spinner').remove();
                });
                return false;
            }


            // Sprawdzenie, czy akcja dotyczy naszego wiązania
            if (sel !== 'bb_link_sku' && sel !== 'bb_link_eanprefix') {
                debugLog('Akcja nie jest nasza, puszczam dalej.');
                return; // Pozwól na działanie domyślnych skryptów modułu
            }
            
            // Jeśli to nasza akcja, przejmujemy kontrolę
            debugLog('Przechwycono akcję BB Linker. Zatrzymuję domyślne zdarzenia.');
            e.preventDefault();
            e.stopImmediatePropagation();

            var $btn = $(this);
            var auctions = [];
            $modal.find('input[data-name="xAllegroAuctionId"]:enabled').each(function() {
                auctions.push(String($(this).closest('tr').data('id')));
            });
            debugLog('Zebrane ID ofert:', auctions);

            if (!auctions.length) {
                alert('Brak ofert do przetworzenia.');
                return;
            }

            // --- NOWA LOGIKA Z PASKIEM POSTĘPU ---

            var totalAuctions = auctions.length;
            var processedCount = 0;
            var results = {
                ok: 0,
                skip: 0,
                fail: 0,
                fail_details: []
            };

            // Przygotowanie interfejsu do aktualizacji
            $modal.find('.x-updater-methods').hide();
            $modal.find('.x-auction-form-list').hide();
            $modal.find('.x-auction-form-submit').prop('disabled', true);
            $modal.find('.x-updater-progress').show();
            $modal.find('.x-updater-progress-to').text(totalAuctions);
            $modal.find('.x-updater-progress-from').text('0');
            $modal.find('.x-updater-progress-bar-fill').css('width', '0%');

            // Funkcja do przetwarzania pojedynczej oferty
            function processNextAuction() {
                if (auctions.length === 0) {
                    // Zakończono przetwarzanie wszystkich ofert
                    showFinalSummary();
                    return;
                }

                var auctionId = auctions.shift(); // Weź pierwszą ofertę z listy i usuń ją

                var payload = {
                    action: 'bbLinkSingle', // NOWA AKCJA do przetwarzania pojedynczej oferty
                    ajax: 1,
                    auctionId: auctionId,
                    token: '{$token}',
                    mode: (sel === 'bb_link_sku' ? 'sku' : 'eanprefix'),
                    verify_ean: $modal.find('#bb-verify-ean').is(':checked') ? 1 : 0,
                    overwrite_links: $modal.find('#bb-overwrite-links').is(':checked') ? 1 : 0,
                    prefixes: (sel === 'bb_link_eanprefix') ? ($modal.find('#bb-prefixes').val() || '') : ''
                };

                $.ajax({
                    url: controllerUrl,
                    method: 'POST',
                    data: payload,
                    dataType: 'json',
                    success: function(resp) {
                        if (resp && resp.success) {
                            // Zaktualizuj statystyki na podstawie odpowiedzi
                            if (resp.status === 'ok') results.ok++;
                            else if (resp.status === 'skip') results.skip++;
                            else {
                                results.fail++;
                                results.fail_details.push(resp.details);
                            }
                        } else {
                            // Błąd krytyczny dla pojedynczej oferty
                            results.fail++;
                            results.fail_details.push({
                                id: auctionId,
                                sku: 'N/A',
                                reason: (resp && resp.message) ? resp.message : 'Błąd serwera (brak odpowiedzi).'
                            });
                        }
                    },
                    error: function() {
                        // Błąd połączenia AJAX
                        results.fail++;
                        results.fail_details.push({
                            id: auctionId,
                            sku: 'N/A',
                            reason: 'Błąd połączenia z serwerem (AJAX error).'
                        });
                    },
                    complete: function() {
                        processedCount++;
                        var progressPercent = (processedCount / totalAuctions) * 100;
                        $modal.find('.x-updater-progress-from').text(processedCount);
                        $modal.find('.x-updater-progress-bar-fill').css('width', progressPercent + '%');
                        
                        // Przetwórz następną ofertę
                        processNextAuction();
                    }
                });
            }

            // Funkcja do wyświetlania końcowego podsumowania
            function showFinalSummary() {
                $modal.find('.x-updater-start-title').hide();
                $modal.find('.x-updater-end-title').show();
                $btn.hide();
                $modal.find('.x-updater-action-close-popup').show().on('click', function() {
                     window.location.reload(); // Przeładuj stronę po zamknięciu
                });

                var resultsHtml = '<div class="alert alert-info" style="margin-top:15px">';
                resultsHtml += '<h4>Podsumowanie operacji</h4>';
                resultsHtml += '<p><strong>Pomyślnie powiązano:</strong> ' + results.ok + '</p>';
                resultsHtml += '<p><strong>Pominięto (już powiązane):</strong> ' + results.skip + '</p>';
                resultsHtml += '<p><strong>Wystąpiło błędów:</strong> ' + results.fail + '</p>';
                resultsHtml += '</div>';

                if (results.fail > 0) {
                    resultsHtml += '<div class="alert alert-danger">';
                    resultsHtml += '<h4>Szczegóły błędów:</h4>';
                    resultsHtml += '<ul style="max-height: 150px; overflow-y: auto;">';
                    
                    var offerUrlBase = 'https://allegro.pl/oferta/';
                    
                    $.each(results.fail_details, function(index, item) {
                        resultsHtml += '<li>';
                        resultsHtml += '<strong>Oferta <a href="' + offerUrlBase + item.id + '" target="_blank">' + item.id + '</a></strong>';
                        if (item.sku) {
                            resultsHtml += ' (SKU: ' + item.sku + ')';
                        }
                        resultsHtml += ': ' + item.reason;
                        resultsHtml += '</li>';
                    });

                    resultsHtml += '</ul>';
                    resultsHtml += '</div>';
                }
                
                var $finishMessageContainer = $modal.find('.x-updater-finish-message');
                $finishMessageContainer.html(resultsHtml).show();
            }

            // Rozpocznij przetwarzanie
            processNextAuction();

            return false;
        });
    })(jQuery);
</script>

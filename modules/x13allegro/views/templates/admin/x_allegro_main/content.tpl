{$isModernLayout = version_compare($smarty.const._PS_VERSION_, '1.7.7.0', '>=')}

{if isset($toolbar_title) && isset($toolbar_btn)}
    {include file='./content-header-toolbar.tpl' page_header_toolbar_btn=$toolbar_btn title=$toolbar_title}
{/if}

{* account switch *}
{if isset($content)}
    {$content}
{/if}

{if isset($products) && !empty($products)}
    <div class="leadin">{block name="leadin"}{/block}</div>

    <div id="allegro-progress" class="bootstrap" style="display:none;">
        <div class="row">
            <div class="panel col-lg-12">
                <p><i class="icon-refresh icon-spin"></i> Trwa pobieranie informacji o produktach z katalogu Allegro dla <span id="allegro-valuenow">0</span> z <span id="allegro-valuemax">0</span> produktów</p>
                <div class="progress">
                    <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
            </div>
        </div>
    </div>

    {if $fees_enabled}
        <div class="alert alert-info">
            <p>
                Zgodnie z ustawieniami tego konta, do kwoty oferty zostanie dodana wyliczona prowizja zgodnie z <a href="https://allegro.pl/pomoc/dla-sprzedajacych/oplaty-i-cenniki/jak-jest-obliczana-prowizja-od-sprzedazy-dykqgOq94iE" target="_blank" rel="noopener">cennikiem Allegro</a>.
                Kwota prowizji doda się po wystawieniu oferty. <a data-toggle="modal" data-target="#fees_modal" href="#">Zobacz jak wyliczamy prowizje.</a>
            </p>
        </div>
        {include file='./fees-modal.tpl'}
    {/if}

    <form id="allegro_main_form" class="bootstrap" action="" method="post" autocomplete="off">
        <input type="hidden" value="x13allegro" name="x13allegro">
        <input type="hidden" value="{$account->id}" name="id_xallegro_account">

        <div class="row">
            <div id="allegro_products" class="panel col-lg-12" x-name="allegro_products">
                <fieldset>
                    <div class="panel-heading"><i class="icon-book"></i> Produkty <span class="badge">{count($products)}</span></div>

                    <table class="table">
                        <colgroup>
                            <col width="30px">
                            <col width="350px">
                            <col width="160px">
                            <col width="150px">
                            <col>
                            <col width="315px">
                        </colgroup>
                        <thead>
                        <tr class="nodrag nodrop" style="height: 30px">
                            <th class="center"></th>
                            <th><span class="title_box">Tytuł oferty</span></th>
                            <th><span class="title_box">Ilość</span></th>
                            <th><span class="title_box">Cena</span></th>
                            <th><span class="title_box">Zdjęcia</span></th>
                            <th><span class="title_box">Szablon / Dodatkowe opcje</span></th>
                        </tr>
                        </thead>

                        <tbody>
                        {foreach $products AS $index => $product}
                            <tr
                                data-productization-status="0"
                                data-index="{$index}"
                                class="{if $product.disabled}product-disabled{/if} {if $product.allegro_status && $product.allegro_status.count > 0}exposed{/if} {if $index is odd}alt_row odd{/if} tr_auction_product"
                            >
                                <td class="center" style="border-bottom: 1px dashed #DDD;vertical-align:middle;padding-top:8px;padding-bottom:8px;">
                                    <input type="checkbox" id="item_{$index}_enabled" name="item[{$index}][enabled]" value="1" x-name="product_switch" data-status="{$product.allegro_status && $product.allegro_status.count > 0}" data-disabled="{$product.disabled|intval}" class="noborder" disabled="disabled" />
                                </td>

                                <td colspan="{if !$product.disabled}5{else}7{/if}" style="border-bottom: 1px dashed #DDD;vertical-align:middle;">
                                    <label for="item_{$index}_enabled" style="font-weight:normal; float:left; margin:0;">
                                        {$product.name}{if $product.name_attribute} - {$product.name_attribute}{/if}
                                        - <strong>{$product.price_buy_now_default} {$marketplaces[$account->base_marketplace].currencySign}</strong>

                                        <span class="xproductization-product-label">
                                            {if $productization_show_reference && !empty($product.reference)}<strong>Ref:</strong> {$product.reference}{/if}
                                            {if $productization_show_gtin}
                                                {if !empty({$product.ean13})}<strong>EAN13:</strong> {$product.ean13}{/if}
                                                {if !empty({$product.isbn})}<strong>ISBN:</strong> {$product.isbn}{/if}
                                                {if !empty({$product.upc})}<strong>UPC:</strong> {$product.upc}{/if}
                                            {/if}
                                            {if $productization_show_mpn && !empty($product.mpn)}<strong>MPN:</strong> {$product.mpn}{/if}
                                        </span>

                                        {if !$product.disabled}
                                            <div class="xproductization-all-info">
                                                <div class="xproductization-status" style="font-size: 11px; color: #999;">{l s='Trwa pobieranie informacji o produkcie' mod='x13allegro'}</div>
                                                <div class="xproductization-category" style="font-size: 11px; color: #999; display: none;"></div>
                                                <div class="xproductization-product-name" style="font-size: 11px; color: #999; display: none;">
                                                    {l s='Produkt' mod='x13allegro'}:&nbsp;<a href="" target="_blank" rel="nofollow"></a>
                                                </div>
                                            </div>
                                        {/if}
                                    </label>

                                    <div class="xproductization-indicator-wrapper">
                                        {if !$product.disabled}
                                            <div class="text-right xproductization-indicator-wrapper-all">
                                                <span class="xproductization-indicator">
                                                    <button type="button" class="xproductization-modal-btn label label-white" data-toggle="" data-target="#selection_modal_{$index}">
                                                        <i class="icon-refresh icon-spin"></i> Pobieranie informacji o produkcie
                                                    </button>
                                                </span>
                                                <span class="xproductization-mode-selector">
                                                    <select name="item[{$index}][productization_mode]" x-name="productization_mode" class="fixed-width-xl" data-current="ASSIGN" disabled="disabled">
                                                        <option value="ASSIGN" {if $productization_mode == 'ASSIGN'}selected="selected"{/if}>{l s='wystaw wg katalogu' mod='x13allegro'}</option>
                                                        <option value="NEW" {if $productization_mode == 'NEW'}selected="selected"{/if}>{l s='wystaw jako nowy produkt' mod='x13allegro'}</option>
                                                    </select>
                                                </span>
                                            </div>
                                            {include file='./product-selection-modal.tpl'}
                                        {/if}
                                        <p class="x13allegro-product-auctio-count">{if $product.allegro_status && $product.allegro_status.count > 0}Wystawiono:<span class="x13allegro-product-auctio-count-small"> {$product.allegro_status.quantity} szt. na {$product.allegro_status.count} {if $product.allegro_status.count == 1}ofercie{else}ofertach{/if}</span>{/if}</p>
                                    </div>
                                </td>
                            </tr>

                            <tr class="{if $product.allegro_status && $product.allegro_status.count > 0}exposed{/if} {if $index is odd}alt_row odd{/if}" style="display: none;" x-name="product" x-id="{$index}">
                                <td colspan="2" style="vertical-align: top; padding-top:10px;">
                                    <input type="hidden" name="item[{$index}][id_auction]" value="" x-name="id_auction" />

                                    <input type="hidden" name="item[{$index}][category_id]" value="0" x-name="category_id" />
                                    <input type="hidden" name="item[{$index}][category_is_leaf]" value="0" x-name="category_is_leaf" />
                                    <input type="hidden" name="item[{$index}][category_gpsr]" value="0" x-name="category_gpsr" />
                                    <input type="hidden" name="item[{$index}][allegro_product_id]" value="" x-name="allegro_product_id" />
                                    <input type="hidden" name="item[{$index}][allegro_product_name]" value="" x-name="allegro_product_name" />
                                    <input type="hidden" name="item[{$index}][allegro_product_images]" value="" x-name="allegro_product_images" />
                                    <input type="hidden" name="item[{$index}][allegro_product_description]" value="" x-name="allegro_product_description" />
                                    <input type="hidden" name="item[{$index}][allegro_product_category_default]" value="" x-name="allegro_product_category_default" />
                                    <input type="hidden" name="item[{$index}][allegro_product_category_similar]" value="" x-name="allegro_product_category_similar" />

                                    <input type="hidden" name="item[{$index}][id_product]" value="{$product.id}" x-name="id_product" />
                                    <input type="hidden" name="item[{$index}][id_product_attribute]" value="{$product.id_attribute}" x-name="id_product_attribute" />
                                    <input type="hidden" name="item[{$index}][id_category_default]" value="{$product.id_category_default}" x-name="id_category_default" />
                                    <input type="hidden" name="item[{$index}][assoc_category_id]" value="{$product.assoc_category_id}" x-name="assoc_category_id" />
                                    <input type="hidden" name="item[{$index}][reference]" value="{$product.reference|escape}" x-name="reference" />
                                    <input type="hidden" name="item[{$index}][ean13]" value="{$product.ean13}" x-name="ean13" />
                                    <input type="hidden" name="item[{$index}][isbn]" value="{$product.isbn}" x-name="isbn" />
                                    <input type="hidden" name="item[{$index}][upc]" value="{$product.upc}" x-name="upc" />
                                    <input type="hidden" name="item[{$index}][mpn]" value="{$product.mpn}" x-name="mpn" />
                                    <input type="hidden" name="item[{$index}][tax_rate]" value="{$product.tax_rate}" x-name="tax_rate">
                                    <input type="hidden" name="item[{$index}][weight]" value="{$product.weight}" x-name="weight" data-cast="float">
                                    <input type="hidden" name="item[{$index}][price_calculate_fees]" value="{$product.price_calculate_fees}">

                                    <input type="hidden" name="item[{$index}][auction_title]" value="{$product.title|escape}" x-name="auction_title" />
                                    <input type="hidden" name="item[{$index}][product_name]" value="{$product.name|escape}" x-name="product_name" />
                                    <input type="hidden" name="item[{$index}][attribute_name]" value="{if isset($product.name_attribute)}{$product.name_attribute|escape}{/if}" x-name="attribute_name" />
                                    <input type="hidden" name="item[{$index}][manufacturer]" value="{$product.manufacturer_name|escape}" x-name="manufacturer" />

                                    <div class="title-box">
                                        {if $productization_name == 'prestashop_copy' ||
                                        $productization_name == 'allegro'}
                                            <a class="btn btn-default button bt-icon label-tooltip allegro-productization-name-copy" x-name="productization_name_copy" data-toggle="tooltip" data-original-title="Kopiuj nazwę produktu Allegro do tytułu oferty">
                                                <i class="icon-copy"></i>
                                            </a>
                                        {/if}

                                        <input type="text" name="item[{$index}][title]" value="{$product.title|escape}" size="75" x-name="title" data-product-name="{$product.title|escape}" />
                                        <p>Ilość znaków: <strong><span x-name="counter">{$product.title_size}</span>/{$titleMaxSize}</strong></p>
                                    </div>

                                    <p>
                                        <a class="btn btn-default button bt-icon" data-toggle="modal" data-target="#description_edit_modal_{$index}" x-name="description_edit">
                                            <i class="icon-edit"></i> <span>Edytuj opis przedmiotu</span>
                                        </a>
                                    </p>

                                    {*<p>
                                        <input type="checkbox" name="item[{$index}][product_tags]" value="1" x-name="product_tags" x-index="{$index}">
                                        <a class="btn btn-default button bt-icon" href="#product_tags_{$index}" x-name="product_tags" x-index="{$index}" disabled="disabled">
                                            <i class="icon-tag"></i> <span>Indywidualne tagi</span>
                                        </a>
                                    </p>*}

                                    <p style="display: none;">
                                        <a class="btn btn-default button bt-icon xproductization-category" data-toggle="modal" data-target="#category_modal_{$index}" x-name="product_category" disabled="disabled">
                                            <i class="icon-folder-open"></i> <span>Wybór kategorii</span>
                                        </a>
                                        <span class="icon-warning label-tooltip xproductization-category-last-node" data-toggle="tooltip" data-original-title="Wybierz kategorię najniższego rzędu" style="display:none;"></span>
                                        <span class="icon-info label-tooltip xproductization-category-assoc" data-toggle="tooltip" data-original-title="Wybrano kategorię na podstawie powiązania" style="display:none;"></span>
                                        <span class="xproductization-category-loading" style="display:none;"><i class="icon-refresh icon-spin"></i></span>
                                    </p>

                                    <p style="display: none;">
                                        <a class="btn btn-default button bt-icon xproductization-category-similar" x-name="product_category_similar" disabled="disabled">
                                            <i class="icon-folder-open"></i> <span>Zmień kategorię</span>
                                        </a>
                                        <span class="label-tooltip xproductization-category-similar-count" data-toggle="tooltip" data-original-title="Lista podobnych kategorii, w których możesz sprzedawać ten produkt" style="display:none;"></span>
                                        <span class="xproductization-category-similar-loading" style="display:none;"><i class="icon-refresh icon-spin"></i></span>
                                    </p>

                                    <p>
                                        <a class="btn btn-default button bt-icon xproductization-parameters" data-toggle="modal" data-target="#category_fields_modal_{$index}" x-name="product_category_fields" disabled="disabled">
                                            <i class="icon-asterisk"></i> <span>Parametry kategorii</span>
                                        </a>
                                        <span class="icon-warning label-tooltip xproductization-parameters-empty-required" data-toggle="tooltip" data-original-title="Uzupełnij wymagane parametry" style="display:none;"></span>
                                        <span class="xproductization-parameters-loading" style="display:none;"><i class="icon-refresh icon-spin"></i></span>
                                    </p>

                                    <p>
                                        <a class="btn btn-default button bt-icon xproductization-gpsr" data-toggle="modal" data-target="#product_gpsr_modal_{$index}" x-name="product_gpsr" disabled="disabled">
                                            <i class="icon-file"></i> <span>Zgodność z GPSR</span>
                                        </a>
                                        <span class="icon-info label-tooltip xproductization-gpsr-excluded" data-toggle="tooltip" data-original-title="Kategoria jest wykluczona z GPSR" style="display:none;"></span>
                                        <span class="icon-warning label-tooltip xproductization-gpsr-empty-required" data-toggle="tooltip" data-original-title="Uzupełnij informacje GPSR" style="display:none;"></span>
                                        <span class="xproductization-gpsr-loading" style="display:none;"><i class="icon-refresh icon-spin"></i></span>
                                    </p>

                                    {*<div id="product_tags_{$index}" class="product-tags bootstrap" x-name="product_tags" x-index="{$index}" style="display: none;"></div>*}

                                    {include file='./product-description-modal.tpl'}
                                    {include file='./product-category-modal.tpl'}
                                    {include file='./product-category-similar-modal.tpl'}
                                    {include file='./product-category-parameters-modal.tpl'}
                                    {include file='./product-gpsr-modal.tpl'}
                                </td>
                                <td style="vertical-align: top;padding-top:10px;">
                                    <span class="clearfix">
                                        <input type="text" name="item[{$index}][quantity]" value="{$product.quantity}" size="3" data-cast="integer" x-start="{$product.quantity}" x-name="quantity" x-oos="{$product.quantity_oos}" {if $product.quantity_check}x-max="{$product.quantity_max}"{/if} style="width: 73px; float: left;" />
                                        <select name="item[{$index}][quantity_type]" x-name="quantity_type" style="width: 71px; float: left; margin-left: 2px; {if $isModernLayout}padding-left: 8px;{/if}">
                                            <option value="UNIT">szt</option>
                                            <option value="SET">kpl</option>
                                            <option value="PAIR">par</option>
                                        </select>
                                    </span>

                                    <p class="clearfix">Na stanie: <strong>{$product.quantity_stock}</strong></p>

                                    <label for="item_selling_mode_{$index}" class="t">Formaty sprzedaży</label>
                                    <select id="item_selling_mode_{$index}" name="item[{$index}][selling_mode]" x-name="selling_mode">
                                        <option value="BUY_NOW">Kup teraz</option>
                                        <option value="AUCTION">Licytacja</option>
                                    </select>

                                    <label for="item_duration_{$index}" class="t">Czas trwania</label>
                                    <select id="item_duration_{$index}" name="item[{$index}][duration]" x-name="duration">
                                        {foreach $durations AS $duration}
                                            <option value="{$duration.id}" data-type="{if isset($duration.type)}{$duration.type}{else}0{/if}" {if $product.duration == $duration.id}data-default="1" selected="selected"{else}data-default="0"{/if}>{$duration.name}</option>
                                        {/foreach}
                                    </select>

                                    <label for="item_auto_renew_{$index}" class="t">Wznawianie</label>
                                    <select id="item_auto_renew_{$index}" name="item[{$index}][auto_renew]" x-name="auto_renew">
                                        <option value="" selected="selected">Domyślnie</option>
                                        <option value="1">Tak</option>
                                        <option value="0">Nie</option>
                                    </select>
                                </td>
                                <td style="vertical-align: top;padding-top:10px;">
                                    <div class="price-buy-now">
                                        <label for="item_price_buy_now_{$index}" class="t">Cena <b>Kup teraz</b></label>
                                        <div class="input-group">
                                            <input type="text" id="item_price_buy_now_{$index}" name="item[{$index}][price_buy_now]" value="{$product.price_buy_now}" data-iso-code="{$marketplaces[$account->base_marketplace].currencyIsoCode}" data-rate="{$marketplaces[$account->base_marketplace].currencyConversionRate}" data-cast="float" x-name="price_buy_now" />
                                            <span class="input-group-addon">{$marketplaces[$account->base_marketplace].currencySign}</span>
                                        </div>

                                        <div class="marketplaces-prices">
                                            {foreach $product.marketplaces as $marketplaceId => $marketplace}
                                                <div class="input-group" data-marketplace-id="{$marketplaceId}" {if !in_array($marketplaceId, $shippingRateMarketplaces[$shippingRateSelectedId])}style="display: none;"{/if}>
                                                    <input type="text" name="item[{$index}][marketplace][{$marketplaceId}][price_buy_now]" value="{$marketplace.price_buy_now}" data-iso-code="{$marketplaces[$marketplaceId].currencyIsoCode}" data-rate="{$marketplaces[$marketplaceId].currencyConversionRate}" data-cast="float" data-cast-precision="{$marketplaces[$marketplaceId].currencyPrecision}" readonly="readonly" />
                                                    <span class="input-group-addon">{$marketplaces[$marketplaceId].currencySign}</span>
                                                </div>
                                            {/foreach}
                                        </div>

                                        <label for="send_tax_{$index}" class="t">
                                            <input type="checkbox" id="send_tax_{$index}" name="item[{$index}][send_tax]" value="1" x-name="send_tax" style="margin-top: 5px;" {if $product.send_tax}checked="checked"{/if}>
                                            Wyślij wartość VAT
                                        </label>
                                        <div class="no-category-tax">Wybierz kategorie aby wyświetlić stawki VAT</div>

                                        <div class="marketplaces-taxes">
                                            {foreach $marketplaces as $marketplaceId => $marketplace}
                                                <div data-marketplace-id="{$marketplaceId}" data-marketplace-country-code="{$marketplace.countryCode}" data-tax-rate="{if $marketplaceId == $account->base_marketplace}{$product.tax_rate}{else}{$product.marketplaces[$marketplaceId].tax}{/if}" style="display: none;">
                                                    <label for="item_marketplace_tax_{$index}_{$marketplaceId}" class="t">Stawka VAT {$marketplace.countryName}</label>
                                                    <select id="item_marketplace_tax_{$index}_{$marketplaceId}" name="item[{$index}][marketplace][{$marketplaceId}][tax]">
                                                        <option value="" selected="selected">nie wysyłaj</option>
                                                    </select>
                                                </div>
                                            {/foreach}
                                        </div>
                                    </div>

                                    <div class="price-asking" style="margin-top: 6px; display: none;">
                                        <label for="item_price_asking_{$index}" class="t"><b>Cena wywoławcza</b></label>
                                        <div class="input-group">
                                            <input type="text" id="item_price_asking_{$index}" name="item[{$index}][price_asking]" value="{$product.price_asking}" size="11" data-cast="float" x-name="price_asking" disabled="disabled" />
                                            <span class="input-group-addon">{$marketplaces[$account->base_marketplace].currencySign}</span>
                                        </div>
                                    </div>

                                    <div class="price-minimal" style="margin-top: 6px; display: none;">
                                        <label for="item_price_minimal_{$index}" class="t">Cena minimalna</label>
                                        <div class="input-group">
                                            <input type="text" id="item_price_minimal_{$index}" name="item[{$index}][price_minimal]" value="{$product.price_minimal}" size="11" data-cast="float" x-name="price_minimal" disabled="disabled" />
                                            <span class="input-group-addon">{$marketplaces[$account->base_marketplace].currencySign}</span>
                                        </div>
                                    </div>
                                </td>
                                <td style="vertical-align: top;padding-top:10px;">
                                    <ul id="images_sortable_{$index}" class="images-sortable" x-max="{$select_images_max}">
                                        {foreach $product.images as $key => $item}
                                            {capture name="image_var"}{if $image_legacy}{$product.id}-{$item.id_image}{else}{$item.id_image}{/if}{/capture}
                                            {capture name="image_url"}{if $item.url}{$item.url}{else}{$link->getImageLink($product.link_rewrite, $smarty.capture.image_var, $images_preview_type.name)}{/if}{/capture}
                                            {if $select_images == 'all'}
                                                <li class="item_form image {if $key==0}main_image{/if}" x-name="images">
                                                    <input type="checkbox" name="item[{$index}][images][]" value="{$item.id_image}" {if $key < $select_images_max}checked="checked"{/if} style="position: absolute; margin: 5px 0px 0px 5px;" x-name="images" />
                                                    <span class="center-helper"></span><img src="{$smarty.capture.image_url|escape:'html':'UTF-8'}" class="" x-value="{$item.id_image}" />
                                                </li>
                                            {elseif $select_images == 'first'}
                                                <li class="item_form image {if $key==0}main_image{/if}" x-name="images">
                                                    <input type="checkbox" name="item[{$index}][images][]" value="{$item.id_image}" {if $key==0}checked="checked"{/if} style="position: absolute; margin: 5px 0px 0px 5px;" x-name="images" />
                                                    <span class="center-helper"></span><img src="{$smarty.capture.image_url|escape:'html':'UTF-8'}" class="" x-value="{$item.id_image}" />
                                                </li>
                                            {else}
                                                <li class="item_form image {if $item.id_image == $product.image_main}main_image{/if}" x-name="images">
                                                    <input type="checkbox" name="item[{$index}][images][]" value="{$item.id_image}" {if in_array($item.id_image, $product.images)}checked="checked"{/if} style="position: absolute; margin: 5px 0px 0px 5px;" x-name="images" />
                                                    <span class="center-helper"></span><img src="{$smarty.capture.image_url|escape:'html':'UTF-8'}" class="" x-value="{$item.id_image}" />
                                                </li>
                                            {/if}
                                        {/foreach}
                                    </ul>
                                    {if isset($product.images[0]['id_image']) && ($select_images == 'all' || $select_images == 'first')}
                                        <input type="hidden" name="item[{$index}][image_main]" value="{$product.images[0]['id_image']}" x-name="image_main" />
                                    {else}
                                        <input type="hidden" name="item[{$index}][image_main]" value="{$product.image_main}" x-name="image_main" />
                                    {/if}
                                </td>
                                <td style="vertical-align: top;padding-top:10px;">
                                    <p class="template-auction clearfix">
                                        <select id="item_template_{$index}" name="item[{$index}][template]" x-name="template" style="display: inline-block; float: left; width: 245px;">
                                            {foreach $templates as $template}
                                                <option value="{$template.id}" {if $template.default}selected="selected"{/if}>{$template.name}</option>
                                            {/foreach}
                                        </select>
                                        <a class="btn btn-default button bt-icon" style="display: inline-block; float: right;" href="#" x-name="preview" x-id="{$index}" title="Podgląd oferty">
                                            <i class="icon-search"></i>
                                        </a>
                                    </p>

                                    <ul class="nav nav-tabs" id="itemTab_{$index}">
                                        <li class="active label-tooltip" data-toggle="tooltip" data-original-title="Opcje promowania">
                                            <a href="#itemTab_{$index}_promotion" aria-controls="itemTab_{$index}_promotion" role="tab" data-toggle="tab">
                                                <i class="allegro-icon allegro-icon-promotion"></i>
                                            </a>
                                        </li>
                                        <li class="label-tooltip" data-toggle="tooltip" data-original-title="Cennik hurtowy">
                                            <a href="#itemTab_{$index}_wholesalePriceList" aria-controls="itemTab_{$index}_wholesalePriceList" role="tab" data-toggle="tab">
                                                <i class="allegro-icon allegro-icon-wholesale-price-list"></i>
                                            </a>
                                        </li>
                                        <li class="label-tooltip" data-toggle="tooltip" data-original-title="Tabela rozmiarów">
                                            <a href="#itemTab_{$index}_sizeTable" aria-controls="itemTab_{$index}_sizeTable" role="tab" data-toggle="tab">
                                                <i class="allegro-icon allegro-icon-size-table"></i>
                                            </a>
                                        </li>
                                        <li class="label-tooltip" data-toggle="tooltip" data-original-title="Przedsprzedaż">
                                            <a href="#itemTab_{$index}_preorder" aria-controls="itemTab_{$index}_preorder" role="tab" data-toggle="tab">
                                                <i class="allegro-icon allegro-icon-preorder"></i>
                                            </a>
                                        </li>
                                    </ul>

                                    <div class="tab-content panel clearfix" id="itemTab_{$index}_Content" role="tabpanel">
                                        <div class="tab-pane active" id="itemTab_{$index}_promotion">
                                            <div class="promo-auction">
                                                <label>
                                                    <input type="radio" name="item[{$index}][basePackages]" value="0" checked="checked" x-name="none">
                                                    Bez promowania
                                                </label>
                                                {foreach $promotionPackages->basePackages as $basePackage}
                                                    <label>
                                                        <input type="radio" name="item[{$index}][basePackages]" value="{$basePackage->id}" x-name="{$basePackage->id}">
                                                        {$basePackage->name}
                                                    </label>
                                                {/foreach}

                                                <p style="font-size: 85%; text-transform: uppercase; margin: 10px 0 5px;">Opcje dodatkowe</p>
                                                {foreach $promotionPackages->extraPackages as $extraPackage}
                                                    <label>
                                                        <input type="checkbox" name="item[{$index}][extraPackages][]" value="{$extraPackage->id}" x-name="{$extraPackage->id}">
                                                        {$extraPackage->name}
                                                    </label>
                                                {/foreach}
                                            </div>
                                        </div>

                                        <div class="tab-pane" id="itemTab_{$index}_wholesalePriceList">
                                            <label for="wholesale_price_{$index}" class="t">Cennik hurtowy</label>
                                            {if !empty($wholesalePriceList)}
                                                <select id="wholesale_price_{$index}" name="item[{$index}][wholesale_price]" x-name="wholesale_price">
                                                    <option value="0">-- Wybierz --</option>
                                                    {foreach $wholesalePriceList as $wholesalePrice}
                                                        <option value="{$wholesalePrice->id}">{$wholesalePrice->benefits[0]->specification->name}</option>
                                                    {/foreach}
                                                </select>
                                            {else}
                                                <p class="help-block">Brak skonfigurowanych cenników hurtowych</p>
                                            {/if}
                                        </div>

                                        <div class="tab-pane" id="itemTab_{$index}_sizeTable">
                                            <label for="size_table_{$index}" class="t">Tabela rozmiarów</label>
                                            {if !empty($sizeTables)}
                                                <select id="size_table_{$index}" name="item[{$index}][size_table]" x-name="size_table">
                                                    <option value="0">-- Wybierz --</option>
                                                    {foreach $sizeTables as $sizeTable}
                                                        <option value="{$sizeTable->id}">{$sizeTable->name}</option>
                                                    {/foreach}
                                                </select>
                                            {else}
                                                <p class="help-block">Brak skonfigurowanych tabel rozmiarów</p>
                                            {/if}
                                        </div>

                                        <div class="tab-pane" id="itemTab_{$index}_preorder">
                                            <div class="preorder-auction">
                                                <label for="item_preorder_{$index}" class="t">
                                                    <input type="checkbox" id="item_preorder_{$index}" name="item[{$index}][preorder]" value="1" x-name="preorder">
                                                    Przedsprzedaż
                                                </label>
                                                <input type="text" name="item[{$index}][preorder_date]" class="datepicker fixed-width-md" value="{$smarty.now|date_format:'%d.%m.%Y'}" x-name="preorder_date" disabled="disabled" style="display: none;">
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        {/foreach}
                        </tbody>
                    </table>
                </fieldset>
            </div>
        </div><div id="bulk_container" class="row is-hidden" style="visibility: hidden;">
            <div class="panel col-lg-12" x-name="allegro_bulk">
                <fieldset>
                    <div class="panel-heading">
                        <i class="icon-copy"></i> Masowe ustawienia dla wszystkich ofert
                        <span id="bulk_container_chevron">
                            <a href="#" id="bulk_container_hide">zwiń<i class="icon-angle-down"></i></a>
                            <a href="#" id="bulk_container_show">rozwiń<i class="icon-angle-up"></i></a>
                        </span>
                    </div>

                    <div style="border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 15px;">
                        <div style="display: inline-block;">
                            <div class="btn-group dropup">
                                <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                                    {l s='Działania masowe' mod='x13allegro'} <span class="caret"></span>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a href="#" x-name="bulk_select_all"><i class="icon-check-sign"></i> <span>{l s='Zaznacz wszystkie' mod='x13allegro'}</span></a></li>
                                    <li><a href="#" x-name="bulk_select_not_exposed"><i class="icon-check-square-o"></i> <span>{l s='Zaznacz tylko niewystawione' mod='x13allegro'}</span></a></li>
                                    <li><a href="#" x-name="bulk_select_none"><i class="icon-check-empty"></i> <span>{l s='Odznacz wszystkie' mod='x13allegro'}</span></a></li>
                                    <li class="divider"></li>
                                    <li><a href="#" x-name="bulk_change_category"><i class="icon-folder-open"></i> <span>{l s='Zmień kategorię' mod='x13allegro'}</span></a></li>
                                    <li><a href="#" x-name="bulk_category_parameters"><i class="icon-asterisk"></i> <span>{l s='Ustaw parametry kategorii' mod='x13allegro'}</span></a></li>
                                    <li><a href="#" x-name="bulk_product_gpsr"><i class="icon-file"></i> <span>{l s='Ustaw zgodność z GPSR' mod='x13allegro'}</span></a></li>
                                </ul>
                            </div>
                        </div>
		{* -- NOWY PRZYCISK - START -- *}
<div style="display: inline-block; margin-left: 10px;">
    <button type="button" id="bulk_fill_gpsr" class="btn btn-default">
        <i class="icon-cogs"></i> {l s='Uzupełnij dane GPSR dla zaznaczonych' mod='x13allegro'}
    </button>
</div>
{* -- NOWY PRZYCISK - KONIEC -- *}
                        <div class="bulk-mode-selector" style="display: inline-block; float: right; margin-right: 147px;">
                            <select x-name="bulk_productization_mode" class="fixed-width-lg">
                                <option value="0">{l s='-- wybierz sposób wystawiania --' mod='x13allegro'}</option>
                                <option value="ASSIGN">{l s='wystaw wg katalogu' mod='x13allegro'}</option>
                                <option value="NEW">{l s='wystaw jako nowy produkt' mod='x13allegro'}</option>
                            </select>
                        </div>
                    </div>

                    <table class="table">
                        <colgroup>
                            <col width="380px"/>
                            <col width="160px"/>
                            <col width="150px"/>
                            <col>
                            <col width="315px"/>
                        </colgroup>
                        <thead>
                        <tr class="nodrag nodrop" style="height: 30px">
                            <th><span class="title_box">Tytuł oferty</span></th>
                            <th><span class="title_box">Ilość</span></th>
                            <th><span class="title_box">Cena</span></th>
                            <th><span class="title_box">Zdjęcia</span></th>
                            <th><span class="title_box">Szablon / Dodatkowe opcje</span></th>
                        </tr>
                        </thead>

                        <tbody>
                        <tr style="background-color: #ececec;">
                            <td style="border-bottom: none; vertical-align: top; padding-top:10px;">
                                <p>
                                    <input type="text" name="bulk_title" value="" size="50" x-name="bulk_title">
                                </p>
                                <p>
                                    <a class="btn btn-default button bt-icon" href="#" x-name="bulk_title_before">
                                        <i class="icon-plus-sign"></i> <span>Dodaj przed</span>
                                    </a>
                                    <a class="btn btn-default button bt-icon" href="#" x-name="bulk_title_after">
                                        <i class="icon-plus-sign"></i> <span>Dodaj po</span>
                                    </a>
                                    <a class="btn btn-default button bt-icon" href="#" x-name="bulk_title_change">
                                        <i class="icon-retweet"></i> <span>Zamień tytuł</span>
                                    </a>
                                </p>
                                <div class="help-block">
                                    <a href="#" id="show_bulk_title_tags">Pokaż dostępne znaczniki</a>
                                    <div id="bulk_title_tags" style="display: none;">
                                    {literal}
                                        <u>Dostępne znaczniki:</u><br>
                                        <span class="x13allegro_black">{auction_title}</span> - Indywidualnie wygenerowany tytuł oferty<br>
                                        <span class="x13allegro_black">{product_id}</span> - ID produktu<br>
                                        <span class="x13allegro_black">{product_name}</span> - Nazwa produktu<br>
                                        <span class="x13allegro_black">{product_name_attribute}</span> - Nazwa atrybutu<br>
                                        <span class="x13allegro_black">{product_reference}</span> - Kod referencyjny (indeks) produktu<br>
                                        <span class="x13allegro_black">{product_ean13}</span> - Kod EAN13<br>
                                        <span class="x13allegro_black">{product_weight}</span> - Waga produktu<br>
                                        <span class="x13allegro_black">{manufacturer_name}</span> - Nazwa producenta
                                    {/literal}
                                    </div>
                                </div>
                                {if $productization_name == 'prestashop_copy' ||
                                $productization_name == 'allegro'}
                                    <p>
                                        <a class="btn btn-default button bt-icon" x-name="bulk_productization_name_copy">
                                            <i class="icon-copy"></i> <span>Kopiuj nazwę produktu Allegro do tytułu oferty</span>
                                        </a>
                                    </p>
                                {/if}
                            </td>
                            <td style="border-bottom: none; vertical-align: top; padding-top:10px;">
                                <input type="text" value="" size="3" id="bulk_quantity" x-name="bulk_quantity" data-cast="integer" style="width: 73px; float: left;" />

                                <select x-name="bulk_quantity_type" style="width: 71px; float: left; margin-left: 2px; {if $isModernLayout}padding-left: 8px;{/if}">
                                    <option value="UNIT">szt</option>
                                    <option value="SET">kpl</option>
                                    <option value="PAIR">par</option>
                                </select>

                                <label for="bulk_selling_mode" class="t">Formaty sprzedaży</label>
                                <select id="bulk_selling_mode" name="bulk_selling_mode" x-name="bulk_selling_mode">
                                    <option value="BUY_NOW">Kup teraz</option>
                                    <option value="AUCTION">Licytacja</option>
                                </select>

                                <label for="bulk_duration" class="t">Czas trwania</label>
                                <select id="bulk_duration" x-name="bulk_duration">
                                    {foreach $durations AS $duration}
                                        <option value="{$duration.id}" {if $product.duration == $duration.id}selected="selected"{/if}>{$duration.name}</option>
                                    {/foreach}
                                </select>

                                <label for="bulk_auto_renew" class="t">Wznawianie</label>
                                <select id="bulk_auto_renew" x-name="bulk_auto_renew">
                                    <option value="" selected="selected">Domyślnie</option>
                                    <option value="1">Tak</option>
                                    <option value="0">Nie</option>
                                </select>
                            </td>
                            <td style="border-bottom: none; vertical-align: top; padding-top:10px;">
                                <label for="bulk_price_buy_now" class="t">
                                    Cena Kup teraz (narzut)
                                    <a href="#" title="Dodaj marżę" x-name="bulk_price_buy_now" x-action="up"><i class="icon-plus-sign"></i></a>
                                    <a href="#" title="Usuń marżę" x-name="bulk_price_buy_now" x-action="down"><i class="icon-minus-sign"></i></a>
                                </label>

                                <div class="input-group">
                                    <input type="text" value="" size="11" id="bulk_price_buy_now" x-name="bulk_price_buy_now" data-cast="float" />
                                    <span class="input-group-addon">%</span>
                                </div>

                                <label for="bulk_send_tax" class="t">
                                    <input type="checkbox" id="bulk_send_tax" value="1" x-name="bulk_send_tax" style="margin-top: 5px;" {if $bulk_send_tax}checked="checked"{/if}>
                                    Wyślij wartość VAT
                                </label>

                                <label for="bulk_price_asking" class="t" style="margin-top: 6px">Cena wywoławcza</label>
                                <div class="input-group">
                                    <input type="text" value="" size="11" id="bulk_price_asking" x-name="bulk_price_asking" data-cast="float" />
                                    <span class="input-group-addon">{$marketplaces[$account->base_marketplace].currencySign}</span>
                                </div>

                                <label for="bulk_price_minimal" class="t" style="margin-top: 6px">Cena minimalna</label>
                                <div class="input-group">
                                    <input type="text" value="" size="11" id="bulk_price_minimal" x-name="bulk_price_minimal" data-cast="float" />
                                    <span class="input-group-addon">{$marketplaces[$account->base_marketplace].currencySign}</span>
                                </div>
                            </td>
                            <td style="vertical-align: top; border-bottom: none; padding-top:10px;">
                                <p>
                                    <a class="btn btn-default button bt-icon" href="#" x-name="bulk_images_all">
                                        <i class="icon-sitemap"></i> <span>Zaznacz wszystkie zdjęcia</span>
                                    </a>
                                </p>
                                <p>
                                    <a class="btn btn-default button bt-icon" href="#" x-name="bulk_images_del">
                                        <i class="icon-times"></i> <span>Odznacz wszystkie zdjęcia</span>
                                    </a>
                                </p>
                                <p>
                                    <a class="btn btn-default button bt-icon" href="#" x-name="bulk_images_first">
                                        <i class="icon-photo"></i> <span>Zaznacz pierwsze zdjęcie</span>
                                    </a>
                                </p>
                                <p>
                                    <a class="btn btn-default button bt-icon" href="#" x-name="bulk_images_invert">
                                        <i class="icon-retweet"></i> <span>Odwróć zaznaczenie zdjęć</span>
                                    </a>
                                </p>
                            </td>
                            <td style="vertical-align: top; border-bottom: none; padding-top:10px;">
                                <p>
                                    <select id="bulk_template" x-name="bulk_template" style="width: 100%;">
                                        {foreach $templates AS $template}
                                            <option value="{$template.id}">{$template.name}</option>
                                        {/foreach}
                                    </select>
                                </p>

                                <ul class="nav nav-tabs" id="itemTabBulk">
                                    <li class="active label-tooltip" data-toggle="tooltip" data-original-title="Opcje promowania">
                                        <a href="#itemTabBulk_promotion" aria-controls="itemTabBulk_promotion" role="tab" data-toggle="tab" data-item-tab="promotion">
                                            <i class="allegro-icon allegro-icon-promotion"></i>
                                        </a>
                                    </li>
                                    <li class="label-tooltip" data-toggle="tooltip" data-original-title="Cennik hurtowy">
                                        <a href="#itemTabBulk_wholesalePriceList" aria-controls="itemTabBulk_wholesalePriceList" role="tab" data-toggle="tab" data-item-tab="wholesalePriceList">
                                            <i class="allegro-icon allegro-icon-wholesale-price-list"></i>
                                        </a>
                                    </li>
                                    <li class="label-tooltip" data-toggle="tooltip" data-original-title="Tabela rozmiarów">
                                        <a href="#itemTabBulk_sizeTable" aria-controls="itemTabBulk_sizeTable" role="tab" data-toggle="tab" data-item-tab="sizeTable">
                                            <i class="allegro-icon allegro-icon-size-table"></i>
                                        </a>
                                    </li>
                                    <li class="label-tooltip" data-toggle="tooltip" data-original-title="Przedsprzedaż">
                                        <a href="#itemTabBulk_preorder" aria-controls="itemTabBulk_preorder" role="tab" data-toggle="tab" data-item-tab="preorder">
                                            <i class="allegro-icon allegro-icon-preorder"></i>
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content panel clearfix" id="itemTabBulk_Content" role="tabpanel">
                                    <div class="tab-pane active" id="itemTabBulk_promotion">
                                        <div x-name="promotionPackages" class="promo-auction">
                                            <label>
                                                <input type="radio" name="bulk[basePackages]" value="0" checked="checked" x-name="none">
                                                Bez promowania
                                            </label>
                                            {foreach $promotionPackages->basePackages as $basePackage}
                                                <label>
                                                    <input type="radio" name="bulk[basePackages]" value="{$basePackage->id}" x-name="{$basePackage->id}">
                                                    {$basePackage->name}
                                                </label>
                                            {/foreach}

                                            <p style="font-size: 85%; text-transform: uppercase; margin: 10px 0 5px;">Opcje dodatkowe</p>
                                            {foreach $promotionPackages->extraPackages as $extraPackage}
                                                <label>
                                                    <input type="checkbox" value="{$extraPackage->id}" x-name="{$extraPackage->id}">
                                                    {$extraPackage->name}
                                                </label>
                                            {/foreach}
                                        </div>
                                    </div>

                                    <div class="tab-pane" id="itemTabBulk_wholesalePriceList">
                                        <label for="bulk_wholesale_price" class="t">Cennik hurtowy</label>
                                        {if !empty($wholesalePriceList)}
                                            <select id="bulk_wholesale_price" name="bulk_wholesale_price" x-name="bulk_wholesale_price">
                                                <option value="0">-- Wybierz --</option>
                                                {foreach $wholesalePriceList as $wholesalePrice}
                                                    <option value="{$wholesalePrice->id}">{$wholesalePrice->benefits[0]->specification->name}</option>
                                                {/foreach}
                                            </select>
                                        {else}
                                            <p class="help-block">Brak skonfigurowanych cenników hurtowych</p>
                                        {/if}
                                    </div>

                                    <div class="tab-pane" id="itemTabBulk_sizeTable">
                                        <label for="bulk_size_table" class="t">Tabela rozmiarów</label>
                                        {if !empty($sizeTables)}
                                            <select id="bulk_size_table" name="bulk_size_table" x-name="bulk_size_table">
                                                <option value="0">-- Wybierz --</option>
                                                {foreach $sizeTables as $sizeTable}
                                                    <option value="{$sizeTable->id}">{$sizeTable->name}</option>
                                                {/foreach}
                                            </select>
                                        {else}
                                            <p class="help-block">Brak skonfigurowanych tabel rozmiarów</p>
                                        {/if}
                                    </div>

                                    <div class="tab-pane" id="itemTabBulk_preorder">
                                        <div class="preorder-auction">
                                            <label for="bulk_preorder" class="t">
                                                <input type="checkbox" id="bulk_preorder" name="bulk_preorder" value="1" x-name="bulk_preorder">
                                                Przedsprzedaż
                                            </label>
                                            <input type="text" name="bulk_preorder_date" class="datepicker fixed-width-md" value="{$smarty.now|date_format:'%d.%m.%Y'}" x-name="bulk_preorder_date" disabled="disabled" style="display: none;">
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </fieldset>
            </div>
        </div><div class="row">
            <div class="panel clearfix form-horizontal">
                <fieldset>
                    <div class="panel-heading">
                        <i class="icon-asterisk"></i> Dodatkowe ustawienia
                    </div>

                    <div class="row">
                        <div class="col-md-5">
                            <div class="form-group">
                                <div class="row">
                                    <div class="col-md-6">
                                        <label for="start" class="control-label t">Oferta pojawi się na Allegro</label>
                                        <select id="start" name="start">
                                            <option value="0" selected="selected">Natychmiast</option>
                                            <option value="1">Rozpocznij w innym terminie</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="start_time" class="control-label t">&nbsp;</label>
                                        <input type="text" id="start_time" name="start_time" class="datetimepicker fixed-width-xxl" value="{$smarty.now|date_format:'%d.%m.%Y %H:%M'}" disabled="disabled" style="display: none;">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group after-sales-fields" x-name="after_sales">
                                {include file='./after-sales.tpl'}
                            </div>
                        </div>

                        <div class="col-md-offset-1 col-md-3">
                            <div class="form-group" x-name="message_to_seller">
                                <label for="message_to_seller" class="control-label t">Uwagi do zakupu (wiadomość dla sprzedającego)</label>
                                <select id="message_to_seller" name="message_to_seller">
                                    <option value="OPTIONAL" {if $message_to_seller}selected="selected"{/if}>Tak - opcjonalne pole "uwagi do zakupu"</option>
                                    <option value="HIDDEN" {if !$message_to_seller}selected="selected"{/if}>Nie - brak pola "uwagi do zakupu"</option>
                                </select>
                            </div>
                            <div class="form-group" x-name="offer_b2b">
                                <label for="offer_b2b_only" class="control-label t">Kto może kupić ofertę</label>
                                <select id="offer_b2b_only" name="offer_b2b_only">
                                    <option value="0" {if !$b2b_only}selected="selected"{/if}>wszyscy klienci</option>
                                    <option value="1" {if $b2b_only}selected="selected"{/if}>tylko klienci biznesowi</option>
                                </select>
                            </div>
                            <div class="form-group" x-name="additional_services">
                                <label for="additional_services" class="control-label t">Grupa dodatkowych usług</label>
                                {if !empty($additionalServices)}
                                    <select id="additional_services" name="additional_services">
                                        <option value="0">-- Wybierz --</option>
                                        {foreach $additionalServices as $additionalService}
                                            <option value="{$additionalService->id}">{$additionalService->name}</option>
                                        {/foreach}
                                    </select>
                                {else}
                                    <p class="help-block">Brak skonfigurowanych dodatkowych usług</p>
                                {/if}
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div><div class="row">
            <div class="panel form-horizontal col-lg-12">
                <fieldset>
                    <div class="panel-heading">
                        <i class="icon-truck"></i> Płatność i dostawa
                    </div>

                    <div class="row">
                        <div class="col-md-12 col-lg-3 pas-fields" x-name="pas">
                            {$pas_fields}
                        </div>
                        <div class="col-md-12 col-lg-9" x-name="shipping_rates">
                            <div class="pas-fields clearfix">
                                <div class="form-group">
                                    <label for="shipping_rate" class="control-label col-lg-3">Cennik dostawy</label>
                                    <div class="col-lg-4">
                                        <select id="shipping_rate" name="shipping_rate" class="fixed-width-xxl fixed-width-xl" id="shipping_rate">
                                            {foreach $shipping_rates as $shipping_rate}
                                                <option value="{$shipping_rate.id}" {if $shipping_rate.id == $shippingRateSelectedId}selected="selected"{/if}>{$shipping_rate.name}</option>
                                            {/foreach}
                                        </select>
                                    </div>
                                    <div class="col-lg-8">
                                        {include file='../marketplace-list.tpl' marketplaces=$marketplaces availableMarketplaces=$shippingRateMarketplaces[$shippingRateSelectedId]}
                                    </div>
                                </div>
                            </div>
                            <hr />

                            {include file='../shipments.tpl' shipments=$shipments editable=false}
                        </div>
                    </div>
                </fieldset>
            </div>
        </div></form>

    {include file='./bulk-category-modal.tpl'}
    {include file='./bulk-category-parameters-modal.tpl'}
    {include file='./bulk-product-gpsr-modal.tpl'}

    <div class="modal_alert xallegro_modal_alert" id="account_change_modal_alert">
        <div class="modal_alert-content">
            <div class="modal_alert-message">
                <h2>{l s='Czy na pewno chcesz zmienić konto?' mod='x13allegro'}</h2>
                <p>{l s='Spowoduje to utratę wcześniej wprowadzonych zmian' mod='x13allegro'}</p>
            </div>
            <div class="modal_alert-buttons">
                <button type="button" class="btn btn-default modal_alert-cancel">{l s='Nie' mod='x13allegro'}</button>
                <button type="button" class="btn btn-primary modal_alert-confirm">{l s='Tak' mod='x13allegro'}</button>
            </div>
        </div>
    </div>

    <div class="modal_alert xallegro_modal_alert" id="price_edit_modal_alert">
        <div class="modal_alert-content">
            <div class="modal_alert-message">
                <h2>{l s='Czy chcesz ustawić cenę dla tej waluty ręcznie?' mod='x13allegro'}</h2>
                <p>{l s='Spowoduje to wyłączenie automatycznego przeliczania tej ceny według kursu wymiany' mod='x13allegro'}</p>
            </div>
            <div class="modal_alert-buttons">
                <button type="button" class="btn btn-default modal_alert-cancel">{l s='Nie' mod='x13allegro'}</button>
                <button type="button" class="btn btn-primary modal_alert-confirm">{l s='Tak' mod='x13allegro'}</button>
            </div>
        </div>
    </div>

    <div class="modal_alert xallegro_modal_alert" id="xproductization_product_preview_description_modal">
        <div class="modal-content">
            <div class="modal-header x13allegro-modal-header modal_alert-header">
                <button type="button" class="close x13allegro-modal-close modal_alert-cancel"><span>&times;</span></button>
                <h4 class="x13allegro-modal-title">{l s='Podgląd opisu produktu z katalogu' mod='x13allegro'}</h4>
                <h6 class="x13allegro-modal-title-small">{* product name from product-selection-modal-content *}</h6>
            </div>
            <div class="modal-body x13allegro-modal-body clearfix">
                {* html from product-selection-modal-content *}
            </div>
        </div>
    </div>

    <div class="modal_alert xallegro_modal_alert" id="xproductization_product_preview_images_modal">
        <div class="modal-content">
            <div class="modal-header x13allegro-modal-header modal_alert-header">
                <button type="button" class="close x13allegro-modal-close modal_alert-cancel"><span>&times;</span></button>
                <h4 class="x13allegro-modal-title">{l s='Podgląd zdjęć produktu z katalogu' mod='x13allegro'}</h4>
                <h6 class="x13allegro-modal-title-small">{* product name from product-selection-modal-content *}</h6>
            </div>
            <div class="modal-body x13allegro-modal-body clearfix">
                {* html from product-selection-modal-content *}
            </div>
        </div>
    </div>

    <script type="text/javascript">
        var iso = '{$iso}';
        var pathCSS = '{$path_css}';
        var ad = '{$ad}';
        var xBackLink = '{$link->getAdminLink('AdminXAllegroPerform')}';
        var xOffersListList = '{$link->getAdminLink('AdminXAllegroAuctionsList')}';
        var XAllegro = new X13Allegro();
        XAllegro.productizationName = '{$productization_name}';
        XAllegro.productizationDescription = '{$productization_description}';
        XAllegro.productizationMode = '{$productization_mode}';
        XAllegro.productSelectMode = {$product_select_mode};
        XAllegro.shippingRateMarketplaces = {$shippingRateMarketplaces|json_encode};
        XAllegro.initPerformProductization();
        XAllegro.auctionForm();

        $(document).ready(function() {
            if ($(".datepicker").length > 0) {
                $(".datepicker").datepicker({
                    onClose: function (date, el) {
                        $(el).trigger('change');
                    }
                });
            }

            if ($(".datetimepicker").length > 0) {
                $(".datetimepicker").datetimepicker();
            }

            {foreach $products AS $index => $product}
                $('#images_sortable_' + {$index}).sortable({
                    update: function(event, ui) {
                        XAllegro.updateImagesPositions({$index});
                    }
                });
            {/foreach}
        });
    </script>
{/if}
{extends file="helpers/options/options.tpl"}

{block name="leadin"}
    {if $ionCubeLicenseInfo !== false}
        <div class="panel form-horizontal">
            <div class="panel-heading">
                <i class="icon-cogs"></i> {l s='Licencja' mod='x13allegro'}
            </div>

            <div class="form-wrapper">
                <div class="form-group">
                    <div class="col-lg-8 col-lg-offset-4">
                        {$ionCubeLicenseInfo.html_content}
                    </div>
                </div>
            </div>
        </div>
    {/if}
{/block}

{block name="input"}
    {if $key == 'AUCTION_MARKETPLACE_CONVERSION_RATE_VALUE'}
        <div class="col-lg-4">
            <p>
                Uzupełnij kurs wymiany na podstawie domyślnej waluty sklepu<br>
                Domyślna waluta sklepu: <i>{$field['currencyDefault']->name} ({$field['currencyDefault']->sign})</i>
            </p>

            <table class="table productization-search-table">
                <thead>
                    <tr>
                        <th>{l s='Rynek' mod='x13allegro'}</th>
                        <th>{l s='Waluta' mod='x13allegro'}</th>
                        <th>{l s='Kurs wymiany' mod='x13allegro'}</th>
                    </tr>
                </thead>
                <tbody>
                    {foreach $field['currencies'] as $currency}
                        <tr>
                            <td>
                                {$currency.marketplace|regex_replace:"/^(\w+)/u":"<b>$1</b>"}
                            </td>
                            <td>
                                {$currency.currencyName} ({$currency.currencySign})
                                {if $field['currencyDefault']->id == $currency.currencyId}
                                    <span class="label-tooltip" data-toggle="tooltip" data-original-title="Domyślna waluta sklepu">
                                        &nbsp;&nbsp;<i class="icon-info-circle"></i>
                                    </span>
                                {/if}
                            </td>
                            <td>
                                <input class="form-control fixed-width-sm xcast xcast-float" type="text" size="10" name="AUCTION_MARKETPLACE_CONVERSION_RATE_VALUE[{$currency.currencyId}]" value="{$currency.currencyRate}" {if $field['currencyDefault']->id == $currency.currencyId}readonly="readonly"{/if}>
                            </td>
                        </tr>
                    {/foreach}
                </tbody>
            </table>
        </div>
    {elseif $key == 'PRODUCTIZATION_SEARCH'}
        <div class="col-lg-5">
            <table class="table productization-search-table">
                <thead>
                    <tr>
                        <th>{l s='Szukaj według' mod='x13allegro'}</th>
                        <th>{l s='Wybierz automatycznie' mod='x13allegro'}</th>
                    </tr>
                </thead>
                <tbody>
                    {include file='./productization-search-table.tpl' searchLabel='Kod GTIN' searchDesc='EAN13, ISBN, UPC' searchInput='GTIN'}
                    {include file='./productization-search-table.tpl' searchLabel='Kod MPN' searchInput='MPN'}
                    {include file='./productization-search-table.tpl' searchLabel='Kod referencyjny' searchInput='reference'}
                    {include file='./productization-search-table.tpl' searchLabel='Nazwa produktu' searchInput='product_name'}
                </tbody>
            </table>
        </div>
    {elseif $key == 'CRON_URL'}
        <div class="col-lg-9">
            <input type="{$field['type']}" size="{if isset($field['size'])}{$field['size']|intval}{else}5{/if}" value="{$field['value']|escape:'htmlall':'UTF-8'}" readonly="readonly" />
        </div>
        <div class="col-lg-9 col-lg-offset-3"><div class="help-block">{$field['desc']}</div></div>
    {elseif $field['type'] == 'hr'}
        <hr />
    {elseif $field['type'] == 'separator'}
        <div class="col-lg-9 col-lg-push-1">
            <h4 style="font-size: 18px; font-weight: 600; margin: 20px 0 0 0;">{$field['heading']}</h4>
            <hr style="margin: 15px 0 0 0;">
        </div>
    {elseif $field['type'] == 'badge_authorize'}
        <div class="col-lg-9" style="padding-top: 7px;">
            <span class="badge badge-danger">{l s='autoryzuj konto aby zmienić te ustawienia' mod='x13allegro'}</span>
        </div>
    {elseif $field['type'] == 'button'}
        <a class="{$field['button_class']}" href="{if isset($field['button_href'])}{$field['button_href']}{else}#{/if}" {if isset($field['button_id'])}id="{$field['button_id']}"{/if}>
            {$field['button_label']|escape:'htmlall':'UTF-8'}
        </a>
    {elseif $field['type'] == 'checkbox'}
        <div class="col-lg-9">
            {foreach $field['choices'] AS $choice}
                <p class="checkbox clearfix">
                    {strip}
                        <label class="col-lg-5" for="{$key}_{$choice.key}" id="choice_{$key}_{$choice.key}">
                            <input type="checkbox" name="{$key}[{$choice.key}]" id="{$key}_{$choice.key}" value="{$choice.key}" {if isset($field['value'][$choice.key])}checked="checked"{/if} {if isset($choice.disabled) && $choice.disabled}disabled="disabled"{/if} /> <span class="choice-label">{$choice.name}</span> {if isset($choice.desc) && $choice.desc}<span class="help-block">{$choice.desc}</span>{/if}
                        </label>
                    {/strip}
                </p>
            {/foreach}
        </div>
    {elseif $field['type'] == 'bool'}
        <div class="col-lg-9">
            <span class="switch prestashop-switch fixed-width-lg {if isset($field['class'])}{$field['class']}{/if}">
                {strip}
                    <input type="radio" name="{$key}" id="{$key}_on" value="1" {if $field['value']} checked="checked"{/if}{if isset($field['js']['on'])} {$field['js']['on']}{/if}{if isset($field['disabled']) && $field['disabled']} disabled="disabled"{/if}/>
                    <label for="{$key}_on" class="radioCheck">
                    {l s='Yes' d='Admin.Global'}
                </label>
                    <input type="radio" name="{$key}" id="{$key}_off" value="0" {if !$field['value']} checked="checked"{/if}{if isset($field['js']['off'])} {$field['js']['off']}{/if}{if isset($field['disabled']) && $field['disabled']} disabled="disabled"{/if}/>
                    <label for="{$key}_off" class="radioCheck">
                    {l s='No' d='Admin.Global'}
                </label>
                {/strip}
                <a class="slide-button btn"></a>
            </span>
        </div>
    {elseif $field['type'] == 'select'}
        <div class="col-lg-9">
            {if $field['list']}
                <select class="form-control fixed-width-xxl {if isset($field['class'])}{$field['class']}{/if}" name="{$key}{if isset($field['multiple']) && $field['multiple']}[]{/if}"{if isset($field['js'])} onchange="{$field['js']}"{/if} id="{$key}" {if isset($field['size'])} size="{$field['size']}"{/if}{if isset($field['disabled']) && $field['disabled']} disabled="disabled"{/if}{if isset($field['multiple']) && $field['multiple']} multiple size=10 style="float: left; width: auto !important;"{/if}>
                    {foreach $field['list'] AS $k => $option}
                        <option value="{$option[$field['identifier']]}"{if $field['value'] == $option[$field['identifier']]|string_format:"%s" || (is_array($field['value']) && in_array($option[$field['identifier']]|string_format:"%s", $field['value']))} selected="selected"{/if}>{$option['name']}</option>
                    {/foreach}
                </select>
                {if isset($field['multiple']) && $field['multiple']}
                    <a href="#" class="btn btn-default js-x13-multiselect-disable-all" style="float: left; margin-left: 10px;">{l s='Odznacz wybrane' mod='x13allegro'}</a>
                {/if}
            {elseif isset($input.empty_message)}
                {$input.empty_message}
            {/if}
        </div>
    {else}
        {$smarty.block.parent}
    {/if}

    {* overriden field types - fix description *}
    {if in_array($field['type'], ['button', 'checkbox', 'bool', 'select']) && isset($field['desc']) && !empty($field['desc'])}
        <div class="col-lg-9 col-lg-offset-3">
            <div class="help-block">
                {if is_array($field['desc'])}
                    {foreach $field['desc'] as $p}
                        {if is_array($p)}
                            <span id="{$p.id}">{$p.text}</span><br />
                        {else}
                            {$p}<br />
                        {/if}
                    {/foreach}
                {else}
                    {$field['desc']}
                {/if}
            </div>
        </div>
    {/if}
{/block}

{block name="defaultOptions"}
    {if isset($xallegro_update) && $xallegro_update}
        <div class="panel">
            <div class="panel-heading">{l s='Aktualizacja modułu' mod='x13allegro'}</div>
            <table class="table" cellspacing="0" cellpadding="0" style="width: 100%; margin: auto;">
                <thead>
                <tr class="nodrag nodrop">
                    <th width="120px" class="center">{l s='Wersja' mod='x13allegro'}</th>
                    <th width="200px" class="center">{l s='Data wydania' mod='x13allegro'}</th>
                    <th>{l s='Lista zmian' mod='x13allegro'}</th>
                </tr>
                </thead>
                <tbody>
                {foreach $xallegro_update->changelog->bundle AS $bundle}
                    <tr>
                        <td class="center">{$bundle->attributes()->version}</td>
                        <td class="center">{$bundle->attributes()->date|date_format:"%d.%m.%Y"}</td>
                        <td>
                            <ul style="margin: 4px 0 4px 0;">
                                {foreach $bundle->list AS $change}
                                    {if !empty($change)}
                                        <li>{$change|ltrim:'- '}</li>
                                    {/if}
                                {/foreach}
                            </ul>
                        </td>
                    </tr>
                {/foreach}
                </tbody>
            </table>
        </div>
    {/if}
    
    {$smarty.block.parent}
{/block}

{block name="after"}
    <div class="modal" id="xallegro_offer_full_synchronization_modal" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header x13allegro-modal-header">
                    <button type="button" class="close x13allegro-modal-close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="x13allegro-modal-title">{l s='Wymuszenie stanu ofert według informacji z Allegro' mod='x13allegro'}</h4>
                </div>
                <div class="modal-body x13allegro-modal-body">
                    <div class="alert alert-info">
                        <p><strong>{l s='Pomaga roziązać problemy z nieprawidłowo ustawionym statusem oferty, widocznością na rynkach zagranicznych, nie aktualizowaniem się ilości lub cen, błędnie zarchiwizowanym powiązaniem, błędnie powiązanym kontem.' mod='x13allegro'}</strong></p>
                        <ul style="margin-top: 15px;">
                            <li>{l s='Aktualizuje lokalne informacje o wszystkich powiązanych ofertach, tj.: ilość, cena, status (zakończona/zaplanowana) oraz widoczność na zagranicznych rynkach, według aktualnych danych z Allegro.' mod='x13allegro'}</li>
                            <li>{l s='Naprawia błędne powiązania ofert z kontem, dotyczy błędów "403: Nie masz uprawnień do operacji na wskazanej ofercie".' mod='x13allegro'}</li>
                            <li>{l s='Archiwizuje powiązania do nieistniejących ofert, oraz przywraca błędnie zarchiwizowane powiązania (jeśli nie zostały trwale usunięte).' mod='x13allegro'}</li>
                        </ul>
                    </div>

                    <div class="form-wrapper clearfix" style="display: none;">
                        <h4 id="synchronization_info"><i class="icon-refresh icon-spin"></i> <span>Trwa pobieranie informacji o ofertach dla kont Allegro</span></h4>
                        <ul id="synchronization_accounts"></ul>

                        <div class="progress">
                            <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer x13allegro-modal-footer">
                    <button type="button" name="closeOfferFullSynchronization" class="btn btn-default pull-left" data-dismiss="modal">{l s='Zamknij' mod='x13allegro'}</button>
                    <button type="button" name="startOfferFullSynchronization" class="btn btn-primary pull-right">{l s='Rozpocznij' mod='x13allegro'}</button>

                    <a href="{$link->getAdminLink('AdminXAllegroAuctionsList')}" id="offerFullSynchronizationAuctionsList" class="btn btn-primary pull-right" style="display: none;">{l s='Lista ofert' mod='x13allegro'}</a>
                </div>
            </div>
        </div>
    </div>

    <script type="text/javascript">
        var XAllegro = new X13Allegro();
        XAllegro.configurationForm();
    </script>
{/block}

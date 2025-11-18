{*
* 2007-2025 PrestaShop
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
* @author    PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}

<div class="modal fade" id="fetchEanModal">
    <div class="modal-dialog">
        <div class="modal-content">
            {* Formularz, który zostanie wysłany metodą POST *}
            <form method="post" action="{$post_action|escape:'htmlall':'UTF-8'}&id_xallegro_account=" id="fetch-ean-form">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
                    <h4 class="modal-title">{l s='Pobierz EAN z Allegro' mod='x13allegro'}</h4>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label>{l s='Konto Allegro' mod='x13allegro'}</label>
                        <select class="form-control" id="fetch-ean-account">
                            {foreach from=$accounts item=acc}
                                <option value="{$acc.id|intval}" {if $acc.id == $current_account_id}selected{/if}>{$acc.username|escape:'html':'UTF-8'}</option>
                            {/foreach}
                        </select>
                        <p class="help-block">{l s='Wybierz konto, w kontekście którego zostanie wykonana operacja.' mod='x13allegro'}</p>
                    </div>

                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="overwrite" value="1">
                            {l s='Nadpisz istniejące EAN w powiązaniach' mod='x13allegro'}
                        </label>
                    </div>

                    {* Przeniesienie zaznaczonych checkboxów z listy do formularza POST *}
                    {foreach from=$selectedBoxes item=box}
                        <input type="hidden" name="{$table}Box[]" value="{$box|escape:'html':'UTF-8'}">
                    {/foreach}

                    {* Niezbędne pola PrestaShop do obsługi akcji masowej *}
                    <input type="hidden" name="submitBulkfetchEan{$table}" value="1">
                    <input type="hidden" name="token" value="{$token|escape:'html':'UTF-8'}">
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Anuluj' mod='x13allegro'}</button>
                    <button type="submit" class="btn btn-primary" id="fetch-ean-submit">{l s='Rozpocznij pobieranie' mod='x13allegro'}</button>
                </div>
            </form>
        </div>
    </div>
</div>

{* Ten skrypt jest częścią modala i dba o prawidłowe wysłanie formularza *}
<script type="text/javascript">
(function() {
    var form = document.getElementById('fetch-ean-form');
    var select = document.getElementById('fetch-ean-account');
    if (!form || !select) return;

    // Musimy nasłuchiwać na submit formularza, a nie kliknięcie przycisku, 
    // aby zapewnić, że walidacja (jeśli jakaś jest) się wykona.
    form.addEventListener('submit', function(ev) {
        // Wstrzyknij wybrane id_xallegro_account do atrybutu ACTION formularza
        var baseAction = this.getAttribute('action').split('&id_xallegro_account=')[0];
        this.setAttribute('action', baseAction + '&id_xallegro_account=' + encodeURIComponent(select.value));
        
        // Nie zatrzymujemy domyślnej akcji (wysłania formularza)
    });
})();
</script>
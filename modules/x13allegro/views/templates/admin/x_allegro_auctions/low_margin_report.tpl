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
<div class="panel">
    <div class="panel-heading">
        <i class="icon-stats-down"></i> {l s='Raport Ofert z Niską Marżą (< 20%)' mod='x13allegro'} - {$allegroAccount->username}
    </div>
    
    <div class="alert alert-info">
        {l s='Poniższa lista zawiera wszystkie aktywne, powiązane oferty, dla których obliczony narzut brutto jest niższy niż 20%.' mod='x13allegro'}
        {l s='Proces generowania raportu jest czasochłonny i może obciążać serwer.' mod='x13allegro'}
    </div>

    {if isset($reportData) && count($reportData)}
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>{l s='ID Oferty' mod='x13allegro'}</th>
                    <th>{l s='Nazwa Oferty' mod='x13allegro'}</th>
                    <th class="text-right">{l s='Cena sprzedaży' mod='x13allegro'}</th>
                    <th class="text-right">{l s='Obliczona cena zakupu' mod='x13allegro'}</th>
                    <th class="text-right">{l s='Narzut (kwota)' mod='x13allegro'}</th>
                    <th class="text-right">{l s='Narzut (%)' mod='x13allegro'}</th>
                    <th>{l s='Akcje' mod='x13allegro'}</th>
                </tr>
            </thead>
            <tbody>
                {foreach from=$reportData item=row}
                <tr style="background-color: #f2dede;">
                    <td>{$row.id_auction}</td>
                    <td>{$row.name}</td>
                    <td class="text-right">{$row.price}</td>
                    <td class="text-right">{$row.purchase_price}</td>
                    <td class="text-right">{$row.margin_value}</td>
                    <td class="text-right"><strong>{$row.margin_percent}</strong></td>
                    <td>
                        <a href="{$row.edit_link|escape:'html':'UTF-8'}" title="{l s='Edytuj produkt' mod='x13allegro'}" target="_blank" class="btn btn-default">
                            <i class="icon-pencil"></i> {l s='Edytuj produkt' mod='x13allegro'}
                        </a>
                        <a href="{$row.allegro_link|escape:'html':'UTF-8'}" title="{l s='Zobacz na Allegro' mod='x13allegro'}" target="_blank" class="btn btn-default">
                            <i class="icon-external-link"></i> {l s='Zobacz na Allegro' mod='x13allegro'}
                        </a>
                    </td>
                </tr>
                {/foreach}
            </tbody>
        </table>
    {else}
        <div class="alert alert-success">
            {l s='Nie znaleziono żadnych ofert z narzutem poniżej 20%.' mod='x13allegro'}
        </div>
    {/if}
    <div class="panel-footer">
        <a href="{$back_link|escape:'html':'UTF-8'}" class="btn btn-default">
            <i class="process-icon-back"></i> {l s='Wróć do listy ofert' mod='x13allegro'}
        </a>
    </div>
</div>
{**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License 3.0 (AFL-3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* https://opensource.org/licenses/AFL-3.0
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
* @author PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
* International Registered Trademark & Property of PrestaShop SA
*}
{strip}
<div class="tvprduct-image-info-wrapper clearfix row product-1" data-product-layout="1">
    {hook h='displayProductTabVideo'}
    <div class="col-md-6 tv-product-page-image">
        {block name='product_cover_thumbnails'}
        {include file='catalog/_partials/product-cover-thumbnails.tpl'}
        {/block}
    </div>
    <div class="col-md-6 tv-product-page-content">
        <div class="tvproduct-title-brandimage" itemprop="itemReviewed" itemscope itemtype="http://schema.org/Thing">
            {block name='page_header_container'}
            {block name='page_header'}
            <h1 class="h1" itemprop="name">{block name='page_title'}{$product.name}{/block}</h1>
            {/block}
            {/block}
            <div class="tvcms-product-brand-logo">
                {if isset($manufacturer_image_url)}
                <a href="{$product_brand_url}" class="tvproduct-brand">
                    <img src="{$manufacturer_image_url}" alt="{$product_manufacturer->name}" title="{$product_manufacturer->name}" height="75px" width="170px" loading="lazy">
                </a>
                {/if}
            </div>
        </div>
        {* ====== KOD SMARTY DO GENEROWANIA IKON ====== *}
        {assign var="features_for_badges" value=$product.features}
        {assign var="dietBadges_direct" value=[]}

        {* Mapowanie nazw cech na ikony (kolor, litery, tooltip) - KLUCZE MUSZĄ DOKŁADNIE ODPOWIADAĆ NAZWOM CECH W PRESTASHOP *}
        {assign var="featureMappings_direct" value=[
            'Dieta: Wegańska'                      => ['color' => '#8CC63F', 'letters' => 'VEGE', 'tooltip' => 'Wegańska'],
            'Dieta: Wegetariańska'                 => ['color' => '#808000', 'letters' => 'VEG',  'tooltip' => 'Wegetariańska'],
            'Dieta: Bez glutenu'                   => ['color' => '#FFA500', 'letters' => 'GF',   'tooltip' => 'Bez glutenu'],
            'Dieta: Keto / Low-Carb'               => ['color' => '#800080', 'letters' => 'KETO', 'tooltip' => 'Low-Carb / Keto'],
            'Certyfikat: BIO'                      => ['color' => '#ADFF2F', 'letters' => 'BIO',  'tooltip' => 'Certyfikat BIO'],
            'Bez: Laktozy'                         => ['color' => '#87CEEB', 'letters' => 'NL',   'tooltip' => 'Bez laktozy'],
            'Bez: Cukru'                           => ['color' => '#8B4513', 'letters' => 'NS',   'tooltip' => 'Bez cukru'],
            'Dieta: Niski Indeks Glikemiczny'      => ['color' => '#4CAF50', 'letters' => 'IG',   'tooltip' => 'Niski indeks glikemiczny']
        ]}

        {if !empty($features_for_badges)}
            {foreach from=$features_for_badges item=feature}
                {* Walidacja: czy $feature ma 'name' i 'value' *}
                {if is_array($feature) && isset($feature.name) && isset($feature.value)}
                    {assign var="featureValueLower" value=$feature.value|lower}
                    {* Jeżeli nazwa cechy jest w mapowaniu i wartość = tak/yes/1/true -> dodaj badge *}
                    {if isset($featureMappings_direct[$feature.name]) && in_array($featureValueLower, ['tak', 'yes', '1', 'true'])}
                        {assign var="dietBadges_direct" value=$dietBadges_direct|array_merge:[ $featureMappings_direct[$feature.name] ]}
                    {/if}
                {/if}
            {/foreach}
        {/if}

        {if !empty($dietBadges_direct)}
            <div class="dietamamyto-icons-wrapper">
                <div class="diet-badges-container">
                    {foreach from=$dietBadges_direct item=badge}
                        <span class="diet-badge" style="background-color: {$badge.color};" title="{$badge.tooltip|escape:'html':'UTF-8'}">
                            {$badge.letters}
                        </span>
                    {/foreach}
                </div>
            </div>
        {/if}
        {* ====== KONIEC KODU SMARTY DO GENEROWANIA IKON ====== *}

        {* Start Product Comment *}
        {hook h='displayReviewProductList' product=$product}
        {* End Product Comment *}

        {block name='product_prices'}
            {include file='catalog/_partials/product-prices.tpl'}
        {/block}

        {block name='product_availability'}
        {if $product.show_availability && $product.availability_message}
        <span id="product-availability">
            {if $product.availability == 'available'}
            <i class="material-icons rtl-no-flip product-available">&#xE5CA;</i>
            {elseif $product.availability == 'last_remaining_items'}
            <i class="material-icons product-last-items">&#xE002;</i>
            {else}
            <i class="material-icons product-unavailable">&#xE14B;</i>
            {/if}
            {$product.availability_message}
        </span>
        {/if}
        {/block}

        {block name='product_description_short'}
        {if $product.description_short }
        <div id="product-description-short-{$product.id}" itemscope itemprop="description" class="tvproduct-page-decs">{$product.description_short nofilter}</div>
        {/if}
        {/block}

        {hook h='displayPricePerUnit' product=$product}
        {hook h='displayProductProWeightSelector' product=$product}
        {hook h='displayProductFlavorSelector' product=$product}

        {if !empty($product.specific_prices.from) && !empty($product.specific_prices.to) && $product.specific_prices.from != '0000-00-00 00:00:00' && $product.specific_prices.to != '0000-00-00 00:00:00'}
            {include file='catalog/_partials/miniatures/product-timer.tpl' timer=$product.specific_prices.to}
        {/if}

        <div class="product-information tvproduct-special-desc">
            {if $product.is_customizable && count($product.customizations.fields)}
            {block name='product_customization'}
                {include file="catalog/_partials/product-customization.tpl" customizations=$product.customizations}
            {/block}
            {/if}
            <div class="product-actions">
                {block name='product_buy'}
                <form action="{$urls.pages.cart}" method="post" id="add-to-cart-or-refresh">
                    <input type="hidden" name="token" value="{$static_token}">
                    <input type="hidden" name="id_product" value="{$product.id}" id="product_page_product_id">
                    <input type="hidden" name="id_customization" value="{$product.id_customization}" id="product_customization_id">
                    {block name='product_variants'}
                        {include file='catalog/_partials/product-variants.tpl'}
                    {/block}
                    {block name='product_pack'}
                    {if $packItems}
                        <div class="product-pack">
                            <p class="h4">{l s='This pack contains' d='Shop.Theme.Catalog'}</p>
                            {foreach from=$packItems item="product_pack"}
                                {block name='product_miniature'}
                                    {include file='catalog/_partials/miniatures/pack-product.tpl' product=$product_pack}
                                {/block}
                            {/foreach}
                        </div>
                    {/if}
                    {/block}
                    {block name='product_discounts'}
                        {include file='catalog/_partials/product-discounts.tpl'}
                    {/block}
                    {block name='product_add_to_cart'}
                        {include file='catalog/_partials/product-add-to-cart.tpl'}
		            {/block}
                    {hook h='displayCustomtab'}
                    {* {block name='product_additional_info'}
                        {include file='catalog/_partials/product-additional-info.tpl'}
                    {/block} *}
                    {block name='product_refresh'}{/block}
                </form>
                {/block}
            </div>
        </div>

        {block name='hook_display_reassurance'}
            {hook h='displayReassurance'}
        {/block}
    </div>
</div>
{/strip}

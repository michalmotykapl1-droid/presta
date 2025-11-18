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
* versions in the future.
* If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
* @author PrestaShop SA <contact@prestashop.com>
    * @copyright 2007-2025 PrestaShop SA
    * @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
    * International Registered Trademark & Property of PrestaShop SA
    *}
    {strip}
    <div class="product-line-grid">
        <div class="product-line-grid-left col-md-3 col-xs-4">
            <span class="product-image media-middle">
                 <img src="{$product.cover.bySize.cart_default.url}" alt="{$product.name|escape:'quotes'}" loading="lazy">
            </span>
        </div>
        <div class="product-line-grid-body col-md-4 col-xs-8">
            <div class="product-line-info">
                <div class="tvproduct-name">
                    <div class="product-title">
                        <a href="{$product.url}" data-id_customization="{$product.id_customization|intval}">
                            <h6>{$product.name}</h6>
                        </a>
                    </div>
                 </div>
            </div>
            <div class="product-price-and-shipping product-line-info {if $product.has_discount}has-discount{/if}">
                <span class="price">{$product.price}</span>
                {if $product.has_discount}
                <span class="regular-price">{$product.regular_price}</span>
                 {if $product.discount_type === 'percentage'}
                <span class="discount discount-percentage tvproduct-discount-price">-{$product.discount_percentage_absolute}</span>
                {else}
                <span class="discount discount-amount tvproduct-discount-price">-{$product.discount_to_display}</span>
                {/if}
                {/if}
                 <div class="current-price">
                    {if $product.unit_price_full}
                    <div class="unit-price-cart">{$product.unit_price_full}</div>
                    {/if}
                </div>
             </div>
            {foreach from=$product.attributes key="attribute" item="value"}
            <div class="product-line-info">
                <span class="label">{$attribute} : </span>
                <span class="value">{$value}</span>
            </div>
            {/foreach}
             {if $product.customizations|count}
            {block name='cart_detailed_product_line_customization'}
            {foreach from=$product.customizations item="customization"}
            <a href="#" data-toggle="modal" data-target="#product-customizations-modal-{$customization.id_customization}">{l s='Product customization' d='Shop.Theme.Catalog'}</a>
            <div class="modal fade customization-modal" id="product-customizations-modal-{$customization.id_customization}" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog" role="document">
                     <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                            <h4 class="modal-title">{l s='Product customization' d='Shop.Theme.Catalog'}</h4>
                        </div>
                         <div class="modal-body">
                            {foreach from=$customization.fields item="field"}
                            <div class="product-customization-line row">
                                 <div class="col-sm-3 col-xs-4 label">
                                    {$field.label}
                                </div>
                                 <div class="col-sm-9 col-xs-8 value">
                                    {if $field.type == 'text'}
                                    {if (int)$field.id_module}
                                     {$field.text nofilter}
                                    {else}
                                    {$field.text}
                                     {/if}
                                    {elseif $field.type == 'image'}
                                     <img src="{$field.image.small.url}">
                                    {/if}
                                </div>
                             </div>
                            {/foreach}
                        </div>
                    </div>
                </div>
             </div>
            {/foreach}
            {/block}
            {/if}
        </div>
        <div class="product-line-grid-right product-line-actions col-md-5 col-xs-12">
            <div class="row">
                <div class="col-xs-4 hidden-md-up"></div>
                <div class="col-md-6 col-xs-7 tvcart-incr-decr-price">
                    <span class="product-price">
                        <strong>
                            {if isset($product.is_gift) && $product.is_gift}
                                 <span class="gift">{l s='Gift' d='Shop.Theme.Checkout'}</span>
                            {else}
                            {$product.total}
                             {/if}
                        </strong>
                    </span>
                    {if isset($product.is_gift) && $product.is_gift}
                    <span class="gift-quantity">{$product.quantity}</span>
                     {else}
                    <input class="js-cart-line-product-quantity" data-down-url="{$product.down_quantity_url}" data-up-url="{$product.up_quantity_url}" data-update-url="{$product.update_quantity_url}" data-product-id="{$product.id_product}" type="text" value="{$product.quantity}" name="product-quantity-spin" min="{$product.minimal_quantity}"  max="{$product.available_quantity|default:$product.quantity_available|default:$product.stock_quantity|default:0|intval}" data-quantity-available="{$product.available_quantity|default:$product.quantity_available|default:$product.stock_quantity|default:0|intval}" data-max="{$product.available_quantity|default:$product.quantity_available|default:$product.stock_quantity|default:0|intval}" />
                    {/if}
                </div>
                 <div class="col-md-6 col-xs-12 text-xs-right tv-cart-delete-icon-wrapper">
                    <div class="cart-line-product-actions">
                        <a class="remove-from-cart" rel="nofollow" href="{$product.remove_from_cart_url}" data-link-action="delete-from-cart" data-id-product="{$product.id_product|escape:'javascript'}" data-id-product-attribute="{$product.id_product_attribute|escape:'javascript'}" data-id-customization="{$product.id_customization|escape:'javascript'}">
                            {if !isset($product.is_gift) || !$product.is_gift}
                            
                            {* --- POCZĄTEK MODYFIKACJI --- *}
                            <i class="material-icons">delete</i>
                            {* --- KONIEC MODYFIKACJI (usunięto klasę 'float-xs-left') --- *}
                            
                            {/if}
                        </a>
                         {block name='hook_cart_extra_product_actions'}
                        {hook h='displayCartExtraProductActions' product=$product}
                        {/block}
                    </div>
                </div>
             </div>
        </div>
        <div class="clearfix"></div>
    </div>
    {/strip}
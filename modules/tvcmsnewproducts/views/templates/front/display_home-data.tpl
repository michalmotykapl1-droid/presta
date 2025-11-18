{**
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
* versions in the future.
* If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
* @author PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}
{strip}
{if $main_heading['main_image_status']}
{$col = 'col-xl-10 col-lg-10 col-md-10 col-sm-12 col-xs-12 tvimage-true'}
{$image = true}
{if $main_heading['main_image_side'] == 'left'}
{$image_side = 'left'}
{else}
{$image_side = 'right'}
{/if}
{else}
{$col = ''}
{$image = ''}
{$image_side = ''}
{/if}
{if $dis_arr_result.status && $dis_arr_result.home_status && count($dis_arr_result.data.product_list) > 0}
<div class='tvnew-product-wrapper-box container'>
    <div class='tvnew-main-title-wrapper'>
        {include file='_partials/tvcms-main-title.tpl' main_heading=$main_heading path=$dis_arr_result['path']}
    </div>
    <div class='tvall-product-offer-banner'>
        {if $image == true && $image_side == 'left'}
     
         <div class="tvall-product-branner col-xl-2 col-lg-2 col-md-2 col-sm-12 col-xs-12">
            <div class="tvbanner-hover-wrapper tvall-block-box-shadows">
                <div class="tvbranner-hover-info-box"></div>
                <div class='tvbanner-hover'></div>
                <img src="{$dis_arr_result.path}{$main_heading.data.image}" alt="{l s='new Offer Banner' mod='tvcmsnewproducts'}"  width="{$main_heading.data.width}" height="{$main_heading.data.height}" class="tv-img-responsive" loading="lazy"/>
                
 <div class='tvbanner-hover1'></div>
            </div>
        </div>
        {/if}
        <div class="tvnew-product-content {$col}">
            <div class="tvall-block-box-shadows">
                <div class="tvnew-product">
                    <div class="products owl-theme owl-carousel tvnew-product-wrapper tvproduct-wrapper-content-box" data-has-image='{if $image == true}true{else}false{/if}'>
  
                         {foreach $dis_arr_result.data.product_list as $product}
                        {include file="catalog/_partials/miniatures/product.tpl" product=$product tv_product_type="new_product"}
                        {/foreach}
                    </div>
    
                 </div>
            </div>
        </div>
        {if $image == true && $image_side == 'right'}
        <div class="tvall-product-branner col-xl-2 col-lg-2 col-md-2 col-sm-12 col-xs-12">
            <div class="tvbanner-hover-wrapper tvall-block-box-shadows">
                <div class="tvbranner-hover-info-box"></div>
        
                 <div class='tvbanner-hover'></div>
                <img src="{$dis_arr_result.path}{$main_heading.data.image}" alt="{l s='new Offer Banner' mod='tvcmsnewproducts'}"  width="{$main_heading.data.width}" height="{$main_heading.data.height}" class="tv-img-responsive"loading="lazy" />
                <div class='tvbanner-hover1'></div>
            </div>
        </div>
        {/if}
        <div class='tvnew-pagination-wrapper tv-pagination-wrapper'>
         
             <div class="tvnew-pagination">
                <div class="tvcmsnew-pagination">
                    <div class="tvcmsnew-next-pre-btn tvcms-next-pre-btn">
                        <div class="tvcmsnew-prev tvcmsprev-btn" data-parent="tvcmsnew-product"><i class='material-icons'>&#xe314;</i></div>
                        <div class="tvcmsnew-next tvcmsnext-btn" data-parent="tvcmsnew-product"><i class='material-icons'>&#xe315;</i></div>
 
                     </div>
                </div>
            </div>
        </div>
        <div class="tvallproduct-link">
            <a class="all-product-link" href="{$dis_arr_result.link}">
                {l s='All New Products' mod='tvcmsnewproducts'} <i 
 class='material-icons'>&#xe315;</i>
            </a>
        </div>
    </div>
</div>
{/if}
{/strip}
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
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
* @author PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}
{strip}
{if $dis_arr_result['status']}
    <div class='tvcmscategory-slider container-fluid'>
        <div class='tvcategory-slider'> {* <-- TUTAJ NASTĄPIŁA POPRAWKA (USUNIĘTO .container) *}

            {if $main_heading['main_status']}
            <div class='tvcategory-slider-main-title-wrapper tvcmsmain-title-wrapper'>
                <div class="tvmain-title-wrapper">
                
                    <div class="tvmain-title tv-diet-heading-flex">
                        {* NOWA STRUKTURA: Elementy w jednej linii za pomocą Flexbox *}
                        <div class="tvcms-diet-heading-accent">MASZ DIETĘ ?</div>
                        <h2 class="tvcategory-slider-main-title tvcms-diet-main-title">
       
                             PRODUKTY DOPASOWANE DO TWOJEJ DIETY
                        </h2>
                    </div>
                </div>
            </div>
   
             {/if}

            <div class='tvcategory-slider-inner-info-box'>
                <div class='tvcategory-slider-content-box tv-grid-layout'>
                    {foreach $dis_arr_result['data'] as $data}
                        <div class='tvcategory-slider-wrapper-info tv-grid-item'>
           
                             <a href="{$link->getCategoryLink($data['id_category'])}" class="tvcategory-slider-title">
                                <div class="tvcategory-img-block">
                                    <img src="{$dis_arr_result['path']}{$data['image']}"
           
                                         alt="{$data['title']|escape:'htmlall':'UTF-8'}"
                                    class="tvcategory-img img-fluid d-block mx-auto"
                                    
                                    loading="lazy" />
                                </div>
                                <div class='tvcategory-slider-info-box'>{$data['title']}{* <div class="tvcategory-slider-short-desc">{$data['short_description']}</div> *}</div>
                            </a>
   
                         </div>
                    {/foreach}
                </div>
                </div>
        </div>
    </div>
{/if}
{/strip}
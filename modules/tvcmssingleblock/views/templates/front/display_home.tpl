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
* @author PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}
{strip}
{if $dis_arr_result.status}
<div class='container-fluid tvcmssingle-block' data-bg-url="{$dis_arr_result.path}{$dis_arr_result.data.image}">
    <div class='container tvsingle-block'>
        {* Możesz odkomentować poniższy blok, jeśli chcesz wyświetlać główny tytuł *}
        {* <div class='tvsingle-block-main-title-wrapper'>
            {include file='_partials/tvcms-main-title.tpl' main_heading=$main_heading path=$dis_arr_result['path']}
        </div> *}
        <a href="{$dis_arr_result.data.link}" class="tvsingle-block-image-info-wrapper row" title="{$dis_arr_result.data.title}">
            <div class="tv-single-block-image-wrapper-1 col-lg-8 col-md-8 col-sm-12 ">
                <div class="tvsingle-block-image-content-inner">
                    <div class="tv-single-block-mainimage">
                        <img src='{$dis_arr_result.path}{$dis_arr_result.data.image_2}' alt="{$dis_arr_result.data.title}" class="tv-img-responsive" loading="lazy"/>
                    </div>
                    <div class="tv-singleblock-content-one">
                        <div class="tv-single-block-image">
                            <img src='{$dis_arr_result.path}{$dis_arr_result.data.image_4}' alt="{$dis_arr_result.data.title}" class="tv-img-responsive" loading="lazy"/>
                        </div>
                        <div class="tv-single-block-content">
                            <span class="tvsingle-block-sub-description">
                                {$dis_arr_result.data.sub_description}
                            </span>
                            <span class="tvsingle-block-title">
                                {$dis_arr_result.data.title}
                            </span>
                            <div class="tvsingle-block-desc">
                                {$dis_arr_result.data.description}
                            </div>
                        </div>
                        {* Możesz odkomentować poniższy blok, jeśli chcesz wyświetlać przycisk "Dowiedz się więcej" lub podobny *}
                        {* <div class="tvsingle-bolck-btn-link-wrapper">
                            <a href="{$dis_arr_result.data.link}" class="tvsingle-bolck-btn-link tvsingle-bolck-btn">{$dis_arr_result.data.btn_caption}</a>
                        </div> *}
                    </div>
                </div>
            </div>
            <div class='tvsingle-block-info-box col-lg-4 col-md-4 col-sm-12'>
                <div class="tvsingle-block-image-two">
                    <img src='{$dis_arr_result.path}{$dis_arr_result.data.image_3}' alt="{$dis_arr_result.data.title}" class="tv-img-responsive" loading="lazy"/>
                </div>
                <div class="tv-singleblock-content-two">
                    <div class="tvsingle-block-btn-link-wrapper">
                        <a href="{$dis_arr_result.data.link}" class="tvsingle-bolck-btn-link tvsingle-bolck-btn">{$dis_arr_result.data.btn_caption}</a>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>
{/if}
{/strip}
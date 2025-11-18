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
    {if $dis_arr_result.status}
    <div class="tvcmscustomer-services container-fluid">
        <div class="tvcustomer-services container">
            <div class="tvservice-inner">
                {include file='_partials/tvcms-main-title.tpl' main_heading=$main_heading path=$dis_arr_result['path']}
                <div class="tvservice-all-block-wrapper">
                    <div class="tvservices-all-block">
                        <div class="tv-all-service wrapper card-deck">
                            {if $dis_arr_result.data.service_1.status}
                            <div class="tvservices-center card odd tvservice-payment">
                                <div class="tvall-block-box-shadows">
                                    <div class="tvservices-1 tvall-services-block">
                                        <div class="tvservices-wrapper">
                                            <div class="tvservices-img-conut">
                                                <div class='tvservices-img'><img src="{$dis_arr_result.path}{$dis_arr_result.data.service_1.image}" class="tv-img-responsive" width="50" height="50" alt="{$dis_arr_result.data.service_1.title}" loading="lazy" />
                                                </div>{* <div class='tvservices-count'>
                                                    {l s='01' mod='tvcmscustomerservices'}
                                                </div> *}
                                            </div>
                                            <div class='tvservices-content-box tvservices-info'>
                                                <div class="tvservices-title">{$dis_arr_result.data.service_1.title}</div>
                                                <div class="tvservice-dec">{$dis_arr_result.data.service_1.desc}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {/if}
                            {if $dis_arr_result.data.service_2.status}
                            <div class="tvservices-center card even tvservice-cash-trustpay">
                                <div class="tvall-block-box-shadows">
                                    <div class="tvservices-2 tvall-services-block">
                                        <div class="tvservices-wrapper">
                                            <div class="tvservices-img-conut">
                                                <div class='tvservices-img'><img src="{$dis_arr_result.path}{$dis_arr_result.data.service_2.image}" class="tv-img-responsive" width="50" height="50" alt="{$dis_arr_result.data.service_2.title}" loading="lazy" />
                                                </div>{*
                                                <div class='tvservices-count'>
                                                    {l s='02' mod='tvcmscustomerservices'}
                                                </div>
                                                *}
                                            </div>
                                            <div class='tvservices-content-box tvservices-info'>
                                                <div class="tvservices-title">{$dis_arr_result.data.service_2.title}</div>
                                                <div class="tvservice-dec">{$dis_arr_result.data.service_2.desc}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {/if}
                            {if $dis_arr_result.data.service_3.status}
                            <div class="tvservices-center card odd tvservice-supprt">
                                <div class="tvall-block-box-shadows">
                                    <div class="tvservices-3 tvall-services-block">
                                        <div class="tvservices-wrapper">
                                            <div class="tvservices-img-conut">
                                                <div class='tvservices-img'><img src="{$dis_arr_result.path}{$dis_arr_result.data.service_3.image}" width="50" height="50" class="tv-img-responsive" alt="{$dis_arr_result.data.service_3.title}" loading="lazy" />
                                                </div>{*
                                                <div class='tvservices-count'>
                                                    {l s='03' mod='tvcmscustomerservices'}
                                                </div> *}
                                            </div>
                                            <div class='tvservices-content-box tvservices-info'>
                                                <div class="tvservices-title">{$dis_arr_result.data.service_3.title}</div>
                                                <div class="tvservice-dec">{$dis_arr_result.data.service_3.desc}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {/if}
                            {if $dis_arr_result.data.service_4.status}
                            <div class="tvservices-center card even tvservice-shopon">
                                <div class="tvall-block-box-shadows">
                                    <div class="tvservices-4 tvall-services-block">
                                        <div class="tvservices-wrapper">
                                            <div class="tvservices-img-conut">
                                                <div class='tvservices-img'>
                                                    <img src="{$dis_arr_result.path}{$dis_arr_result.data.service_4.image}" class="tv-img-responsive" width="50" height="50" alt="{$dis_arr_result.data.service_4.title}" loading="lazy" />
                                                </div>{* <div class='tvservices-count'>
                                                    {l s='04' mod='tvcmscustomerservices'}
                                                </div>
                                                *}
                                            </div>
                                            <div class='tvservices-content-box tvservices-info'>
                                                <div class="tvservices-title">{$dis_arr_result.data.service_4.title}</div>
                                                <div class="tvservice-dec">{$dis_arr_result.data.service_4.desc}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            {/if}
                        </div>
                    </div>
                </div>
            </div>{* <div class='tvcms-service-pagination-wrapper'>
                <div class="tvcms-service-pagination">
                    <div class="tvcms-service-next-pre-btn">
                        <div class="tvservice-slider-prev tvcmsprev-btn"><i class='material-icons'>&#xe314;</i></div>
                        <div class="tvservice-slider-next tvcmsnext-btn"><i class='material-icons'>&#xe315;</i></div>
                    </div>
                </div>
            </div> *}
        </div>
    </div>
    {/if}
    {/strip}
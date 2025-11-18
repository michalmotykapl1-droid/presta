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
    {$col = ''}
    {if !empty($offer_banner)}
        {$col = 'col-md-10 col-lg-10'}
    {/if}
    {if !empty($data)}
        <div class="tvcms-slider-offerbanner-wrapper container-fluid">
            <div class="container">
                <div class="row">
                    <div class="{$col} tvcmsmain-slider-wrapper" data-speed='{$main_slider_js.speed}' data-pause-hover='{$main_slider_js.pause}'>
                        <div class='tvcms-main-slider'>
                            <div class='tv-main-slider'>
                                <div id='tvmain-slider' class="owl-theme owl-carousel tvcms-hide-owl" {if !empty($data[0]['width']) && !empty($data[0]['height'])} style="aspect-ratio: {$data[0]['width']/$data[0]['height']}; height:{$data[0]['height']};"{/if}>
                                    {$i = 0}
                                    {assign var="dataBottom" value=""}
                                    {foreach $data as $slide}
                                        {if $slide["ivr_value"] == "image_upload"}
                                            {if !empty($slide['url']) && $slide["ivr_value"] == "image_upload"}
                                            <a href="{$slide['url']}" class="tvimage tvslider-list" title="{$slide['title']}">
                                            {/if}
                                            <picture>
                                                {* medium *}
                                                <source srcset='{$slide["med_image_url"]}' media="(max-width: 768px) and (min-width: 576px)">
                                                {* small *}
                                                <source srcset='{$slide["sml_image_url"]}' media="(max-width: 575px)">
                                                {* Default large - ZMIANA PONIŻEJ *}
                                                <img class="tvmain-slider-img img-fluid w-100 d-block" src='{$slide["image_url"]}' alt='{l s="Main Slider" mod="tvcmsslider"}' loading="lazy" style="object-fit: contain; height: auto;" />
                                            </picture>
                                            {if !empty($slide['url']) && $slide["ivr_value"]=="image_upload" }
                                            </a>
                                            {/if}
                                        {elseif $slide["ivr_value"]=="video_upload" }
                                        <div class="tv-video tvslider-list">
                                            <a href="{$slide['url']}" title="{$slide['title']}">
                                                <button class="tvslider-video-link active">
                                                    <i class="material-icons">&#xe89e;</i>
                                                </button>
                                            </a>
                                            <button class="tvslider-video-play active">
                                                <i class="material-icons">play_arrow</i>
                                            </button>
                                            <button class="tvslider-video-mute active">
                                                <i class="material-icons">volume_up</i>
                                            </button>
                                            <video class="tvslider-video{if {!$slide['video_width']} && {!$slide['video_height']}} tvcms-test{/if}" loop="loop" {if {$slide['video_width']} && {$slide['video_height']}} width="{$slide['video_width']}" height="{$slide['video_height']}" {/if} preload="metadata">
                                                <source type="video/mp4" data-src='{$slide["image_url"]}'>
                                            </video>
                                        </div>
                                        {/if}
                                        {if $slide['image_url'] || (!$slide['class_name'] == 'tvmain-slider-contant-none')}
                                            {if $slide['title'] || $slide['description']}
                                                {assign var="dataBottom" value="{$dataBottom}<div id=\"tvmain-slider-img-{$i}\" class=\"tvmain-slider-content-inner {if $i==0}active{/if}\" data-index=\"{$i}\">
                                                    <div class=\"tvmain-slider-contant {$slide['class_name']}\">
                                                        <h2 class=\"tvmain-slider-title animated\">{$slide['title']}</h2>
                                                        <div class=\"tvmain-slider-info animated\">{$slide['description'] nofilter}</div>
                                                    </div>
                                                </div>"}
                                            {/if}
                                        {/if}
                                        {$i=$i + 1}
                                    {/foreach}
                                </div>
                                <div class="tvmain-slider-next-pre-btn">
                                    <div class="tvcmsmain-prev tvcmsprev-btn">
                                        <i class='material-icons'>&#xe5cb;</i>
                                    </div>
                                    <div class="tvcmsmain-next tvcmsnext-btn">
                                        <i class='material-icons'>&#xe5cc;</i>
                                    </div>
                                </div>
                            </div>
                            <div class="tvmain-slider-content-wrapper">
                                {$dataBottom nofilter}
                            </div>
                        </div>
                    </div>
                    {$offer_banner nofilter}
                </div>
            </div>
        </div>
    {/if}
{/strip}
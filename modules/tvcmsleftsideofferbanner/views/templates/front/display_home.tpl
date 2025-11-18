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
* @author     PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}
{strip}

{if $AllPageShow == 1}
{if !empty({$data['TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME']})}
<div class="tvcmsleftsideofferbanners-one">
    <div class="tvbanner-wrapper tvone-banner-wrapper-info tvbanner1">
        <a href="{$data['TVCMSLEFTSIDEOFFERBANNER_LINK']}" class="tvbanner-hover-wrapper" title="{$data['TVCMSLEFTSIDEOFFERBANNER_CAPTION'][$language_id]}">
            <img src="{$path}{$data['TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME']}"
                 class="tvofferbanner-img img-fluid w-100 d-block"
                 alt="{if !empty($data['TVCMSLEFTSIDEOFFERBANNER_CAPTION'][$language_id])}{$data['TVCMSLEFTSIDEOFFERBANNER_CAPTION'][$language_id]}{else}{l s='Slider Offer Banner' mod='tvcmsleftsideofferbanner'}{/if}"
                 loading="lazy"
                 style="object-fit: contain; height: auto;" />
        </a>
        {if !empty($data['TVCMSLEFTSIDEOFFERBANNER_CAPTION'][$language_id]) && $data['TVCMSLEFTSIDEOFFERBANNER_CAPTION_SIDE'] != 'none'}
        <div class="tvcmsleftsideofferbanners-content {$data['TVCMSLEFTSIDEOFFERBANNER_CAPTION_SIDE']}">
            {$data['TVCMSLEFTSIDEOFFERBANNER_CAPTION'][$language_id]}
        </div>
        {/if}
    </div>
</div>
{/if}
{else if  $AllPageShow == 0 && $page.page_name == 'index'}
{if !empty({$data['TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME']})}
    <div class="tvcmsleftsideofferbanners-one">
    <div class="tvbanner-wrapper tvone-banner-wrapper-info tvbanner1">
        <a href="{$data['TVCMSLEFTSIDEOFFERBANNER_LINK']}" class="tvbanner-hover-wrapper" title="{$data['TVCMSLEFTSIDEOFFERBANNER_CAPTION'][$language_id]}">
            <img src="{$path}{$data['TVCMSLEFTSIDEOFFERBANNER_IMAGE_NAME']}"
                 class="tvofferbanner-img img-fluid w-100 d-block"
                 alt="{if !empty($data['TVCMSLEFTSIDEOFFERBANNER_CAPTION'][$language_id])}{$data['TVCMSLEFTSIDEOFFERBANNER_CAPTION'][$language_id]}{else}{l s='Slider Offer Banner' mod='tvcmsleftsideofferbanner'}{/if}"
                 loading="lazy"
                 style="object-fit: contain; height: auto;" />
        </a>
        {if !empty($data['TVCMSLEFTSIDEOFFERBANNER_CAPTION'][$language_id]) && $data['TVCMSLEFTSIDEOFFERBANNER_CAPTION_SIDE'] != 'none'}
        <div class="tvcmsleftsideofferbanners-content {$data['TVCMSLEFTSIDEOFFERBANNER_CAPTION_SIDE']}">
            {$data['TVCMSLEFTSIDEOFFERBANNER_CAPTION'][$language_id]}
        </div>
        {/if}
    </div>
</div>
{/if}
{/if}
{/strip}
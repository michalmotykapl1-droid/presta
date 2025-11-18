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
* @author    PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}
{strip}
{if !empty({$data['TVCMSOFFERBANNER_IMAGE_NAME']})}
<div class="tvcmsofferbanners-one container-fluid">
	<div class=""> {* <-- POPRAWKA: Usunięto klasę .container z tej linii *}
        <a href="{$data['TVCMSOFFERBANNER_LINK']}" class="tvbanner-hover-wrapper" {* title="{$data['TVCMSOFFERBANNER_CAPTION'][$language_id]}" *}>
                <img src="{$path}{$data['TVCMSOFFERBANNER_IMAGE_NAME']}" class="tvimage-lazy img-responsive tv-img-responsive" alt="{l s='Offer Banner' mod='tvcmsofferbanner'}" height="{$data['TVCMSOFFERBANNER_IMAGE_HEIGHT']}" width="{$data['TVCMSOFFERBANNER_IMAGE_WIDTH']}" loading="lazy"/>
                {if !empty($data['TVCMSOFFERBANNER_CAPTION'][$language_id]) && $data['TVCMSOFFERBANNER_CAPTION_SIDE'] != 'none'}
                     <div class="{$data['TVCMSOFFERBANNER_CAPTION_SIDE']} tvofferbanner-content">
                        {$data['TVCMSOFFERBANNER_CAPTION'][$language_id] nofilter}
                    </div>
                {/if}
        </a>
    </div>
</div>  
{/if}
{/strip}
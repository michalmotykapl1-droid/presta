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
* @author     PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
* International Registered Trademark & Property of PrestaShop SA
*}
{strip}
{assign var='num_of_rec' value='1'}
{assign var='TVCMSMULTIBANNER1_IMAGE_NAME' value='TVCMSMULTIBANNER1_IMAGE_NAME_'|cat:$num_of_rec}
{assign var='num_of_rec' value='2'}
{assign var='TVCMSMULTIBANNER2_IMAGE_NAME' value='TVCMSMULTIBANNER1_IMAGE_NAME_'|cat:$num_of_rec}
{assign var='num_of_rec' value='3'}
{assign var='TVCMSMULTIBANNER3_IMAGE_NAME' value='TVCMSMULTIBANNER1_IMAGE_NAME_'|cat:$num_of_rec}
{if !empty($data[$TVCMSMULTIBANNER1_IMAGE_NAME][$language_id]) ||
!empty($data[$TVCMSMULTIBANNER2_IMAGE_NAME][$language_id]) || !empty($data[$TVCMSMULTIBANNER3_IMAGE_NAME][$language_id])}
    <div class="tvcmsmultibanners container-fluid">
        <div class=""> {* <-- POPRAWKA: Usunięto klasę .container z tej linii *}
            <div class="tvmultibanner">
            {assign var='num_of_rec' value='1'}
            {assign var='TVCMSMULTIBANNER1_IMAGE_NAME' value='TVCMSMULTIBANNER1_IMAGE_NAME_'|cat:$num_of_rec}
            {assign var='TVCMSMULTIBANNER1_CAPTION' value='TVCMSMULTIBANNER1_CAPTION_'|cat:$num_of_rec}
            {assign var='TVCMSMULTIBANNER1_IMAGE_WIDTH' value='TVCMSMULTIBANNER1_IMAGE_WIDTH_'|cat:$num_of_rec}
            {assign var='TVCMSMULTIBANNER1_IMAGE_HEIGHT' 
 value='TVCMSMULTIBANNER1_IMAGE_HEIGHT_'|cat:$num_of_rec}
            {assign var='TVCMSMULTIBANNER1_CAPTION_SIDE' value='TVCMSMULTIBANNER1_CAPTION_SIDE_'|cat:$num_of_rec}
            {assign var='TVCMSMULTIBANNER1_LINK' value='TVCMSMULTIBANNER1_LINK_'|cat:$num_of_rec}
            {if $data[$TVCMSMULTIBANNER1_IMAGE_NAME]}
            <div class="tvmultibanner1-wrapper col-sm-4 tvmultibanner-{$num_of_rec|escape:'htmlall':'UTF-8'}">
                <a class="tvbanner-hover-wrapper" href="{$data[$TVCMSMULTIBANNER1_LINK][$language_id]|escape:'htmlall':'UTF-8'}" title="{l s='Multibanner' mod='tvcmsmultibanner1'}{$num_of_rec|escape:'htmlall':'UTF-8'}">
                    <img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/{$data[$TVCMSMULTIBANNER1_IMAGE_NAME][$language_id]|escape:'htmlall':'UTF-8'}"
  
                         class="tvmultibanner-img img-fluid w-100 d-block"
                         alt=""
                         loading="lazy"
                        
                          style="object-fit: contain; height: auto;" />
                </a>
                {if $data[$TVCMSMULTIBANNER1_CAPTION_SIDE][$language_id] != 'none'}
                <div class="{$data[$TVCMSMULTIBANNER1_CAPTION_SIDE][$language_id]|escape:'htmlall':'UTF-8'} tvmultibanner-content">
                    {$data[$TVCMSMULTIBANNER1_CAPTION][$language_id] nofilter}
                </div>
     
             {/if}
            </div>
            {/if}

            {assign var='num_of_rec' value='2'}
            {assign var='TVCMSMULTIBANNER1_IMAGE_NAME' value='TVCMSMULTIBANNER1_IMAGE_NAME_'|cat:$num_of_rec}
            {assign var='TVCMSMULTIBANNER1_CAPTION' value='TVCMSMULTIBANNER1_CAPTION_'|cat:$num_of_rec}
            {assign var='TVCMSMULTIBANNER1_IMAGE_WIDTH' value='TVCMSMULTIBANNER1_IMAGE_WIDTH_'|cat:$num_of_rec}
         
             {assign var='TVCMSMULTIBANNER1_IMAGE_HEIGHT' value='TVCMSMULTIBANNER1_IMAGE_HEIGHT_'|cat:$num_of_rec}
            {assign var='TVCMSMULTIBANNER1_CAPTION_SIDE' value='TVCMSMULTIBANNER1_CAPTION_SIDE_'|cat:$num_of_rec}
            {assign var='TVCMSMULTIBANNER1_LINK' value='TVCMSMULTIBANNER1_LINK_'|cat:$num_of_rec}
            {if $data[$TVCMSMULTIBANNER1_IMAGE_NAME]}
            <div class="tvmultibanner2-wrapper col-sm-4 tvmultibanner-{$num_of_rec|escape:'htmlall':'UTF-8'}">
                <a class='tvbanner-hover-wrapper' href="{$data[$TVCMSMULTIBANNER1_LINK][$language_id]|escape:'htmlall':'UTF-8'}" title="{l s='Multibanner' mod='tvcmsmultibanner1'}{$num_of_rec|escape:'htmlall':'UTF-8'}">
                  
                     <img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/{$data[$TVCMSMULTIBANNER1_IMAGE_NAME][$language_id]|escape:'htmlall':'UTF-8'}"
                         class="tvmultibanner-img img-fluid w-100 d-block"
                         alt=""
                         loading="lazy"
                   
                         style="object-fit: contain; height: auto;" />
                </a>
                {if $data[$TVCMSMULTIBANNER1_CAPTION_SIDE][$language_id] != 'none'}
                <div class="{$data[$TVCMSMULTIBANNER1_CAPTION_SIDE][$language_id]|escape:'htmlall':'UTF-8'} tvmultibanner-content">
                    {$data[$TVCMSMULTIBANNER1_CAPTION][$language_id] nofilter}
                
                 </div>
                {/if}
            </div>
            {/if}

            {assign var='num_of_rec' value='3'}
            {assign var='TVCMSMULTIBANNER1_IMAGE_NAME' value='TVCMSMULTIBANNER1_IMAGE_NAME_'|cat:$num_of_rec}
            {assign var='TVCMSMULTIBANNER1_CAPTION' value='TVCMSMULTIBANNER1_CAPTION_'|cat:$num_of_rec}
            {assign var='TVCMSMULTIBANNER1_IMAGE_WIDTH' value='TVCMSMULTIBANNER1_IMAGE_WIDTH_'|cat:$num_of_rec}
    
                 {assign var='TVCMSMULTIBANNER1_IMAGE_HEIGHT' value='TVCMSMULTIBANNER1_IMAGE_HEIGHT_'|cat:$num_of_rec}
            {assign var='TVCMSMULTIBANNER1_CAPTION_SIDE' value='TVCMSMULTIBANNER1_CAPTION_SIDE_'|cat:$num_of_rec}
            {assign var='TVCMSMULTIBANNER1_LINK' value='TVCMSMULTIBANNER1_LINK_'|cat:$num_of_rec}
            {if $data[$TVCMSMULTIBANNER1_IMAGE_NAME]}
            <div class="tvmultibanner3-wrapper col-sm-4 tvmultibanner-{$num_of_rec|escape:'htmlall':'UTF-8'}">
                <a class="tvbanner-hover-wrapper" href="{$data[$TVCMSMULTIBANNER1_LINK][$language_id]|escape:'htmlall':'UTF-8'}" title="{l s='Multibanner' mod='tvcmsmultibanner1'}{$num_of_rec|escape:'htmlall':'UTF-8'}">
             
                     <img src="{$module_dir|escape:'htmlall':'UTF-8'}views/img/{$data[$TVCMSMULTIBANNER1_IMAGE_NAME][$language_id]|escape:'htmlall':'UTF-8'}"
                         class="tvmultibanner-img img-fluid w-100 d-block"
                         alt=""
                         loading="lazy"
              
                           style="object-fit: contain;
 height: auto;" />
                </a>
                {if $data[$TVCMSMULTIBANNER1_CAPTION_SIDE][$language_id] != 'none'}
                <div class="{$data[$TVCMSMULTIBANNER1_CAPTION_SIDE][$language_id]|escape:'htmlall':'UTF-8'} tvmultibanner-content">
                    {$data[$TVCMSMULTIBANNER1_CAPTION][$language_id] nofilter}
                </div>
        
                 {/if}
            </div>
            {/if}
            </div>
        </div>
    </div>
{/if}
{/strip}
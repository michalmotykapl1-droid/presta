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
*  @author PrestaShop SA <contact@prestashop.com>
*  @copyright  2007-2025 PrestaShop SA
*  @license    http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}
{strip}
<div id="tvcmssearch-mobile" class="search-widget tvcmsheader-search" data-search-controller-url="{$search_controller_url|escape:'htmlall':'UTF-8'}" data-ajax-url="{$link->getModuleLink('tvcmssearch','ajax')|escape:'htmlall':'UTF-8'}">
	<div class="tvsearch-top-wrapper">
		{* <div class="tvheader-sarch-display">
			<div class="tvheader-search-display-icon">
				<div class="tvsearch-open"></div>
			</div>
		</div> *}
		<div class="tvsearch-header-display-wrappper">
			<div class="tvsearch-close hidden-md-up">
				<i class='material-icons'>&#xe5cd;</i>
			</div>
			<form method="get" action="{$search_controller_url|escape:'htmlall':'UTF-8'}">
				<input type="hidden" name="controller" value="search" />
				<select class="tvcms-select-category">
		            <option value="0">{l s='All' mod='tvcmssearch'}</option>
		            {foreach $options as $option}
		            	<option value="{$option['id_category']|escape:'htmlall':'UTF-8'}">{$option['name'] nofilter}</option>
		            {/foreach}
		        </select>
				<div class="tvheader-top-search">
					<div class="tvheader-top-search-wrapper-info-box">
						<input type="text" name="s" class='tvcmssearch-words' {* value="{$search_string|escape:'htmlall':'UTF-8'}" *} placeholder="{l s='Search our catalog' mod='tvcmssearch'}" aria-label="{l s='Search' mod='tvcmssearch'}" autocomplete="off"/>
					</div>
					<div class='tvsearch-result'></div>
				</div>
				<div class="tvheader-top-search-wrapper">
					<button type="submit">
						<i class='material-icons'>&#xe8b6;</i>
			      		<span class="tvserach-name">{l s='Search' mod='tvcmssearch'}</span>
					</button>
				</div>
			</form>
		</div>
	</div>
</div>
{/strip}
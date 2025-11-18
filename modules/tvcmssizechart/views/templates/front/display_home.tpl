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

<div class="tvproduct-size-custom">
	{if $TVCMSSIZECHART_TEXT_STATUS_1}
		<button type="button" id="tvcustomizebtn" class="btn btn-unstyle tvcustomize-btn {* fancybox fancybox.iframe *}" data-target="#tvproduct-customize" data-toggle="modal">
			<i class="tvcustom-icons"></i>
			<span>{* {$TVCMSSIZECHART_TEXT_1_NAME} *} {l s='Size Guide' mod='tvcmssizechart'}</span> 
		</button>
	{/if}
	<a class="open-comment-form btn btn-unstyle" href="#new_comment_form">
		<i class="tvcustom-icons"></i>
		<span>{l s='Write your review' mod='tvcmssizechart'}</span>
	</a>
	<div id="tvcmssizechart-popup" class="cms-description">{$TVCMSSIZECHART_TEXT_1 nofilter}</div>
</div>
{if $TVCMSSIZECHART_TEXT_STATUS_1}
<div class="modal fade" id="tvproduct-customize" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
    	<div class="modal-content">
	    	<div class="modal-body cms-description">
	    		<div id="tvcmssizechart-popup" class="cms-description">{$TVCMSSIZECHART_TEXT_1 nofilter}</div>
		    	<button type="button" class="tvmodel-close close" data-dismiss="modal" aria-label="Close">
		          <span aria-hidden="true"><i class='material-icons'>&#xe5cd;</i></span>
		        </button>
		    </div>
	    </div>
    </div>
</div>
{/if}
{/strip}


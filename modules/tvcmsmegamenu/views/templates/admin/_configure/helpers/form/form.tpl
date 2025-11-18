{**
* 2007-2025 PrestaShop — ThemeVolty tvcmsmegamenu
* Nadpisanie helpers/form aby ukrywać sekcje + doładować JS/CSS w BO
*}

{extends file="helpers/form/form.tpl"}

{block name="field"}
	{if $input.type == 'select_link'}
		<select class="form-control fixed-width-xxl ps_link" name="ps_link" id="ps_link">
			{$all_options|escape:'quotes':'UTF-8'}
		</select>
		<script type="text/javascript">
			var type_link = {$type_link|intval};
			{if $type_link == 1}
			$(document).ready(function() {
				$("#ps_link").val('{if isset($ps_link_value) &&  $ps_link_value != ''}{$ps_link_value|escape:"html":"UTF-8"}{/if}');
			});
			{else}
				$('.ps_link').parent('.form-group').css('display','none');
			{/if}
		</script>
	{/if}

	{if $input.name == 'title'}
		<div class="tv-menu-title">{$smarty.block.parent}</div>
		<script type="text/javascript">
		var type_link = {$type_link|intval};
		if (type_link == 1 || type_link == 4)
			$('.tv-menu-title').parent('.form-group').css('display','none');
		</script>
	{elseif $input.name == 'link'}
		<div class="tv-menu-link">{$smarty.block.parent}</div>
		<script type="text/javascript">
		var type_link = {$type_link|intval};
		if (type_link == 1 || type_link == 4)
			$('.tv-menu-link').parent('.form-group').css('display','none');
		</script>
	{elseif $input.name == 'text'}
		<div class="tv-menu-text">{$smarty.block.parent}</div>
		<script type="text/javascript">
		var type_link = {$type_link|intval};
		if (type_link == 1 || type_link == 2 || type_link == 4)
			$('.tv-menu-text').parent('.form-group').css('display','none');
		</script>
	{elseif $input.name == 'id_product'}
		<div class="tv-menu-product">{$smarty.block.parent}</div>
		<script type="text/javascript">
		var type_link = {$type_link|intval};
		if (type_link == 1 || type_link == 2 || type_link == 3)
			$('.tv-menu-product').parent('.form-group').css('display','none');
		</script>
	{else}
		{$smarty.block.parent}
	{/if}
{/block}

{block name="footer"}
  {$smarty.block.parent}
  {assign var=modurl value=$smarty.const._MODULE_DIR_|cat:"tvcmsmegamenu/"}
  <link rel="stylesheet" href="{$modurl}views/css/bo_dynproducts.css">
  <script src="{$modurl}views/js/bo_dynproducts.js"></script>
{/block}

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
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2025 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
<div class="panel">
	<div class="panel-heading">
		<i class="icon-filter"></i> {l s='Filtry narzutu (dla widocznej strony)' mod='x13allegro'}
	</div>
	<div class="panel-body">
		<button id="margin-filter-all" class="btn btn-default btn-sm">{l s='Pokaż wszystkie' mod='x13allegro'}</button>
		<button id="margin-filter-red" class="btn btn-danger btn-sm">{l s='Pokaż czerwone (< 20%)' mod='x13allegro'}</button>
		<button id="margin-filter-yellow" class="btn btn-warning btn-sm">{l s='Pokaż żółte (20-35%)' mod='x13allegro'}</button>
		<button id="margin-filter-green" class="btn btn-success btn-sm">{l s='Pokaż zielone (> 35%)' mod='x13allegro'}</button>
	</div>
</div>

<script type="text/javascript">
	$(document).ready(function() {
		function filterMargin(color) {
			$('#table-xallegro_auction tbody tr').each(function() {
				var $row = $(this);
				var marginDiv = $row.find('div[data-margin-color]');
				
				if (color === 'all') {
					$row.show();
				} else if (marginDiv.length && marginDiv.data('margin-color') === color) {
					$row.show();
				} else {
					$row.hide();
				}
			});
		}

		$('#margin-filter-all').click(function() { filterMargin('all'); });
		$('#margin-filter-red').click(function() { filterMargin('red'); });
		$('#margin-filter-yellow').click(function() { filterMargin('yellow'); });
		$('#margin-filter-green').click(function() { filterMargin('green'); });
	});
</script>
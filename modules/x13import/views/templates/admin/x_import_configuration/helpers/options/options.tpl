{extends file="helpers/options/options.tpl"}

{block name="leadin"}
    {if $ionCubeLicenseInfo !== false}
        <div class="panel">
            <div class="panel-heading">
                <i class="icon-cogs"></i> {l s='Licencja' mod='x13import'}
            </div>

            <div class="form-wrapper clearfix">
                <div class="form-group">
                    <div class="col-lg-9 col-lg-offset-3">
                        {$ionCubeLicenseInfo.html_content}
                    </div>
                </div>
            </div>
        </div>
    {/if}
{/block}

{block name="input"}
    {if $field.type == 'select'}
        <div class="col-lg-9">
            {if $field['list']}
                <select class="form-control fixed-width-xxl {if isset($field['class'])}{$field['class']}{/if}" name="{$key}"{if isset($field['js'])} onchange="{$field['js']}"{/if} id="{$key}" {if isset($field['size'])} size="{$field['size']}"{/if} {if isset($field['disabled']) && $field['disabled']} disabled="disabled"{/if}>
                    {foreach $field['list'] AS $k => $option}
                        <option value="{$option[$field['identifier']]}"{if $field['value'] == $option[$field['identifier']]} selected="selected"{/if}>{$option['name']}</option>
                    {/foreach}
                </select>
            {elseif isset($input.empty_message)}
                {$input.empty_message}
            {/if}
        </div>
        {if isset($field['desc']) && !empty($field['desc'])}
            <div class="col-lg-9 col-lg-offset-3">
                <div class="help-block">
                    {if is_array($field['desc'])}
                        {foreach $field['desc'] as $p}
                            {if is_array($p)}
                                <span id="{$p.id}">{$p.text}</span><br />
                            {else}
                                {$p}<br />
                            {/if}
                        {/foreach}
                    {else}
                        {$field['desc']}
                    {/if}
                </div>
            </div>
        {/if}
	{elseif $field.type == 'disabled'}
        {if isset($is_bootstrap) && $is_bootstrap}<div class="col-lg-9">{/if}
		<input type="text" value="{$field.disabled|date_format:"%H:%M:%S  %d-%m-%Y"}" readonly="readonly" disabled="disabled" size="{if isset($field.size)}{$field.size|intval}{else}5{/if}" />
		{if isset($is_bootstrap) && $is_bootstrap}</div>
			{if isset($field['desc']) && !empty($field['desc'])}
			<div class="col-lg-9 col-lg-offset-3">
				<div class="help-block">
					{if is_array($field['desc'])}
						{foreach $field['desc'] as $p}
							{if is_array($p)}
								<span id="{$p.id}">{$p.text}</span><br />
							{else}
								{$p}<br />
							{/if}
						{/foreach}
					{else}
						{$field['desc']}
					{/if}
				</div>
			</div>
			{/if}
		{/if}
    {elseif $field.type == 'readonly'}
        {if isset($is_bootstrap) && $is_bootstrap}<div class="col-lg-9">{/if}
        <input type="text" value="{$field.readonly}" readonly="readonly" size="{if isset($field.size)}{$field.size|intval}{else}5{/if}" />
		{if isset($is_bootstrap) && $is_bootstrap}</div>
			{if isset($field['desc']) && !empty($field['desc'])}
			<div class="col-lg-9 col-lg-offset-3">
				<div class="help-block">
					{if is_array($field['desc'])}
						{foreach $field['desc'] as $p}
							{if is_array($p)}
								<span id="{$p.id}">{$p.text}</span><br />
							{else}
								{$p}<br />
							{/if}
						{/foreach}
					{else}
						{$field['desc']}
					{/if}
				</div>
			</div>
			{/if}
		{/if}
    {elseif $field['type'] == 'hr'}
        <hr />
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{block name="after"}
    <script>
        $(document).on('change', 'input[name="IMPORT_UNAVAILABLE"]', function () {
            var optionsList = [
                'MOD_ADVANCED_STOCK_NEW'
            ];
            var selected = (parseInt($('input[name="IMPORT_UNAVAILABLE"]:checked').val()) === 1);

            optionsList.forEach(function (value) {
                if (selected) {
                    $('select[name="' + value + '"], input[name="' + value + '"]').removeAttr('disabled').trigger('xchange');
                } else {
                    $('select[name="' + value + '"], input[name="' + value + '"]').attr('disabled', 'disabled').trigger('xchange');
                }
            });
        });

        $(document).on('change', 'input[name="MOD_ADVANCED_STOCK_NEW"]', function () {
            var optionsList = [
                'MOD_ADVANCED_STOCK_NEW_ACTIVE',
                'MOD_ADVANCED_STOCK_NEW_VIS',
                'MOD_ADVANCED_STOCK_NEW_OOS',
                'MOD_ADVANCED_STOCK_NEW_AFO'
            ];
            var selectedParent = (parseInt($('input[name="IMPORT_UNAVAILABLE"]:checked').val()) === 1);
            var selected = (parseInt($('input[name="MOD_ADVANCED_STOCK_NEW"]:checked').val()) === 1);

            optionsList.forEach(function (value) {
                if (!selected || !selectedParent) {
                    $('select[name="' + value + '"], input[name="' + value + '"]').attr('disabled', 'disabled');
                } else if (selected) {
                    $('select[name="' + value + '"], input[name="' + value + '"]').removeAttr('disabled');
                }
            });
        });

        $(document).on('change', 'input[name="MOD_ADVANCED_STOCK_UPD"]', function () {
            var optionsList = [
                'MOD_ADVANCED_STOCK_UPD_ACTIVE',
                'MOD_ADVANCED_STOCK_UPD_VIS',
                'MOD_ADVANCED_STOCK_UPD_OOS',
                'MOD_ADVANCED_STOCK_UPD_AFO',
                'MOD_ADVANCED_STOCK_UPD_QTY_ZERO'
            ];
            var selected = (parseInt($('input[name="MOD_ADVANCED_STOCK_UPD"]:checked').val()) === 1);

            optionsList.forEach(function (value) {
                if (!selected) {
                    $('select[name="' + value + '"], input[name="' + value + '"]').attr('disabled', 'disabled');
                } else {
                    $('select[name="' + value + '"], input[name="' + value + '"]').removeAttr('disabled');
                }
            });
        });

        $(document).on('change', 'input[name="UPDATE_PRICE"]', function () {
            var selected = (parseInt($('input[name="UPDATE_PRICE"]:checked').val()) === 1);

            if (!selected) {
                $('select[name="UPDATE_UNIT_PRICE"], input[name="UPDATE_UNIT_PRICE"]').attr('disabled', 'disabled');
            } else {
                $('select[name="UPDATE_UNIT_PRICE"], input[name="UPDATE_UNIT_PRICE"]').removeAttr('disabled');
            }
        });
    </script>
{/block}

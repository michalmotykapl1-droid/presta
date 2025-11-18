<tr class="{$class}" x-id="{$row.id}" {if isset($editable) && !$editable && !isset($row.values)}style="display: none;"{/if}>
    <td>
        <input type="checkbox" id="deliveryMethods_{$row.id}" name="deliveryMethods[{$row.id}][enabled]" value="1" {if isset($row.values) && $row.values.enabled}checked="checked"{/if} x-name="switch" data-cast="switch" {if isset($editable) && !$editable}style="display: none;"{/if} />
        <label for="deliveryMethods_{$row.id}">{$row.name}</label>
    </td>
    <td {if $row.type == 'free'}style="display: none;"{/if}>
        <div class="input-group col-lg-2">
            <input style="width: 100px;" type="text" name="deliveryMethods[{$row.id}][firstItemRate]" data-name="firstItemRate" value="{if isset($row.values)}{$row.values.firstItemRate|string_format:"%.2f"}{else}0{/if}" data-cast="float" {if isset($editable) && !$editable}disabled="disabled"{/if} />
            <span class="input-group-addon">zł</span>
        </div>
    </td>
    <td {if $row.type == 'free'}style="display: none;"{/if}>
        <div class="input-group col-lg-2">
            <input style="width: 100px;" type="text" name="deliveryMethods[{$row.id}][nextItemRate]" data-name="nextItemRate" value="{if isset($row.values)}{$row.values.nextItemRate|string_format:"%.2f"}{else}0{/if}" data-cast="float" {if isset($editable) && !$editable}disabled="disabled"{/if} />
            <span class="input-group-addon">zł</span>
        </div>
    </td>
    <td {if $row.type == 'free'}style="display: none;"{/if}>
        <div class="input-group col-lg-2">
            <input style="width: 60px;" type="text" name="deliveryMethods[{$row.id}][maxQuantityPerPackage]" data-name="maxQuantityPerPackage" value="{if isset($row.values)}{$row.values.maxQuantityPerPackage}{else}1{/if}" data-cast="integer" {if isset($editable) && !$editable}disabled="disabled"{/if} />
            <span class="input-group-addon">szt.</span>
        </div>
    </td>
</tr>

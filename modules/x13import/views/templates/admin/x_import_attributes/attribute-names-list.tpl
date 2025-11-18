<tr class="attribute-row-option {if $isOdd}odd{/if}" data-x-attribute-group-id="{$xAttributeGroupId}">
    <td></td>
    <td colspan="4">
        <label for="attribute_import_default_option_{$xAttributeGroupId}">
            {l s='Domyślne ustawienie importu wartości atrybutów dla' mod='x13import'}: <b>{$xAttributeGroupName}</b>
        </label>
        <select id="attribute_import_default_option_{$xAttributeGroupId}" data-x-attribute-group-id="{$xAttributeGroupId}" data-value="{$xAttributeImportDefaultOption}">
            {foreach $attributeImportDefaultOption as $option}
                <option value="{$option.value}" {if $xAttributeImportDefaultOption == $option.value}selected="selected"{/if}>{$option.name}</option>
            {/foreach}
        </select>
    </td>
    <td></td>
</tr>

{foreach $attributes as $attribute}
    <tr class="attribute-row {if $isOdd}odd{/if}" data-x-attribute-group-id="{$xAttributeGroupId}" data-x-attribute-id="{$attribute.id_ximport_attribute}" data-attribute-group-id="{$attributeGroupId}">
        <td></td>
        <td class="column-name" colspan="2">
            <label>
                <input type="checkbox" data-x-attribute-id="{$attribute.id_ximport_attribute}">
                {$attribute.name}
            </label>
        </td>
        <td class="column-attribute_import_option">
            <select class="{if $attributeGroupImportOption == $attributeImportOption['DONT_IMPORT'].value}readonly{/if}" data-x-attribute-id="{$attribute.id_ximport_attribute}" data-value="{$attribute.import_option}">
                {foreach $attributeImportOption as $option}
                    <option value="{$option.value}" {if $attribute.import_option == $option.value}selected="selected"{/if}>{$option.name}</option>
                {/foreach}
            </select>
        </td>
        <td class="column-id_attribute">
            <select class="ximport-select2" data-x-attribute-id="{$attribute.id_ximport_attribute}" data-value="{$attribute.id_attribute}" style="width: 100%; {if $attribute.import_option != $attributeImportOption['ASSIGN_VALUE'].value}display: none;{/if}">
                <option value="{$attribute.id_attribute}" selected="selected">{$attribute.attribute_name}</option>
            </select>
        </td>
        <td></td>
    </tr>
{/foreach}

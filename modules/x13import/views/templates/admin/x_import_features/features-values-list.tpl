<tr class="feature-value-row-option {if $isOdd}odd{/if}" data-x-feature-id="{$xFeatureId}">
    <td></td>
    <td colspan="5">
        <label for="feature_import_default_option_{$xFeatureId}">
            {l s='Domyślne ustawienie importu wartości cech dla' mod='x13import'}: <b>{$xFeatureName}</b>
        </label>
        <select id="feature_import_default_option_{$xFeatureId}" data-x-feature-id="{$xFeatureId}" data-value="{$xFeatureImportDefaultOption}">
            {foreach $featureValueImportDefaultOption as $option}
                <option value="{$option.value}" {if $xFeatureImportDefaultOption == $option.value}selected="selected"{/if}>{$option.name}</option>
            {/foreach}
        </select>
    </td>
    <td></td>
</tr>

{foreach $featuresValues as $featureValue}
    <tr class="feature-value-row {if $isOdd}odd{/if}" data-x-feature-id="{$xFeatureId}" data-x-feature-value-id="{$featureValue.id_ximport_feature_value}" data-feature-id="{$featureId}">
        <td></td>
        <td class="column-value" colspan="2">
            <label>
                <input type="checkbox" data-x-feature-value-id="{$featureValue.id_ximport_feature_value}">
                {$featureValue.value}
            </label>
        </td>
        <td class="column-feature_value_import_option">
            <select class="{if $featureImportOption == $featureValueImportOption['DONT_IMPORT'].value}readonly{/if}" data-x-feature-value-id="{$featureValue.id_ximport_feature_value}" data-value="{$featureValue.import_option}">
                {foreach $featureValueImportOption as $option}
                    <option value="{$option.value}" {if $featureValue.import_option == $option.value}selected="selected"{/if}>{$option.name}</option>
                {/foreach}
            </select>
        </td>
        <td class="column-id_feature_value">
            <select class="ximport-select2" data-x-feature-value-id="{$featureValue.id_ximport_feature_value}" data-value="{$featureValue.id_feature_value}" style="width: 100%; {if $featureValue.import_option != $featureValueImportOption['ASSIGN_VALUE'].value}display: none;{/if}">
                <option value="{$featureValue.id_feature_value}" selected="selected">{$featureValue.feature_value}</option>
            </select>
        </td>
        <td colspan="2"></td>
    </tr>
{/foreach}

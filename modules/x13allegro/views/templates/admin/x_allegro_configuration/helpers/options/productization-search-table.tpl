{if isset($field['value'][$searchInput]['search']) && $field['value'][$searchInput]['search']}
    {$searchSelected = true}
{else}
    {$searchSelected = false}
{/if}

<tr>
    <td>
        <label>
            <input type="checkbox" class="productization-search-checkbox" name="PRODUCTIZATION_SEARCH[{$searchInput}][search]" value="1" {if $searchSelected}checked="checked"{/if}>
            {$searchLabel}{if isset($searchDesc)}<br><small>{$searchDesc}</small>{/if}
        </label>
    </td>
    <td>
        <select class="productization-search-select" name="PRODUCTIZATION_SEARCH[{$searchInput}][select]" {if !isset($field['value'][$searchInput]['search']) || !$field['value'][$searchInput]['search']}disabled="disabled"{/if}>
            <option value="always_first" {if isset($field['value'][$searchInput]['select']) && $field['value'][$searchInput]['select'] == 'always_first'}selected="selected"{/if}>{l s='zawsze pierwszy z listy znalezionych powiązań' mod='x13allegro'}</option>
            <option value="only_single" {if isset($field['value'][$searchInput]['select']) && $field['value'][$searchInput]['select'] == 'only_single'}selected="selected"{/if}>{l s='tylko gdy znaleziono jedno powiązanie' mod='x13allegro'}</option>
            <option value="none" {if (isset($field['value'][$searchInput]['select']) && $field['value'][$searchInput]['select'] == 'none') || !$searchSelected}selected="selected"{/if}>{l s='nie wybieraj' mod='x13allegro'}</option>
        </select>
    </td>
</tr>

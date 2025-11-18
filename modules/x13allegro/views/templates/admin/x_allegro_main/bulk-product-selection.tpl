<div class="xproductization-bulk-product">
    <div class="xproductization-bulk-modal-product-selection radio pull-left">
        <label>
            <input type="radio" class="bulk-modal-product-radio" name="bulk_category_product_selection_{$radioNameSuffix}" value="all" checked="checked">
            {l s='ustaw dla wszystkich' mod='x13allegro'}
        </label>
        <label>
            <input type="radio" class="bulk-modal-product-radio" name="bulk_category_product_selection_{$radioNameSuffix}" value="chosen">
            {l s='wybierz z listy' mod='x13allegro'}
        </label>
    </div>
    <div class="xproductization-bulk-modal-product-table" style="display: none;">
        <div class="pull-right">
            <a href="#" class="btn btn-default bulk-modal-product-table-select" data-select="1"><i class="icon-check-sign"></i> {l s='Zaznacz wszystkie' mod='x13allegro'}</a>
            <a href="#" class="btn btn-default bulk-modal-product-table-select" data-select="0"><i class="icon-check-empty"></i> {l s='Odznacz wszystkie' mod='x13allegro'}</a>
        </div>

        <table class="table">
            <colgroup>
                <col width="30px">
                <col>
            </colgroup>
            <thead>
                <tr>
                    <th colspan="2"></th>
                </tr>
            </thead>
            <tbody>
                {if isset($productSelection) && !empty($productSelection)}
                    {foreach $productSelection as $product}
                        <tr>
                            <td><input type="checkbox" id="bulk_product_{$product.index}_{$radioNameSuffix}" value="{$product.index}"></td>
                            <td><label for="bulk_product_{$product.index}_{$radioNameSuffix}">{$product.productName}{if !empty($product.productAttributeName)} - {$product.productAttributeName}{/if}</label></td>
                        </tr>
                    {/foreach}
                {/if}
            </tbody>
        </table>
    </div>
</div>

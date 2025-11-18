<div class="modal xproductization-category-similar-selector" id="category_similar_modal_{$index}" x-name="product_category_similar" x-index="{$index}" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header x13allegro-modal-header">
                <button type="button" class="close x13allegro-modal-close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="x13allegro-modal-title">{l s='Zmień kategorię' mod='x13allegro'}</h4>
                <h6 class="x13allegro-modal-title-small">dla produktu: <span>{$product.name}{if $product.name_attribute} - {$product.name_attribute}{/if}</span></h6>

                <span class="xproductization-product-label">
                    {if $productization_show_reference && !empty($product.reference)}<strong>Ref:</strong> {$product.reference}{/if}
                    {if $productization_show_gtin}
                        {if !empty({$product.ean13})}<strong>EAN13:</strong> {$product.ean13}{/if}
                        {if !empty({$product.isbn})}<strong>ISBN:</strong> {$product.isbn}{/if}
                        {if !empty({$product.upc})}<strong>UPC:</strong> {$product.upc}{/if}
                    {/if}
                    {if $productization_show_mpn && !empty($product.mpn)}<strong>MPN:</strong> {$product.mpn}{/if}
                </span>
            </div>
            <div class="modal-body x13allegro-modal-body">
                {* ajax content *}
            </div>
            <div class="modal-footer x13allegro-modal-footer">
                <button type="button" class="btn btn-primary xproductization-category-similar" data-dismiss="modal">{l s='Zatwierdź wybraną kategorie' mod='x13allegro'}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal xproductization-description-edit" id="description_edit_modal_{$index}" x-name="description_edit" x-index="{$index}" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header x13allegro-modal-header">
                <button type="button" class="close x13allegro-modal-close" x-name="description_cancel"><span>&times;</span></button>
                <h4 class="x13allegro-modal-title">{l s='Edytuj opis' mod='x13allegro'}</h4>
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
                <textarea name="item[{$index}][description]" x-name="description" style="display:none;">{$product.description}</textarea>
                <textarea id="description_edit_mce_{$index}" x-name="description_edit_mce" rows="10" cols="128"></textarea>
            </div>
            <div class="modal-footer x13allegro-modal-footer">
                <button type="button" class="btn btn-left btn-default" x-name="description_cancel">{l s='Anuluj' mod='x13allegro'}</button>
                <button type="button" class="btn btn-primary" x-name="description_save">{l s='Zapisz' mod='x13allegro'}</button>
            </div>
        </div>
    </div>

    <div class="modal_alert xallegro_modal_alert" id="description_edit_modal_alert_confirm">
        <div class="modal_alert-content">
            <div class="modal_alert-message">
                <h2>{l s='Czy na pewno chcesz anulować edycję opisu?' mod='x13allegro'}</h2>
                <p>{l s='Spowoduje to utratę wcześniej wprowadzonych zmian' mod='x13allegro'}</p>
            </div>
            <div class="modal_alert-buttons">
                <button type="button" class="btn btn-default modal_alert-cancel">{l s='Nie' mod='x13allegro'}</button>
                <button type="button" class="btn btn-primary modal_alert-confirm">{l s='Tak' mod='x13allegro'}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal bulk-category-selector" id="bulk_change_category_modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header x13allegro-modal-header">
                <button type="button" class="close x13allegro-modal-close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="x13allegro-modal-title">{l s='Działania masowe' mod='x13allegro'}</h4>
                <h6 class="x13allegro-modal-title-small">{l s='Wybierz kategorie' mod='x13allegro'}</h6>
            </div>
            <div class="modal-body x13allegro-modal-body clearfix">
                <div class="xproductization-category-list">
                    <div class="allegro-category-select">
                        <select name="bulk_id_allegro_category[]">
                            <option value="0">-- Wybierz --</option>
                            {foreach $categories as $category}
                                <option value="{$category.id}">{$category.name}</option>
                            {/foreach}
                        </select>
                    </div>
                    <hr style="margin:20px 0 5px 0;">
                </div>
                <div class="xproductization-category-search">
                    <div class="form-group clearfix">
                        <label class="control-label">ID kategorii</label>
                        <input type="text" class="fixed-width-xl" value="" x-name="product_category_input" placeholder="{l s='ID Kategorii' mod='x13allegro'}">
                        <input type="hidden" value="" x-name="product_category_input_current">
                        <input type="hidden" value="0" x-name="product_category_input_is_leaf">
                    </div>
                </div>

                <hr style="margin:20px 0 5px 0;">

                {include file='./bulk-product-selection.tpl' radioNameSuffix='0'}
            </div>
            <div class="modal-footer x13allegro-modal-footer">
                <button type="button" class="btn btn-primary bulk-category-submit">{l s='Ustaw wybraną kategorie' mod='x13allegro'}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal_alert xallegro_modal_alert" id="bulk_change_category_modal_alert_empty">
    <div class="modal_alert-content">
        <div class="modal_alert-message">
            <h2>{l s='Nie wybrano żadnego produktu do masowej zmiany kategorii' mod='x13allegro'}</h2>
        </div>
        <div class="modal_alert-buttons">
            <button type="button" class="btn btn-primary modal_alert-confirm">{l s='Ok' mod='x13allegro'}</button>
        </div>
    </div>
</div>

<div class="modal_alert xallegro_modal_alert" id="bulk_change_category_modal_alert_leaf">
    <div class="modal_alert-content">
        <div class="modal_alert-message">
            <h2>{l s='Nie wybrano poprawnej kategorii' mod='x13allegro'}</h2>
            <p>{l s='Podano niepoprawny numer kategorii, lub wybrana kategoria nie jest najniższego rzędu' mod='x13allegro'}</p>
        </div>
        <div class="modal_alert-buttons">
            <button type="button" class="btn btn-primary modal_alert-confirm">{l s='Ok' mod='x13allegro'}</button>
        </div>
    </div>
</div>

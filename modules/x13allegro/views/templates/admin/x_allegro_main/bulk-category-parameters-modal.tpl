<div class="modal bulk-parameters-selector" id="bulk_parameters_modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header x13allegro-modal-header">
                <button type="button" class="close x13allegro-modal-close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="x13allegro-modal-title">{l s='Działania masowe' mod='x13allegro'}</h4>
                <h6 class="x13allegro-modal-title-small">{l s='Ustaw parametry kategorii' mod='x13allegro'}</h6>
            </div>
            <div class="modal-body x13allegro-modal-body clearfix"></div>
            <div class="modal-footer x13allegro-modal-footer">
                <div class="x13allegro-modal-footer-left">
                    <span class="x13allegro-category-parameters-info-required">*</span> - pole wymagane<br/>
                    <span class="x13allegro-category-parameters-info-productization"></span> - parametr z <strong>Katalogu Allegro</strong>
                </div>
                <button type="button" class="btn btn-primary bulk-parameters-submit">{l s='Ustaw parametry' mod='x13allegro'}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal_alert xallegro_modal_alert" id="bulk_parameters_modal_alert_empty">
    <div class="modal_alert-content">
        <div class="modal_alert-message">
            <h2>{l s='Nie wybrano żadnego produktu do masowego ustawienia parametrów kategorii' mod='x13allegro'}</h2>
        </div>
        <div class="modal_alert-buttons">
            <button type="button" class="btn btn-primary modal_alert-confirm">{l s='Ok' mod='x13allegro'}</button>
        </div>
    </div>
</div>

<div class="modal_alert xallegro_modal_alert" id="bulk_parameters_modal_alert_leaf">
    <div class="modal_alert-content">
        <div class="modal_alert-message">
            <h2>{l s='Nie znaleziono żadnej poprawnej kategorii' mod='x13allegro'}</h2>
            <p>{l s='W wybranych produktach nie znaleziono poprawnej katgorii, lub ustawiona kategoria nie jest najniższego rzędu' mod='x13allegro'}</p>
        </div>
        <div class="modal_alert-buttons">
            <button type="button" class="btn btn-primary modal_alert-confirm">{l s='Ok' mod='x13allegro'}</button>
        </div>
    </div>
</div>

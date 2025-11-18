<div class="modal bulk-product-gpsr" id="bulk_product_gpsr_modal" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header x13allegro-modal-header">
                <button type="button" class="close x13allegro-modal-close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="x13allegro-modal-title">{l s='Działania masowe' mod='x13allegro'}</h4>
                <h6 class="x13allegro-modal-title-small">{l s='Zgodność z GPSR' mod='x13allegro'}</h6>
            </div>
            <div class="modal-body x13allegro-modal-body clearfix">
                <div class="row">
                    <div class="col-lg-offset-2 col-md-8">
                        <div class="form-group">
                            <label for="bulk_marketed_before_gpsr_obligation" class="control-label">Produkt wprowadzony do obrotu na terenie Unii Europejskiej przed&nbsp;13&nbsp;grudnia&nbsp;2024&nbsp;r.</label>
                            <select id="bulk_marketed_before_gpsr_obligation" x-name="bulk_marketed_before_gpsr_obligation">
                                <option value="">-- Wybierz --</option>
                                <option value="1">Tak</option>
                                <option value="0">Nie</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-offset-2 col-md-8">
                        <div class="form-group">
                            <label for="bulk_responsible_producer" class="control-label">Dane producenta - GPSR</label>
                            <select id="bulk_responsible_producer" x-name="bulk_responsible_producer">
                                <option value="">-- Wybierz --</option>
                                {foreach $responsibleProducers as $responsibleProducer}
                                    <option value="{$responsibleProducer->id}">{$responsibleProducer->name}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-offset-2 col-md-8">
                        <div class="form-group">
                            <label for="bulk_responsible_person" class="control-label">Osoba odpowiedzialna za zgodność produktu - GPSR</label>
                            <select id="bulk_responsible_person" x-name="bulk_responsible_person">
                                <option value="">-- Wybierz --</option>
                                {foreach $responsiblePersons as $responsiblePerson}
                                    <option value="{$responsiblePerson->id}">{$responsiblePerson->name}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-offset-2 col-md-8">
                        <div class="form-group">
                            <label for="bulk_safety_information_type" class="control-label">Informacje o bezpieczeństwie produktu</label>
                            <select id="bulk_safety_information_type" x-name="bulk_safety_information_type">
                                <option value="">-- Wybierz --</option>
                                {foreach $safetyInformationTypes as $safetyInformationType}
                                    <option value="{$safetyInformationType.id}">{$safetyInformationType.name}</option>
                                {/foreach}
                            </select>

                            <div class="gpsr-safety-information-text-wrapper" style="display: none;">
                                <textarea x-name="bulk_safety_information_text"></textarea>
                                <p class="help-block counter-wrapper" data-max="{$safetyInformationTextMax}">
                                    <span class="counter-error" style="display: none;">Tekst jest za długi!</span>
                                    <span class="counter"><span class="count">0</span>/{$safetyInformationTextMax}</span>
                                </p>
                            </div>

                            {include file="./gpsr-safety-information-attachment-wrapper.tpl" bulk=true}
                        </div>
                    </div>
                </div>

                <hr style="margin:20px 0 5px 0;">

                <div class="form-group checkbox">
                    <label>
                        <input type="checkbox" x-name="bulk_product_gpsr_override" value="1">
                        {l s='nadpisz już uzupełnione' mod='x13allegro'}
                    </label>
                </div>

                {include file='./bulk-product-selection.tpl' radioNameSuffix='0'}
            </div>
            <div class="modal-footer x13allegro-modal-footer">
                <button type="button" class="btn btn-primary bulk-product-gpsr-submit">{l s='Ustaw wybrane opcje GPSR' mod='x13allegro'}</button>
            </div>
        </div>
    </div>
</div>

<div class="modal_alert xallegro_modal_alert" id="bulk_product_gpsr_modal_alert_empty">
    <div class="modal_alert-content">
        <div class="modal_alert-message">
            <h2>{l s='Nie wybrano żadnego produktu do masowej zmiany zgodności z GPSR' mod='x13allegro'}</h2>
        </div>
        <div class="modal_alert-buttons">
            <button type="button" class="btn btn-primary modal_alert-confirm">{l s='Ok' mod='x13allegro'}</button>
        </div>
    </div>
</div>

<div class="modal_alert xallegro_modal_alert" id="bulk_product_gpsr_modal_alert_leaf">
    <div class="modal_alert-content">
        <div class="modal_alert-message">
            <h2>{l s='Nie znaleziono żadnej poprawnej kategorii' mod='x13allegro'}</h2>
            <p>{l s='W wybranych produktach nie znaleziono poprawnej katgorii, ustawiona kategoria nie jest najniższego rzędu, lub jest wykluczona z GPSR' mod='x13allegro'}</p>
        </div>
        <div class="modal_alert-buttons">
            <button type="button" class="btn btn-primary modal_alert-confirm">{l s='Ok' mod='x13allegro'}</button>
        </div>
    </div>
</div>

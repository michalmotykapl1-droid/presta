{extends file="helpers/list/list_footer.tpl"}

{block name="footer"}
    {$smarty.block.parent}

    </div>
{/block}

{block name="after"}
    <div id="bulk_feature_group" class="modal" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header x13import-modal-header">
                    <button type="button" class="close x13import-modal-close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="x13import-modal-title">{l s='Ustawienia masowe grup cech' mod='x13import'}</h4>
                </div>
                <div class="modal-body x13import-modal-body clearfix">
                    <label for="bulk_feature_group_import_option" class="control-label t col-lg-12">{l s='Ustawienia importu' mod='x13import'}</label>
                    <div class="col-lg-12">
                        <select id="bulk_feature_group_import_option">
                            <option value="">{l s='-- Wybierz --' mod='x13import'}</option>
                            {foreach $featureImportOption as $option}
                                <option value="{$option.value}">{$option.name}</option>
                            {/foreach}
                        </select>
                    </div>

                    <label for="bulk_feature_group_import_as_custom" class="control-label t col-lg-12">{l s='Dodaj jako cechy indywidualne' mod='x13import'}</label>
                    <div class="col-lg-12">
                        <select id="bulk_feature_group_import_as_custom">
                            <option value="">{l s='-- Wybierz --' mod='x13import'}</option>
                            <option value="1">{l s='Tak' mod='x13import'}</option>
                            <option value="0">{l s='Nie' mod='x13import'}</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer x13import-modal-footer">
                    <button type="submit" class="button btn btn-success pull-right">{l s='Ustaw' mod='x13import'}</button>
                </div>
            </div>
        </div>
    </div>

    <div id="bulk_feature_value" class="modal" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header x13import-modal-header">
                    <button type="button" class="close x13import-modal-close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="x13import-modal-title">{l s='Ustawienia masowe wartości cech' mod='x13import'}</h4>
                </div>
                <div class="modal-body x13import-modal-body clearfix">
                    <label for="bulk_feature_value_import_option" class="control-label t col-lg-12">{l s='Ustawienia importu' mod='x13import'}</label>
                    <div class="col-lg-12">
                        <select id="bulk_feature_value_import_option">
                            <option value="">{l s='-- Wybierz --' mod='x13import'}</option>
                            {foreach $featureValueImportOption as $option}
                                <option value="{$option.value}">{$option.name}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
                <div class="modal-footer x13import-modal-footer">
                    <button type="submit" class="button btn btn-success pull-right">{l s='Ustaw' mod='x13import'}</button>
                </div>
            </div>
        </div>
    </div>
{/block}

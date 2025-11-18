{extends file="helpers/list/list_footer.tpl"}

{block name="after"}
    <div id="ximport_bulk_popup_markup" class="modal" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <form action="{$currentIndex}&token={$token}" method="post" id="bulk_popup_markup_form">
                <div class="modal-content">
                    <div class="modal-header x13import-modal-header">
                        <button type="button" class="close x13import-modal-close" data-dismiss="modal"><span>&times;</span></button>
                        <h4 class="x13import-modal-title">{l s='Ustaw narzut kategorii' mod='x13import'}</h4>
                    </div>
                    <div class="modal-body x13import-modal-body clearfix">
                        <div id="bulk_markup_hidden"></div>

                        <label for="bulk_markup_type" class="control-label t col-lg-12">{l s='Rodzaj narzutu' mod='x13import'}:</label>
                        <div class="col-lg-12">
                            <select id="bulk_markup_type" name="bulk_markup_type">
                                <option value="percent">{l s='procent' mod='x13import'}</option>
                                <option value="amount">{l s='kwota' mod='x13import'}</option>
                            </select>
                        </div>

                        <label for="bulk_markup" class="control-label t col-lg-12">{l s='Narzut' mod='x13import'}:</label>
                        <div class="col-lg-12">
                            <input type="text" name="bulk_markup" id="bulk_markup" value="0" class="x-cast x-cast-float">
                        </div>
                    </div>
                    <div class="modal-footer x13import-modal-footer">
                        <button class="button btn btn-success pull-right" type="submit" name="submitBulkmarkupximport_category" value="1"><i class="icon-save"></i>&nbsp;&nbsp;{l s='Zapisz' mod='x13import'}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div id="ximport_bulk_popup_title_pattern" class="modal" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog" role="document">
            <form action="{$currentIndex}&token={$token}" method="post" id="bulk_popup_title_pattern_form">
                <div class="modal-content">
                    <div class="modal-header x13import-modal-header">
                        <button type="button" class="close x13import-modal-close" data-dismiss="modal"><span>&times;</span></button>
                        <h4 class="x13import-modal-title">{l s='Ustaw wzorzec nazwy produktu' mod='x13import'}</h4>
                    </div>
                    <div class="modal-body x13import-modal-body clearfix">
                        <div id="bulk_title_pattern_hidden"></div>

                        <label for="bulk_title_pattern" class="control-label t col-lg-12">{l s='Wzorzec nazwy produktu' mod='x13import'}:</label>
                        <div class="col-lg-12">
                            <input type="text" name="bulk_title_pattern" id="bulk_title_pattern" value="" style="width: 100%;">
                            <div class="help-block">
                                <p>{l s='Wzorzec nazwy produktu może zawierać dowolne znaki alfanumeryczne oraz zmienne' mod='x13import'}:</p>
                                <ul>
                                    <li><b>{literal}{%name%}{/literal}</b> - {l s='która zostanie zastąpiona nazwą produktu' mod='x13import'}</li>
                                    <li><b>{literal}{%reference%}{/literal}</b> - {l s='która zostanie zastąpiona kodem referencyjnym' mod='x13import'}</li>
                                    <li><b>{literal}{%manufacturer%}{/literal}</b> - {l s='która zostanie zastąpiona nazwą producenta' mod='x13import'}</li>
                                    <li><b>{literal}[%feature%]{/literal}</b> - {l s='która zostanie zastapiona wartością podanej cechy, np. [%Materiał%]' mod='x13import'}</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer x13import-modal-footer">
                        <button class="button btn btn-success pull-right" type="submit" name="submitBulktittlePatternximport_category" value="1"><i class="icon-save"></i>&nbsp;&nbsp;{l s='Zapisz' mod='x13import'}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {literal}
        <script>
            var X13Import = new $.XImport();
            X13Import.categoriesList();
        </script>
    {/literal}
{/block}

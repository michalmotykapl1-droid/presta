{extends file="helpers/list/list_header.tpl"}

{block name=leadin}
    <form id="xallegro_advanced_filters" class="defaultForm form-horizontal bootstrap clearfix" action="{$currentIndex}&token={$token}" method="post">
        <div class="panel col-lg-12 clearfix">
            <fieldset>
                <div class="panel-heading">{l s='Zaawansowane filtrowanie' mod='x13allegro'}</div>

                <div class="row">
                    <div class="col-lg-6">
                        <div id="container_category_tree">
                            {$category_tree}
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="row">

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="col-xs-4 control-label">{l s='Cena' mod='x13allegro'}:</label>
                                    <input type="text" name="xFilterPriceFrom" value="{$xFilterPriceFrom}" data-cast="float" /> -
                                    <input type="text" name="xFilterPriceTo" value="{$xFilterPriceTo}" data-cast="float" />
                                </div>
                                <div class="form-group">
                                    <label class="col-xs-4 control-label">{l s='Ilość' mod='x13allegro'}:</label>
                                    <input type="text" name="xFilterQtyFrom" value="{$xFilterQtyFrom}" data-cast="integer" /> -
                                    <input type="text" name="xFilterQtyTo" value="{$xFilterQtyTo}" data-cast="integer" />
                                </div>

                                {if !empty($manufacturers)}
                                    <div class="form-group chosen-styled-selectbox">
                                        <label for="xFilterManufacturer" class="col-xs-4 control-label">{l s='Producent' mod='x13allegro'}:</label>
                                        <div class="col-xs-8">
                                        <select data-placeholder="Wybierz" id="xFilterManufacturer" class="fixed-width-lg chosen" name="xFilterManufacturer[]" multiple>
                                            <option></option>
                                            {foreach from=$manufacturers item=manufacturer}
                                                <option
                                                    value="{$manufacturer.id_manufacturer}"
                                                    {if $manufacturer.id_manufacturer|in_array:$xFilterManufacturer}selected="selected"{/if}
                                                >{$manufacturer.name}</option>
                                            {/foreach}
                                        </select>
                                        <script>
                                            $(function() {
                                                var selectedManufacturers = {$xFilterManufacturer|json_encode};
                                                $('#xFilterManufacturer').val(selectedManufacturers).trigger('chosen:updated');
                                            });
                                        </script>
                                        </div>
                                    </div>
                                {/if}

                                {if !empty($suppliers)}
                                    <div class="form-group chosen-styled-selectbox">
                                        <label for="xFilterSupplier" class="col-xs-4 control-label">{l s='Dostawca' mod='x13allegro'}:</label>
                                        <div class="col-xs-8">
                                        <select data-placeholder="Wybierz" id="xFilterSupplier" class="fixed-width-lg chosen" name="xFilterSupplier[]" multiple>
                                            <option></option>
                                            {foreach from=$suppliers item=supplier}
                                                <option
                                                    value="{$supplier.id_supplier}"
                                                    {if $supplier.id_supplier|in_array:$xFilterSupplier}selected="selected"{/if}
                                                >{$supplier.name}</option>
                                            {/foreach}
                                        </select>
                                        <script>
                                            $(function() {
                                                var selectedSuppliers = {$xFilterSupplier|json_encode};
                                                $('#xFilterSupplier').val(selectedSuppliers).trigger('chosen:updated');
                                            });
                                        </script>
                                        </div>
                                    </div>
                                {/if}
                            </div>

                            <div class="col-md-6">
                                <div class="clearfix" style="margin-bottom: 6px;">
                                    <label for="xFilterPerformed" class="col-xs-4 control-label">{l s='Wystawiony' mod='x13allegro'}:</label>
                                    <select id="xFilterPerformed" name="xFilterPerformed" class="col-xs-8">
                                        <option value="0">--</option>
                                        <option value="1" {if $xFilterPerformed == 1}selected="selected"{/if}>{l s='tak (lub chociaż 1dna kombinacja)' mod='x13allegro'}</option>
                                        <option value="2" {if $xFilterPerformed == 2}selected="selected"{/if}>{l s='tak (wszystkie kombinacje)' mod='x13allegro'}</option>
                                        <option value="3" {if $xFilterPerformed == 3}selected="selected"{/if}>{l s='nie (lub żadna kombinacja)' mod='x13allegro'}</option>
                                        <option value="4" {if $xFilterPerformed == 4}selected="selected"{/if}>{l s='nie (nie wszystkie kombinacje)' mod='x13allegro'}</option>
                                    </select>
                                </div>
                                
                                <div class="radio clearfix">
                                <div class="radio clearfix">
                                    <label class="col-xs-4 control-label">{l s='Ukryj "zbiorcze"' mod='x13allegro'}:</label>
                                    <label class="filter-label" for="xFilterHideZbiorcze_1">
                                        <input type="radio" name="xFilterHideZbiorcze" id="xFilterHideZbiorcze_1" value="1" {if $xFilterHideZbiorcze}checked="checked"{/if} />
                                        <img src="../img/admin/enabled.gif" alt="{l s='Tak' mod='x13allegro'}" title="{l s='Tak' mod='x13allegro'}" />
                                    </label>
                                    <label class="filter-label" for="xFilterHideZbiorcze_0">
                                        <input type="radio" name="xFilterHideZbiorcze" id="xFilterHideZbiorcze_0" value="0" {if !$xFilterHideZbiorcze}checked="checked"{/if} />
                                        <img src="../img/admin/disabled.gif" alt="{l s='Nie' mod='x13allegro'}" title="{l s='Nie' mod='x13allegro'}" />
                                    </label>
                                </div>
                                <div class="radio clearfix">
                                    <label class="col-xs-4 control-label">{l s='Ukryj "surowiec"' mod='x13allegro'}:</label>
                                    <label class="filter-label" for="xFilterHideSurowiec_1">
                                        <input type="radio" name="xFilterHideSurowiec" id="xFilterHideSurowiec_1" value="1" {if $xFilterHideSurowiec}checked="checked"{/if} />
                                        <img src="../img/admin/enabled.gif" alt="{l s='Tak' mod='x13allegro'}" title="{l s='Tak' mod='x13allegro'}" />
                                    </label>
                                    <label class="filter-label" for="xFilterHideSurowiec_0">
                                        <input type="radio" name="xFilterHideSurowiec" id="xFilterHideSurowiec_0" value="0" {if !$xFilterHideSurowiec}checked="checked"{/if} />
                                        <img src="../img/admin/disabled.gif" alt="{l s='Nie' mod='x13allegro'}" title="{l s='Nie' mod='x13allegro'}" />
                                    </label>
                                </div>
                                <div class="radio clearfix">
                                    <label class="col-xs-4 control-label">{l s='Pokaż tylko "zbiorcze"' mod='x13allegro'}:</label>
                                    <label class="filter-label" for="xFilterShowZbiorcze_1">
                                        <input type="radio" name="xFilterShowZbiorcze" id="xFilterShowZbiorcze_1" value="1" {if $xFilterShowZbiorcze}checked="checked"{/if} />
                                        <img src="../img/admin/enabled.gif" alt="{l s='Tak' mod='x13allegro'}" title="{l s='Tak' mod='x13allegro'}" />
                                    </label>
                                    <label class="filter-label" for="xFilterShowZbiorcze_0">
                                        <input type="radio" name="xFilterShowZbiorcze" id="xFilterShowZbiorcze_0" value="0" {if !$xFilterShowZbiorcze}checked="checked"{/if} />
                                        <img src="../img/admin/disabled.gif" alt="{l s='Nie' mod='x13allegro'}" title="{l s='Nie' mod='x13allegro'}" />
                                    </label>
                                </div>
                                <div class="radio clearfix">
                                    <label class="col-xs-4 control-label">{l s='Pokaż tylko "surowiec"' mod='x13allegro'}:</label>
                                    <label class="filter-label" for="xFilterShowSurowiec_1">
                                        <input type="radio" name="xFilterShowSurowiec" id="xFilterShowSurowiec_1" value="1" {if $xFilterShowSurowiec}checked="checked"{/if} />
                                        <img src="../img/admin/enabled.gif" alt="{l s='Tak' mod='x13allegro'}" title="{l s='Tak' mod='x13allegro'}" />
                                    </label>
                                    <label class="filter-label" for="xFilterShowSurowiec_0">
                                        <input type="radio" name="xFilterShowSurowiec" id="xFilterShowSurowiec_0" value="0" {if !$xFilterShowSurowiec}checked="checked"{/if} />
                                        <img src="../img/admin/disabled.gif" alt="{l s='Nie' mod='x13allegro'}" title="{l s='Nie' mod='x13allegro'}" />
                                    </label>
                                </div>
                                </div>


                                <div class="radio clearfix">
                                    <label class="col-xs-4 control-label">{l s='Ukryj SKU "A_MAG"' mod='x13allegro'}:</label>
                                    <label class="filter-label" for="xFilterHideAmag_1">
                                        <input type="radio" name="xFilterHideAmag" id="xFilterHideAmag_1" value="1" {if $xFilterHideAmag}checked="checked"{/if} />
                                        <img src="../img/admin/enabled.gif" alt="{l s='Tak' mod='x13allegro'}" title="{l s='Tak' mod='x13allegro'}" />
                                    </label>
                                    <label class="filter-label" for="xFilterHideAmag_0">
                                        <input type="radio" name="xFilterHideAmag" id="xFilterHideAmag_0" value="0" {if !$xFilterHideAmag}checked="checked"{/if} />
                                        <img src="../img/admin/disabled.gif" alt="{l s='Nie' mod='x13allegro'}" title="{l s='Nie' mod='x13allegro'}" />
                                    </label>
                                </div>
                                <div class="radio clearfix">
                                    <label class="col-xs-4 control-label">{l s='Pokaż tylko SKU "A_MAG"' mod='x13allegro'}:</label>
                                    <label class="filter-label" for="xFilterShowAmag_1">
                                        <input type="radio" name="xFilterShowAmag" id="xFilterShowAmag_1" value="1" {if $xFilterShowAmag}checked="checked"{/if} />
                                        <img src="../img/admin/enabled.gif" alt="{l s='Tak' mod='x13allegro'}" title="{l s='Tak' mod='x13allegro'}" />
                                    </label>
                                    <label class="filter-label" for="xFilterShowAmag_0">
                                        <input type="radio" name="xFilterShowAmag" id="xFilterShowAmag_0" value="0" {if !$xFilterShowAmag}checked="checked"{/if} />
                                        <img src="../img/admin/disabled.gif" alt="{l s='Nie' mod='x13allegro'}" title="{l s='Nie' mod='x13allegro'}" />
                                    </label>
                                </div>

<div class="radio clearfix">
                                    <label class="col-xs-4 control-label">{l s='Aktywny' mod='x13allegro'}:</label>
                                    <label class="filter-label" for="xFilterActive_1">
                                        <input type="radio" name="xFilterActive" id="xFilterActive_1" value="2" {if $xFilterActive == 2}checked="checked"{/if} />
                                        <img src="../img/admin/enabled.gif" alt="{l s='Tak' mod='x13allegro'}" title="{l s='Tak' mod='x13allegro'}" />
                                    </label>
                                    <label class="filter-label" for="xFilterActive_0">
                                        <input type="radio" name="xFilterActive" id="xFilterActive_0" value="1" {if $xFilterActive == 1}checked="checked"{/if} />
                                        <img src="../img/admin/disabled.gif" alt="{l s='Nie' mod='x13allegro'}" title="{l s='Nie' mod='x13allegro'}" />
                                    </label>
                                </div>
                            </div>

                        </div>
                    </div>

                    <div class="col-lg-12 xfilter-submit">
                        <a href="{$currentIndex}&token={$token}&reset_xFilter=1" class="button btn btn-default">{l s='Resetuj' mod='x13allegro'}</a>
                        <button type="submit" name="submit_xFilter" class="button btn btn-success">{l s='Filtruj' mod='x13allegro'}</button>
                    </div>
                </div>
            </fieldset>
        </div>
    </form>
{/block}

{block name="startForm"}
    <form method="post" action="{$currentIndex}&token={$token}" class="form-horizontal clearfix" id="allegro_perform_list">
{/block}
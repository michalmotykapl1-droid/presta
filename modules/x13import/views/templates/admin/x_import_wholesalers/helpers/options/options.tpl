{extends file="helpers/options/options.tpl"}

{block name="leadin"}
    {include file="../../../progress_block.tpl"}
{/block}

{block name="input"}
    {if $field['type'] == 'hr'}
        <hr />
    {elseif $category == 'general'}
        <input type="hidden" name="tabWholesaler" value="{$tab_wholesaler}">
        <input type="hidden" name="tabWholesalerOption" value="{$tab_wholesaler_option}">

        <ul class="nav nav-tabs nav-tab-wholesaler" id="tabWholesaler">
            {if !empty($wholesalers)}
                {foreach from=$wholesalers item=wholesale name=wholesale}
                    <li{if $smarty.foreach.wholesale.index == 0} class="active"{/if}><a href="#{$wholesale.name}" aria-controls="{$wholesale.name}" role="tab" data-toggle="tab">{$wholesale.name}</a></li>
                {/foreach}
            {/if}
            <li{if empty($wholesalers)} class="active"{/if}><a href="#addNewWholesaler" aria-controls="addNewWholesaler" role="tab" data-toggle="tab">{l s='Dodaj hurtownię' mod='x13import'}&nbsp;&nbsp;<i class="icon-plus-sign"></i></a></li>
        </ul>

        <div role="tabpanel" class="tab-content tab-wholesaler row" id="tabContentWholesaler">
            {if !empty($wholesalers)}
                {foreach from=$wholesalers item=wholesale name=wholesale}
                    <div class="tab-pane{if $smarty.foreach.wholesale.index == 0} active{/if}" id="{$wholesale.name}">
                        {if $maintenance_mode}
                            <div class="col-lg-12">
                                <div class="alert alert-info">
                                    {if $wholesale.info}<p><b>info</b>: {$wholesale.info}</p>{/if}
                                    {if $wholesale.version}<p><b>version</b>: {$wholesale.version}</p>{/if}
                                    <p><b>hasAttributes</b>: {if $wholesale.hasAttributes}Tak{else}Nie{/if}</p>
                                    <p><b>hasFeatures</b>: {if $wholesale.hasFeatures}Tak{else}Nie{/if}</p>
                                </div>
                            </div>
                        {/if}

                        {if !$wholesale.code}
                            <div class="alert alert-warning">
                                <p><b>{l s='Plik hurtowni nie jest zgodny z najnowszą wersją modułu' mod='x13import'}</b> <i><small>(INVALID STATIC CODE)</small></i>.</p>
                                <p>{l s='Skontaktuj się z działem supportu, aby uzyskać aktualizacje.' mod='x13import'}</p>
                            </div>
                        {/if}

                        <div class="col-lg-2">
                            <ul class="list-group nav-tab-wholesaler-option" id="tabWholesaler{$wholesale.name}">
                                <li class="list-group-item active"><a href="#{$wholesale.name}_baseOptions" aria-controls="{$wholesale.name}_baseOptions" role="tab" data-toggle="tab"><i class="icon-cogs"></i>&nbsp;&nbsp;{l s='Konfiguracja podstawowa' mod='x13import'}</a></li>
                                <li class="list-group-item"><a href="#{$wholesale.name}_availabilityOptions" aria-controls="{$wholesale.name}_availabilityOptions" role="tab" data-toggle="tab"><i class="icon-calendar"></i>&nbsp;&nbsp;{l s='Informacje o dostępności' mod='x13import'}</a></li>
                                <li class="list-group-item"><a href="#{$wholesale.name}_descriptionOptions" aria-controls="{$wholesale.name}_descriptionOptions" role="tab" data-toggle="tab"><i class="icon-indent"></i>&nbsp;&nbsp;{l s='Opis produktu i SEO' mod='x13import'}</a></li>
                                <li class="list-group-item"><a href="#{$wholesale.name}_additionalOptions" aria-controls="{$wholesale.name}_additionalOptions" role="tab" data-toggle="tab"><i class="icon-asterisk"></i>&nbsp;&nbsp;{l s='Dodatkowe ustawienia' mod='x13import'}</a></li>
                                <li class="list-group-item"><a href="#{$wholesale.name}_importRestrictionOptions" aria-controls="{$wholesale.name}_importRestrictionOptions" role="tab" data-toggle="tab"><i class="icon-ban"></i>&nbsp;&nbsp;{l s='Ograniczenia importu' mod='x13import'}</a></li>
                                <li class="list-group-item"><a href="#{$wholesale.name}_productManagement" aria-controls="{$wholesale.name}_productManagement" role="tab" data-toggle="tab"><i class="icon-book"></i>&nbsp;&nbsp;{l s='Zarządzanie produktami' mod='x13import'}</a></li>
                            </ul>
                        </div>
                        <div class="col-lg-10">
                            <div role="tabpanel" class="tab-content tab-wholesaler-option panel" id="tabWholesaler{$wholesale.name}">
                                <div class="tab-pane active" id="{$wholesale.name}_baseOptions">
                                    <div class="panel-heading">{$wholesale.name} - {l s='Konfiguracja podstawowa' mod='x13import'}</div>

                                    {if empty($wholesale.baseOptions)}
                                        <div class="form-wrapper">
                                            <div class="form-group">
                                                <p>{l s='Dla hurtowni [1]%s[/1] nie przewidzano ustawień indywidualnych.' sprintf=[$wholesale.name] tags=['<strong>'] mod='x13import'}</p>
                                            </div>
                                        </div>
                                    {else}
                                        {include file="./options_fields.tpl" optionsFields=$wholesale.baseOptions}
                                    {/if}

                                    {if !empty($wholesale.descriptionOptions.cleanerRecommended) || !empty($wholesale.recommendedOptions)}
                                        <hr>

                                        {if !empty($wholesale.descriptionOptions.cleanerRecommended)}
                                            <div class="alert alert-info">
                                                <p>{l s='Sugerujemy dla tej hurtowni ustawienie poprawienia opisu. Przejdź' mod='x13import'} <a href="#{$wholesale.name}_descriptionOptions" class="link-recommendations" data-wholesaler="{$wholesale.name}">{l s='TUTAJ' mod='x13import'}</a></p>
                                            </div>
                                        {/if}

                                        {if !empty($wholesale.recommendedOptions)}
                                            <div class="alert alert-info">
                                                <p>{l s='Sugerujemy dla tej hurtowni ustawienie następujących opcji. Przejdź' mod='x13import'} <a href="#{$wholesale.name}_descriptionOptions" class="link-recommendations" data-wholesaler="{$wholesale.name}">{l s='TUTAJ' mod='x13import'}</a></p>
                                                <ul>
                                                    {foreach $wholesale.recommendedOptions as $recommendedOption}
                                                        <li>{$recommendedOption}</li>
                                                    {/foreach}
                                                </ul>
                                            </div>
                                        {/if}
                                    {/if}
                                </div>
                                <div class="tab-pane" id="{$wholesale.name}_availabilityOptions">
                                    <div class="panel-heading">{$wholesale.name} - {l s='Informacje o dostępności' mod='x13import'}</div>
                                    {include file="./options_fields.tpl" optionsFields=$wholesale.availabilityOptions}
                                </div>
                                <div class="tab-pane" id="{$wholesale.name}_descriptionOptions">
                                    <div class="panel-heading">{$wholesale.name} - {l s='Opis produktu i SEO' mod='x13import'}</div>

                                    {if !empty($wholesale.descriptionOptions.cleanerRecommended)}
                                        <div class="col-lg-4 col-lg-offset-3"><div class="cleaner-recommended">Rekomendowane ustawienia dla tej hurtowni</div></div>
                                        <div class="clearfix"></div>
                                        {include file="./options_fields.tpl" optionsFields=$wholesale.descriptionOptions.cleanerRecommended}
                                    {/if}

                                    {if !empty($wholesale.descriptionOptions.cleanerDefault)}
                                        {if !empty($wholesale.descriptionOptions.cleanerRecommended)}
                                            <div class="col-lg-4 col-lg-offset-3"><a href="#" class="cleaner-default">Pokaż więcej ustawień</a></div>
                                            <div class="clearfix"></div>
                                            <div class="cleaner-default-fields" style="display: none;">
                                                <hr/>
                                                {include file="./options_fields.tpl" optionsFields=$wholesale.descriptionOptions.cleanerDefault}

                                                {if !empty($wholesale.descriptionOptions.cleanerDisabled)}
                                                    {include file="./options_fields.tpl" optionsFields=$wholesale.descriptionOptions.cleanerDisabled}
                                                {/if}
                                            </div>
                                        {else}
                                            {include file="./options_fields.tpl" optionsFields=$wholesale.descriptionOptions.cleanerDefault}
                                        {/if}
                                    {/if}

                                    {if empty($wholesale.descriptionOptions.cleanerRecommended) && !empty($wholesale.descriptionOptions.cleanerDisabled)}
                                        {include file="./options_fields.tpl" optionsFields=$wholesale.descriptionOptions.cleanerDisabled}
                                    {/if}

                                    <div class="form-group"><hr /></div>
                                    {include file="./options_fields.tpl" optionsFields=$wholesale.descriptionOptions.general}
                                </div>
                                <div class="tab-pane" id="{$wholesale.name}_additionalOptions">
                                    <div class="panel-heading">{$wholesale.name} - {l s='Dodatkowe ustawienia' mod='x13import'}</div>
                                    {include file="./options_fields.tpl" optionsFields=$wholesale.additionalOptions}
                                </div>
                                <div class="tab-pane" id="{$wholesale.name}_importRestrictionOptions">
                                    <div class="panel-heading">{$wholesale.name} - {l s='Ograniczenia importu' mod='x13import'}</div>
                                    {include file="./options_fields.tpl" optionsFields=$wholesale.importRestrictionOptions}
                                </div>
                                <div class="tab-pane" id="{$wholesale.name}_productManagement">
                                    <div class="panel-heading">{$wholesale.name} - {l s='Zarządzanie produktami' mod='x13import'}</div>
                                    <div class="alert alert-info">
                                        <p>{l s='W przypadku wyłączenia produktów, pozostaną one wyłączone do kolejnego uruchomienia zadania cron.php' mod='x13import'}<br/>
                                        {l s='Jeśli produkt dostępny jest w hurtowni, w pobranej, aktywnej oraz zmapowanej / zaimportowanej kategorii zostanie ponownie włączony.' mod='x13import'}<br/>
                                        {l s='Jeśli produkt ma być na stałe wyłączony mimo jego dostępności w hurtowni, nalezy wyłaczyć go a następnie wykluczyć w zakładce "Wykluczenie produktów".' mod='x13import'}</p>
                                    </div>

                                    <div class="form-wrapper">
                                        <a href="#" data-wholesaler="{$wholesale.name}" data-wholesaler-code="{$wholesale.code}" class="btn btn-default" {if !$wholesale.code}disabled="disabled"{/if}><i class="process-icon-off x-products-off"></i>&nbsp;&nbsp;{l s='Wyłącz produkty z hurtowni' mod='x13import'}</a>
                                        <a href="#" data-wholesaler="{$wholesale.name}" data-wholesaler-code="{$wholesale.code}" class="btn btn-default" {if !$wholesale.code}disabled="disabled"{/if}><i class="process-icon-delete x-products-delete"></i>&nbsp;&nbsp;{l s='Usuń produkty z hurtowni' mod='x13import'}</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                {/foreach}
            {/if}

            <div class="tab-pane{if empty($wholesalers)} active{/if}" id="addNewWholesaler">
                <div class="col-lg-12">
                    <div class="alert alert-info">
                        {if empty($wholesalers)}
                            <p>{l s='W tym miejscu można wgrać pierwszą hurtownię do modułu' mod='x13import'} <strong>{l s='integracji XML / CSV / API' mod='x13import'}.</strong><br/>
                            {l s='Gdy zamówienie zostanie opłacone, otrzymasz maila z pytaniem o hurtownie którą mamy przygotować.' mod='x13import'}<br/>
                            {l s='Ustawiona wersja PHP to' mod='x13import'}: <strong>{phpversion()}</strong><br/>
                            {l s='Po otrzymaniu pliku, należy wgrać go poniżej, a następnie przejść do konfiguracji modułu.' mod='x13import'}</p>
                        {else}
                            <p>{l s='W tym miejscu można dodać kolejną hurtownię do modułu' mod='x13import'} <strong>{l s='integracji XML / CSV / API' mod='x13import'}.</strong><br/>
                            {l s='Skontaktuj się z nami mailowo, prześlij informację z jaką hurtownię chcesz się zintegrować.' mod='x13import'}<br/>
                            {l s='Po otrzymaniu informacji o posiadanej gotowej integracji lub możliwości jej realizacji z dostarczonego pliku, dokonaj zakupu dodania dodatkowej hurtowni' mod='x13import'}
                            <a href="https://x13.pl/integracje-prestashop/dodatkowa-hurtownia-do-modulu-import-xml.html" target="_blank">{l s='- TUTAJ' mod='x13import'}</a><br/><br/>
                            {l s='Ustawiona wersja PHP to' mod='x13import'}: <strong>{phpversion()}</strong>
                            </p>
                        {/if}
                    </div>

                    <div class="alert alert-danger">
                        <p>
                        {l s='Pamiętaj aby w tym miejscu wgrywać tylko i wyłącznie pliki hurtowni dostarczone od' mod='x13import'} <strong>x13.pl</strong>{l s=', mailowo lub z adresu URL.' mod='x13import'}</p>
                    </div>
                </div>

                <div class="form-wrapper">
                    <div class="form-group">
                        <label class="control-label col-lg-3">{l s='Plik hurtowni' mod='x13import'}</label>
                        <div class="col-lg-4">
                            <input id="wholesaler_file" type="file" name="wholesaler_file" style="display: none;" />
                            <div class="dummyfile input-group">
                                <span class="input-group-addon"><i class="icon-file"></i></span>
                                <input id="wholesaler_file-name" type="text" name="wholesaler_file" readonly />
                                <span class="input-group-btn">
                                    <button id="wholesaler_file-selectbutton" type="button" class="btn btn-default">
                                        <i class="icon-folder-open"></i> {l s='Add file'}
                                    </button>
                                </span>
                            </div>
                        </div>
                        <div class="col-lg-6 col-lg-offset-3">
                            <div class="help-block">{l s='Plik hurtowni o rozszerzeniu ".php" lub paczka ".zip" otrzymana od x13.pl' mod='x13import'}</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="col-lg-9 col-lg-push-3">
                            <button class="btn btn-default" type="submit" name="submitAddNewWholesaler">
                                <i class="icon-upload-alt"></i>&nbsp;{l s='Wgraj hurtownię' mod='x13import'}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{block name="after"}
    <script>
        var wholesalersAdminLink = '{$wholesalers_link}';
        var tabWholesaler = getUrlParam('tabWholesaler');
        var tabWholesalerOption = getUrlParam('tabWholesalerOption');

        $(document).ready(function() {
            if (tabWholesaler) {
                $('.nav-tab-wholesaler li a[aria-controls="' + tabWholesaler + '"]').trigger('click');
                if (tabWholesalerOption) {
                    $('.tab-wholesaler #' + tabWholesaler + ' .nav-tab-wholesaler-option li a[aria-controls="' + tabWholesalerOption + '"]').trigger('click');
                }
            }

            $('a.link-recommendations').on('click', function(e) {
                e.preventDefault();
                $('ul#tabWholesaler' + $(this).attr('data-wholesaler')).find('a[href="' + $(this).attr('href') + '"]').click();
            });
            
            $('.tab-wholesaler-option').find('select[multiple]').each(function(i, el) {
                $(el).on('click', function() {
                    if ($(this).find(':selected').val() === 'all') {
                        $(this).find('option').prop('selected', false);
                        $(this).find('option[value="all"]').prop('selected', true);
                    }
                });
            });

            $('.multiple-clear').on('click', function(e) {
                e.preventDefault();

                $(this).parent().find('select[multiple]').each(function() {
                    $(this).find('option').prop('selected', false);
                });

                $(this).parent().find('select[multiple] option.multiple-empty').prop('selected', true);
            });

            $('.individual-enabled').on('change', function() {
                var elements = 'input:not(.individual-enabled):not(.version-disabled):not(.skip-dynamic), ' +
                    'select:not(.individual-enabled):not(.version-disabled):not(.skip-dynamic), ' +
                    'button:not(.individual-enabled):not(.version-disabled):not(.skip-dynamic)';

                if (parseInt($(this).val()) === 1) {
                    $(this).closest('.tab-pane').find(elements).removeAttr('disabled');
                } else {
                    $(this).closest('.tab-pane').find(elements).prop('disabled', true);
                }
            });

            $('.cleaner-default').on('click', function(e) {
                e.preventDefault();

                $(this).hide();
                $(this).closest('.tab-pane').find('.cleaner-default-fields').show();
            });

            $('#wholesaler_file-selectbutton').click(function() {
                $('#wholesaler_file').trigger('click');
            });

            $('#wholesaler_file-name').click(function() {
                $('#wholesaler_file').trigger('click');
            });

            $('#wholesaler_file').change(function() {
                var name  = '';

                if ($(this)[0].files !== undefined) {
                    var files = $(this)[0].files;
                    $.each(files, function(index, value) {
                        name += value.name+', ';
                    });

                    $('#wholesaler_file-name').val(name.slice(0, -2));
                }
                else { // Internet Explorer 9 Compatibility
                    name = $(this).val().split(/[\\/]/);
                    $('#wholesaler_file-name').val(name[name.length-1]);
                }
            });

            $('button[name="submitAddNewWholesaler"]').on('click', function(e) {
                if (!$('#wholesaler_file')[0].files.length) {
                    e.preventDefault();
                    alert('Nie wybrano pliku do przesłania');
                }
            });

            $('.nav-tab-wholesaler a').on('click', function() {
                setUrlParams(
                    $(this).attr('aria-controls'),
                    $('.tab-wholesaler #' + $(this).attr('aria-controls') + ' .nav-tab-wholesaler-option li.active a').attr('aria-controls')
                );
            });

            $('.nav-tab-wholesaler-option a').on('click', function() {
                setUrlParams(
                    $('.nav-tab-wholesaler li.active a').attr('aria-controls'),
                    $(this).attr('aria-controls')
                );
            });
        });

        function getUrlParam(parameter) {
            var value = false;
            if (window.location.href.indexOf(parameter) > -1) {
                value = getUrlVars()[parameter];
            }

            return (value !== '' ? value : false);
        }

        function getUrlVars() {
            var vars = [];
            window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m, key, value) {
                vars[key] = value;
            });

            return vars;
        }

        function setUrlParams(tabWholesaler, tabWholesalerOption) {
            tabWholesalerOption = (typeof tabWholesalerOption === 'undefined' ? '' : tabWholesalerOption);

            $('input[name="tabWholesaler"]').val(tabWholesaler);
            $('input[name="tabWholesalerOption"]').val(tabWholesalerOption);

            history.replaceState(null, '', wholesalersAdminLink + '&tabWholesaler=' + tabWholesaler + '&tabWholesalerOption=' + tabWholesalerOption);
        }
    </script>

    {if version_compare($smarty.const._PS_VERSION_, '1.7.0.0', '<') && (isset($tinymce) && $tinymce)}
        <script type="text/javascript">
            var iso = '{$iso|addslashes}';
            var pathCSS = '{$smarty.const._THEME_CSS_DIR_|addslashes}';
            var ad = '{$ad|addslashes}';

            $(document).ready(function(){
                tinySetup({
                    editor_selector :"autoload_rte"
                });
            });
        </script>
    {/if}

    {$smarty.block.parent}
{/block}

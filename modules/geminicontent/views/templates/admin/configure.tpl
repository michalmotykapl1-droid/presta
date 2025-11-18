{* Ustawienia Gemini API *}
<form method="post" action="{$current_controller_url|escape:'html':'UTF-8'}" enctype="multipart/form-data" class="form-horizontal">
  <div class="panel">
    <h3>{l s='Ustawienia Gemini API' mod='geminicontent'}</h3>
    <div class="form-group">
      <label class="control-label col-lg-3">{l s='Klucz API Gemini' mod='geminicontent'}</label>
      <div class="col-lg-9">
        <input type="text" name="GEMINI_API_KEY" value="{$gemini_api_key|escape:'html':'UTF-8'}" class="form-control" />
      </div>
    </div>
    <div class="form-group">
      <label class="control-label col-lg-3">
        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Włącz, aby testować moduł bez zużywania limitów API.' mod='geminicontent'}">
          {l s='Tryb deweloperski (Mock Mode)' mod='geminicontent'}
        </span>
      </label>
      <div class="col-lg-9">
        <span class="switch prestashop-switch fixed-width-lg">
          <input type="radio" name="GEMINI_MOCK_MODE" id="GEMINI_MOCK_MODE_on" value="1" {if $gemini_mock_mode == 1}checked="checked"{/if} />
          <label for="GEMINI_MOCK_MODE_on" class="radioCheck">{l s='Tak' mod='geminicontent'}</label>
          <input type="radio" name="GEMINI_MOCK_MODE" id="GEMINI_MOCK_MODE_off" value="0" {if $gemini_mock_mode == 0}checked="checked"{/if} />
          <label for="GEMINI_MOCK_MODE_off" class="radioCheck">{l s='Nie' mod='geminicontent'}</label>
          <a class="slide-button btn"></a>
        </span>
      </div>
    </div>

    <h3>{l s='Szablony promptów' mod='geminicontent'}</h3>
    <div class="form-group">
      <label class="control-label col-lg-3">{l s='Prompt creative' mod='geminicontent'}</label>
      <div class="col-lg-9">
        <textarea name="GEMINI_PROMPT_CREATIVE" class="form-control" rows="5">{$gemini_prompt_creative|escape:'html':'UTF-8'}</textarea>
      </div>
    </div>
    <div class="form-group">
      <label class="control-label col-lg-3">{l s='Prompt formatter' mod='geminicontent'}</label>
      <div class="col-lg-9">
        <textarea name="GEMINI_PROMPT_FORMATTER" class="form-control" rows="5">{$gemini_prompt_formatter|escape:'html':'UTF-8'}</textarea>
      </div>
    </div>
    <div class="form-group">
      <label class="control-label col-lg-3">{l s='Prompt SEO' mod='geminicontent'}</label>
      <div class="col-lg-9">
        <textarea name="GEMINI_PROMPT_SEO" class="form-control" rows="5">{$gemini_prompt_seo|escape:'html':'UTF-8'}</textarea>
      </div>
    </div>
    <div class="form-group">
      <label class="control-label col-lg-3">{l s='Prompt "Wszystko"' mod='geminicontent'}</label>
      <div class="col-lg-9">
        <textarea name="GEMINI_PROMPT_ALL_IN_ONE" id="gemini-prompt-all-in-one" class="form-control" rows="8">{$gemini_prompt_all_in_one|escape:'html':'UTF-8'}</textarea>
        <p class="help-block">{l s='Zostaw puste, aby moduł generował ten prompt automatycznie. Wypełnij, aby używać własnej, stałej wersji.' mod='geminicontent'}</p>
      </div>
    </div>

    <h3>{l s='Ustawienia importu CSV' mod='geminicontent'}</h3>
    <div class="form-group">
      <label class="control-label col-lg-3">
        <span class="label-tooltip" data-toggle="tooltip" title="{l s='Zdefiniuj, jak nowy opis ma być połączony ze starym podczas importu z CSV.' mod='geminicontent'}">
            {l s='Szablon łączenia opisów' mod='geminicontent'}
        </span>
      </label>
      <div class="col-lg-9">
        <textarea name="GEMINI_MERGE_TEMPLATE" class="form-control" rows="3">{$gemini_merge_template|default:'{new_content}<hr><h2>Opis oryginalny</h2>{old_content}'|escape:'html':'UTF-8'}</textarea>
        <p class="help-block">
            {l s='Dostępne zmienne:' mod='geminicontent'}
            <code>{literal}{new_content}{/literal}</code> - {l s='treść z kolumny description_ai' mod='geminicontent'},
            <code>{literal}{old_content}{/literal}</code> - {l s='istniejący opis produktu.' mod='geminicontent'}
        </p>
      </div>
    </div>

    <div class="panel-footer">
      <button type="submit" name="submitGeminiSettings" class="btn btn-primary pull-right">
        <i class="process-icon-save"></i> {l s='Zapisz ustawienia' mod='geminicontent'}
      </button>
    </div>
  </div>
</form>

{* Panel statystyk *}
<div class="panel">
    <h3><i class="icon-bar-chart"></i> {l s='Statystyki' mod='geminicontent'}</h3>
    <div class="row">
        <div class="col-lg-4">
            <div class="alert alert-danger">{l s='Brak opisu i SEO:' mod='geminicontent'} <strong>{$gemini_stats.missing_both|intval}</strong></div>
        </div>
        <div class="col-lg-4">
            <div class="alert alert-warning">{l s='Brak tylko opisu:' mod='geminicontent'} <strong>{$gemini_stats.missing_desc|intval}</strong></div>
        </div>
        <div class="col-lg-4">
            <div class="alert alert-info">{l s='Brak tylko SEO:' mod='geminicontent'} <strong>{$gemini_stats.missing_seo|intval}</strong></div>
        </div>
    </div>
</div>

{* Produkty do przetworzenia *}
<div class="panel">
  <h3>{l s='Produkty do przetworzenia' mod='geminicontent'}</h3>
  <form method="post" action="{$current_controller_url|escape:'html':'UTF-8'}" id="product-list-form">
    <table class="table table-bordered table-hover">
      <thead>
        <tr>
          <th class="text-center"><input type="checkbox" id="checkall" /></th>
          <th>{l s='ID' mod='geminicontent'}</th>
          <th>{l s='Nazwa produktu' mod='geminicontent'}</th>
          <th>{l s='SKU' mod='geminicontent'}</th>
          <th>{l s='EAN-13' mod='geminicontent'}</th>
          <th>{l s='Opis wygenerowany' mod='geminicontent'}</th>
          <th>{l s='SEO wygenerowane' mod='geminicontent'}</th>
          <th>{l s='Akcje' mod='geminicontent'}</th>
        </tr>
      </thead>
      <tbody>
        {foreach from=$products_to_process item=product}
          <tr id="product-row-{$product.id_product}">
            <td class="text-center"><input type="checkbox" name="product_ids[]" value="{$product.id_product}" class="js-product-checkbox" /></td>
            <td class="text-center">{$product.id_product}</td>
            <td>{$product.name}</td>
            <td>{$product.reference}</td>
            <td>{$product.ean13}</td>
            <td class="text-center">
              {if $product.description_generated}
                <i class="icon-check text-success"></i>
              {else}
                <i class="icon-times text-danger"></i>
              {/if}
            </td>
            <td class="text-center">
              {if $product.seo_generated}
                <i class="icon-check text-success"></i>
              {else}
                <i class="icon-times text-danger"></i>
              {/if}
            </td>
            <td class="text-center">
              <div class="btn-group-action">
                <div class="btn-group">
                  <button type="button" class="btn btn-sm btn-default js-generate-description" data-id-product="{$product.id_product}"><i class="icon-magic"></i> {l s='Opis' mod='geminicontent'}</button>
                  <button type="button" class="btn btn-sm btn-default js-generate-seo" data-id-product="{$product.id_product}"><i class="icon-search"></i> {l s='SEO' mod='geminicontent'}</button>
                  <button type="button" class="btn btn-sm btn-primary js-generate-all" data-id-product="{$product.id_product}"><i class="icon-bolt"></i> {l s='Wszystko' mod='geminicontent'}</button>
                </div>
              </div>
            </td>
          </tr>
        {/foreach}
      </tbody>
    </table>
  </form>
</div>

{* Modal do podglądu i zapisu *}
<div class="modal fade" id="geminiPreviewModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">{l s='Podgląd i zapis' mod='geminicontent'}</h4>
      </div>
      <div class="modal-body">
        <div id="gemini-seo-fields" style="display:none; margin-bottom:15px;">
          <div class="form-group">
            <label>{l s='Meta Tytuł' mod='geminicontent'}</label>
            <input type="text" id="gemini-meta-title" class="form-control"/>
          </div>
          <div class="form-group">
            <label>{l s='Meta Opis' mod='geminicontent'}</label>
            <textarea id="gemini-meta-description" class="form-control" rows="3"></textarea>
          </div>
          <div class="form-group">
            <label>{l s='Przyjazny URL' mod='geminicontent'}</label>
            <input type="text" id="gemini-link-rewrite" class="form-control"/>
          </div>
          <div class="form-group">
            <label>{l s='Tagi' mod='geminicontent'}</label>
            <input type="text" id="gemini-tags" class="form-control"/>
          </div>
          <hr/>
        </div>
        <div id="gemini-description-fields">
          <ul class="nav nav-tabs" role="tablist">
            <li role="presentation" class="active"><a href="#preview" aria-controls="preview" role="tab" data-toggle="tab">{l s='Podgląd' mod='geminicontent'}</a></li>
            <li role="presentation"><a href="#source" aria-controls="source" role="tab" data-toggle="tab">{l s='Kod źródłowy (HTML)' mod='geminicontent'}</a></li>
          </ul>
          <div class="tab-content" style="margin-top:10px;">
            <div role="tabpanel" class="tab-pane active" id="preview" style="min-height:200px; border:1px solid #ddd; padding:15px;">
              <div id="gemini-preview-content"></div>
            </div>
            <div role="tabpanel" class="tab-pane" id="source">
              <textarea id="gemini-source-content" class="form-control" style="height:200px;"></textarea>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">{l s='Anuluj' mod='geminicontent'}</button>
        <button type="button" id="save-gemini-description" class="btn btn-primary">{l s='Zapisz' mod='geminicontent'}</button>
      </div>
    </div>
  </div>
</div>

{* Panel Importu / Eksportu CSV *}
<div class="panel">
    <h3><i class="icon-exchange"></i> {l s='Import / Eksport masowy (CSV)' mod='geminicontent'}</h3>
    <p class="alert alert-info">
        {l s='Użyj tej sekcji do masowej aktualizacji opisów produktów. Wyeksportuj plik, zmodyfikuj kolumnę "description" w programie takim jak Excel lub LibreOffice Calc, a następnie zaimportuj plik z powrotem.' mod='geminicontent'}
        <br><strong>{l s='Ważne:' mod='geminicontent'}</strong> {l s='Plik musi być zakodowany w UTF-8, a separatorem kolumn musi być średnik (;).' mod='geminicontent'}
    </p>

    <div class="row">
        {* Kolumna Eksportu *}
        <div class="col-lg-6">
            <h4>{l s='Eksportuj produkty do pliku CSV' mod='geminicontent'}</h4>
            <form action="{$current_controller_url|escape:'html':'UTF-8'}" method="post" class="form-horizontal">
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Zakres eksportu' mod='geminicontent'}</label>
                    <div class="col-lg-9">
                        <div class="radio">
                            <label>
                                <input type="radio" name="export_range" value="all" checked="checked">
                                {l s='Wszystkie produkty' mod='geminicontent'}
                            </label>
                        </div>
                        <div class="radio">
                            <label>
                                <input type="radio" name="export_range" value="limit">
                                {l s='Tylko pierwsze' mod='geminicontent'}
                                <input type="number" name="export_limit" value="100" style="width: 80px; display: inline-block;" class="form-control">
                                {l s='produktów' mod='geminicontent'}
                            </label>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Status produktów' mod='geminicontent'}</label>
                    <div class="col-lg-9">
                        <select name="export_status" class="form-control">
                            <option value="all">{l s='Wszystkie produkty' mod='geminicontent'}</option>
                            <option value="all_unprocessed">{l s='Tylko nieprzetworzone (brak opisu LUB SEO)' mod='geminicontent'}</option>
                            <option value="missing_desc">{l s='Tylko brak opisu' mod='geminicontent'}</option>
                            <option value="missing_seo">{l s='Tylko brak SEO' mod='geminicontent'}</option>
                            <option value="missing_both">{l s='Tylko brak opisu I SEO' mod='geminicontent'}</option>
                        </select>
                        <p class="help-block">{l s='Wybierz, jakie produkty chcesz wyeksportować na podstawie ich statusu przetwarzania przez moduł.' mod='geminicontent'}</p>
                    </div>
                </div>
                <div class="form-group">
                    <label class="control-label col-lg-3">
                        {l s='Prefiksy SKU (przed "_")' mod='geminicontent'}
                    </label>
                    <div class="col-lg-9">
                        <input type="text" name="sku_prefixes" class="form-control" placeholder="{l s='np. bp, ekowit, stew, nat' mod='geminicontent'}" />
                        <p class="help-block">
                            {l s='Wpisz listę prefiksów oddzielonych przecinkami; "_" dopiszemy automatycznie.' mod='geminicontent'}
                        </p>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-lg-9 col-lg-offset-3">
                        <button type="submit" name="submitExportCsv" class="btn btn-primary">
                            <i class="icon-download"></i> {l s='Pobierz plik CSV' mod='geminicontent'}
                        </button>
                    </div>
                </div>
            </form>
        </div>

        {* Kolumna Importu *}
        <div class="col-lg-6">
            <h4>{l s='Zaimportuj zmodyfikowany plik CSV' mod='geminicontent'}</h4>
            <form action="{$current_controller_url|escape:'html':'UTF-8'}" method="post" enctype="multipart/form-data" class="form-horizontal">
                <div class="form-group">
                    <label class="control-label col-lg-3">{l s='Plik CSV' mod='geminicontent'}</label>
                    <div class="col-lg-9">
                        <input type="file" name="import_csv_file" accept=".csv">
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-lg-9 col-lg-offset-3">
                         <button type="submit" name="submitImportCsv" class="btn btn-primary">
                            <i class="icon-upload"></i> {l s='Importuj i aktualizuj' mod='geminicontent'}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>


{* ============================================================================== *}
{* ZMIANA: Formularz do wgrania pliku dla funkcji "Dodaj tabelę..."             *}
{* ============================================================================== *}
<div class="panel">
    <div class="panel-heading">
        <i class="icon-table"></i> {l s='Dodaj tabelę „Skład” i „Wartości odżywcze” z pliku CSV (AUTO)' mod='geminicontent'}
    </div>
    <form method="post" action="{$current_controller_url|escape:'html':'UTF-8'}" enctype="multipart/form-data" class="form-horizontal">
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Plik CSV' mod='geminicontent'}</label>
            <div class="col-lg-9" style="padding-top: 7px;">
                <input type="file" name="gc_ingr_nutr_csv" class="form-control" accept=".csv" required>
                <p class="help-block">
                    {l s='CSV w UTF-8 z separatorem „;”. Wymagane kolumny:' mod='geminicontent'} <code>id_product, name, description, ingredients_src, nutrition_src</code>.
                </p>
            </div>
        </div>
        <div class="panel-footer">
            <button type="submit" name="submitGcIngrNutrPreview" class="btn btn-primary pull-right">
                <i class="icon-search"></i> {l s='Pokaż podgląd zmian' mod='geminicontent'}
            </button>
        </div>
    </form>
</div>
{* ============================================================================== *}


{* --- PANEL: Przetwarzanie masowe CSV z AI --- *}
<div class="panel">
    <h3><i class="icon-cogs"></i> {l s='Przetwarzanie masowe CSV z AI' mod='geminicontent'}</h3>
    <div class="alert alert-info">
        {l s='Wgraj plik CSV (wyeksportowany z tego modułu), zdefiniuj zadanie dla AI, a moduł przetworzy każdy wiersz i przygotuje nowy plik do pobrania z uzupełnionymi danymi.' mod='geminicontent'}
    </div>

    <form action="{$current_controller_url|escape:'html':'UTF-8'}&action=startCsvProcessing" method="post" enctype="multipart/form-data" class="form-horizontal" id="csv-ai-form">
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Plik CSV do przetworzenia' mod='geminicontent'}</label>
            <div class="col-lg-9" style="padding-top: 7px;">
                <input type="file" name="csv_process_file" accept=".csv" required>
            </div>
        </div>
        <div class="form-group">
            <label class="control-label col-lg-3">{l s='Zadanie (Prompt) dla AI' mod='geminicontent'}</label>
            <div class="col-lg-9">
                <textarea name="GEMINI_PROMPT_CSV_PROCESS" id="gemini-prompt-csv-process" class="form-control" rows="8">{$gemini_prompt_csv_process|escape:'html':'UTF-8'}</textarea>
                <button type="button" id="js-save-csv-prompt" class="btn btn-primary btn-sm" style="margin-top: 5px;">
                    <i class="icon-save"></i> {l s='Zapisz ten prompt do późniejszego użytku' mod='geminicontent'}
                </button>
                <button type="button" id="js-reset-csv-prompt" class="btn btn-default btn-sm" style="margin-top: 5px;">
                    <i class="icon-refresh"></i> {l s='Przywróć domyślny' mod='geminicontent'}
                </button>
                <p class="help-block">
                    {l s='Opisz, co AI ma zrobić z danymi z każdego wiersza. Możesz używać zmiennych:' mod='geminicontent'}
                    <code>{literal}{{csv_row_as_json}}{/literal}</code>.
                    <br>
                    {l s='Twoim celem jest wygenerowanie odpowiedzi w formacie JSON, która będzie zawierać klucze odpowiadające nagłówkom w pliku CSV, np. {"description": "nowy opis", "meta_title": "nowy tytuł"}.' mod='geminicontent'}
                </p>
            </div>
        </div>
        <div class="panel-footer">
            <button type="submit" name="submitProcessCsv" id="submit-process-csv" class="btn btn-primary pull-right">
                <i class="icon-play"></i> {l s='Rozpocznij przetwarzanie' mod='geminicontent'}
            </button>
        </div>
    </form>
</div>


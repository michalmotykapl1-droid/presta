{*
  CSV panel injected via displayAdminAfterHeader (non-destructive).
*}
<div class="panel">
  <div class="panel-heading">
    <i class="icon-upload"></i> Dodaj tabelę „Skład” i „Wartości odżywcze” z pliku CSV (AUTO)
  </div>

  {if isset($gc_ingr_nutr_errors) && $gc_ingr_nutr_errors|@count}
    <div class="alert alert-danger">
      <ul>
        {foreach from=$gc_ingr_nutr_errors item=e}
          <li>{$e|escape:'htmlall':'UTF-8'}</li>
        {/foreach}
      </ul>
    </div>
  {/if}

  <form method="post" enctype="multipart/form-data">
    <div class="form-group">
      <label class="control-label">{l s='Plik CSV' mod='geminicontent'}</label>
      <input type="file" name="gc_ingr_nutr_csv" class="form-control" accept=".csv" required>
      <p class="help-block">
        CSV w UTF-8 z separatorem „;”. Wymagane kolumny: <code>id_product, reference, name, description, ingredients_src, nutrition_src</code>.
      </p>
    </div>
    <button type="submit" name="submitGcIngrNutrPreview" class="btn btn-primary">
      <i class="icon-search"></i> Podgląd i wygeneruj nowy CSV
    </button>
    {if $gc_ingr_nutr_csv_url}
      <a href="{$gc_ingr_nutr_csv_url|escape:'htmlall':'UTF-8'}" class="btn btn-default" style="margin-left:8px;">
        <i class="icon-download"></i> Pobierz wygenerowany CSV
      </a>
    {/if}
  </form>
</div>

{if $gc_ingr_nutr_preview}
  <div class="panel">
    <div class="panel-heading">
      <i class="icon-search"></i> Podgląd importu z pliku CSV
    </div>
    {foreach from=$gc_ingr_nutr_preview item=row}
      <div class="well">
        <p><strong>Produkt:</strong> {$row.name|escape:'htmlall':'UTF-8'} <span class="label label-default">ID: {$row.id_product|escape}</span> <span class="label label-default">SKU: {$row.reference|escape}</span></p>
        <table class="table">
          <thead>
            <tr><th style="width:40%;">Stara zawartość (fragment)</th><th>Nowa zawartość (fragment)</th></tr>
          </thead>
          <tbody>
            <tr>
              <td><div style="max-height:130px; overflow:auto;">{$row.old_desc|escape:'htmlall':'UTF-8'}</div></td>
              <td><div style="max-height:220px; overflow:auto;">{$row.new_desc nofilter}</div></td>
            </tr>
          </tbody>
        </table>
      </div>
    {/foreach}
  </div>
{/if}

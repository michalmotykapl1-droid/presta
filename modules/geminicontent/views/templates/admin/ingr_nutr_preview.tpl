{* /modules/geminicontent/views/templates/admin/ingr_nutr_preview.tpl *}
{extends file="helpers/view/view.tpl"}

{block name="override_tpl"}
<style>
    .gc-preview-container .well {
        border-left: 3px solid #ddd;
        padding-bottom: 0;
    }
    .gc-preview-container .product-header {
        margin-bottom: 15px;
    }
    .gc-preview-container .preview-section {
        margin-bottom: 20px;
    }
    .gc-preview-container h4 {
        font-size: 1.1em;
        font-weight: 600;
        margin-top: 0;
        margin-bottom: 10px;
        padding-bottom: 5px;
        border-bottom: 1px solid #eee;
    }
    .gc-preview-container .new-content-wrapper {
        padding: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        background-color: #f9f9f9;
        max-height: 300px;
        overflow-y: auto;
    }
    .gc-preview-container .old-content-wrapper {
        max-height: 200px;
        overflow-y: auto;
        font-size: 12px;
        color: #777;
        white-space: pre-wrap;
        word-break: break-word;
        background: #fafafa;
        padding: 10px;
        border: 1px solid #eee;
        border-radius: 3px;
    }
    .gc-preview-container details > summary {
        cursor: pointer;
        font-weight: 600;
        color: #555;
        margin-bottom: 10px;
    }
</style>

<div class="panel">
    <h3><i class="icon-eye-open"></i> {l s='Podgląd zmian: Dodawanie tabel Skład/Wartości odżywcze' mod='geminicontent'}</h3>
</div>

<form method="post" action="{$current_controller_url|escape:'html':'UTF-8'}" id="form-confirm-import">
    <div class="alert alert-info">{l s='Sprawdź poniższy podgląd. Zaznacz produkty, które chcesz zaktualizować, a następnie kliknij "Zatwierdź i Zapisz". Zmiany zostaną zapisane bezpośrednio w bazie danych.' mod='geminicontent'}</div>

    <div class="panel gc-preview-container">
        <div class="panel-heading">
            <i class="icon-list"></i> {l s='Lista produktów do aktualizacji' mod='geminicontent'}
            <div class="pull-right">
                <input type="checkbox" id="checkall-ingr-nutr" checked="checked"> 
                <label for="checkall-ingr-nutr" style="font-weight: normal; cursor: pointer;">{l s='Zaznacz/Odznacz wszystko' mod='geminicontent'}</label>
            </div>
        </div>

        {if !empty($gc_ingr_nutr_preview)}
            {foreach from=$gc_ingr_nutr_preview item=row}
                <div class="well">
                    <div class="product-header">
                        <input type="checkbox" name="products_to_update[]" value="{$row.id_product|intval}" class="js-ingr-nutr-checkbox" checked="checked">
                        <strong>{l s='Produkt:' mod='geminicontent'}</strong> 
                        <a href="{$row.edit_link|escape:'html':'UTF-8'}" target="_blank">
                            {$row.name|escape:'htmlall':'UTF-8'} <i class="icon-external-link"></i>
                        </a>
                        <span class="label label-default">ID: {$row.id_product|escape:'html':'UTF-8'}</span> 
                        <span class="label label-default">SKU: {$row.reference|escape:'html':'UTF-8'}</span>
                    </div>

                    <div class="preview-section">
                        <h4>{l s='Nowa treść (podgląd z tabelami)' mod='geminicontent'}</h4>
                        <div class="new-content-wrapper">
                            {$row.new_desc nofilter}
                        </div>
                    </div>

                    <div class="preview-section">
                        <details>
                            <summary>{l s='Pokaż/Ukryj starą treść (bez formatowania)' mod='geminicontent'}</summary>
                            <div class="old-content-wrapper">
                                {$row.old_desc|escape:'htmlall':'UTF-8'}
                            </div>
                        </details>
                    </div>
                </div>
            {/foreach}
        {/if}
    </div>

    <div class="panel-footer">
        <a href="{$current_controller_url|escape:'html':'UTF-8'}&cancelGcIngrNutr=1" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Anuluj' mod='geminicontent'}</a>
        <button type="submit" name="submitConfirmGcIngrNutr" class="btn btn-primary pull-right">
            <i class="icon-save"></i> {l s='Zatwierdź i Zapisz Zaznaczone' mod='geminicontent'}
        </button>
    </div>
</form>

<script>
    $(document).ready(function() {
        $('#checkall-ingr-nutr').on('click', function() {
            $('.js-ingr-nutr-checkbox').prop('checked', $(this).prop('checked'));
        });
    });
</script>
{/block}
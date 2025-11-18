{*
 * Ścieżka do pliku: /modules/geminicontent/views/templates/admin/product_preview_modal_content.tpl
 *}

<div class="product-preview-modal-container">
    <ul class="nav nav-tabs" role="tablist">
        <li role="presentation" class="active">
            <a href="#preview-new" aria-controls="preview-new" role="tab" data-toggle="tab">{l s='Podgląd po zmianach' mod='geminicontent'}</a>
        </li>
        <li role="presentation">
            <a href="#preview-current" aria-controls="preview-current" role="tab" data-toggle="tab">{l s='Wersja aktualna' mod='geminicontent'}</a>
        </li>
    </ul>

    <div class="tab-content" style="padding-top: 20px;">
        {* Zakładka z wersją po zmianach *}
        <div role="tabpanel" class="tab-pane active" id="preview-new">
            <h4>{l s='Dane SEO' mod='geminicontent'}</h4>
            <div class="well">
                <p><strong>{l s='Meta Tytuł:' mod='geminicontent'}</strong> {$preview_product_data.meta_title|escape:'html':'UTF-8'}</p>
                <p><strong>{l s='Meta Opis:' mod='geminicontent'}</strong> {$preview_product_data.meta_description|escape:'html':'UTF-8'}</p>
                <p><strong>{l s='Przyjazny URL:' mod='geminicontent'}</strong> .../{$preview_product_data.link_rewrite|escape:'html':'UTF-8'}.html</p>
                <p><strong>{l s='Tagi:' mod='geminicontent'}</strong> {$preview_product_data.tags|escape:'html':'UTF-8'}</p>
            </div>
            
            <h4>{l s='Opis produktu' mod='geminicontent'}</h4>
            <div class="product-description-preview">
                {$preview_product_data.description nofilter}
            </div>
        </div>

        {* Zakładka z wersją aktualną *}
        <div role="tabpanel" class="tab-pane" id="preview-current">
            <h4>{l s='Dane SEO' mod='geminicontent'}</h4>
            <div class="well">
                 <p><strong>{l s='Meta Tytuł:' mod='geminicontent'}</strong> {$current_product_data.meta_title|escape:'html':'UTF-8'}</p>
                <p><strong>{l s='Meta Opis:' mod='geminicontent'}</strong> {$current_product_data.meta_description|escape:'html':'UTF-8'}</p>
                <p><strong>{l s='Przyjazny URL:' mod='geminicontent'}</strong> .../{$current_product_data.link_rewrite|escape:'html':'UTF-8'}.html</p>
                <p><strong>{l s='Tagi:' mod='geminicontent'}</strong> {$current_product_data.tags|escape:'html':'UTF-8'}</p>
            </div>
            
            <h4>{l s='Opis produktu' mod='geminicontent'}</h4>
            <div class="product-description-preview">
                {$current_product_data.description nofilter}
            </div>
        </div>
    </div>
</div>

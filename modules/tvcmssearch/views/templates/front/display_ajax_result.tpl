{strip}
<div class="tvcmssearch-dropdown">
    <div class="tvsearch-dropdown-close-wrapper tvsearch-dropdown-close">
        <i class="material-icons">&#xe5cd;</i>
    </div>

    {* Główny, dwukolumnowy kontener na kategorie i produkty *}
    <div class="tvsearch-results-container">
        {* Lewa kolumna na filtry kategorii *}
        <div class="tvsearch-filter-column">
            
            {* POPRAWKA 1: Link "Wyczyść filtry" przeniesiony na górę *}
       
     <div class="tvsearch-reset-wrapper">
                <a href="#" class="tvsearch-reset-filters" style="display: none;">{l s='Wyczyść filtry' mod='tvcmssearch'}</a>
            </div>

            {* Lista kategorii *}
            {if $showCategories && !empty($options.categories)}
                <h4>{l s='Categories' mod='tvcmssearch'}</h4>
           
     <ul class="tvsearch-category-list">
                    {foreach from=$options.categories item=category}
                        <li class="tvsearch-category-item">
                            <a href="#" class="tvsearch-category-link" data-id-category="{$category.id_category|escape:'htmlall':'UTF-8'}" data-category-url="{$category.url|escape:'htmlall':'UTF-8'}">
               
                 {$category.name|escape:'htmlall':'UTF-8'}
                                {if $showCatCount && isset($category.product_count)}
                                    <span class="tvsearch-product-count">({$category.product_count})</span>
           
                     {/if}
                            </a>
                        </li>
                    {/foreach}
       
         </ul>
            {/if}

            {* Lista kategorii dietetycznych *}
            {if $showDiet && !empty($options.diet_categories)}
                <h4 class="tvsearch-category-title">{l s='Preferencje dietetyczne' mod='tvcmssearch'}</h4>
                <ul class="tvsearch-category-list">
           
         {foreach from=$options.diet_categories item=diet_category}
                        <li class="tvsearch-category-item">
                            <a href="#" class="tvsearch-category-link" data-id-category="{$diet_category.id_category|escape:'htmlall':'UTF-8'}" data-category-url="{$diet_category.url|escape:'htmlall':'UTF-8'}">
                                
{$diet_category.name|escape:'htmlall':'UTF-8'}
                                {if $showCatCount && isset($diet_category.product_count)}
                                    <span class="tvsearch-product-count">({$diet_category.product_count})</span>
                            
    {/if}
                            </a>
                        </li>
                    {/foreach}
                </ul>
        
    {/if}

            
            {* ================================================================ *}
            {* START MODYFIKACJI: Przeniesienie filtrów do lewej kolumny *}
            {* ================================================================ *}
            
            {* Sekcja na filtry dodatkowe - przeniesiona z dołu *}
            {* Zmieniono klasę z 'tvsearch-fullwidth-filter-section' na 'tvsearch-extra-filter-section' *}
            <div class="tvsearch-extra-filter-section">
                <h4 class="tvsearch-filter-toggle">
                    {l s='Filtry dodatkowe' mod='tvcmssearch'}
                    <span class="tvsearch-toggle-icon">&#x25BC;</span>
                </h4>
                <div class="tvsearch-feature-grid-wrapper" style="display: none;">
                    {if $showDietFilter && !empty($diet_features)}
                        <ul class="tvsearch-feature-grid">
                            {foreach from=$diet_features item=feature}
                                <li class="tvsearch-feature-item">
                                    <label>
                                        <input type="checkbox" class="tvsearch-feature-filter" data-id-feature="{$feature.id_feature|escape:'htmlall':'UTF-8'}" />
                                        <span>{$feature.name|escape:'htmlall':'UTF-8'}</span>
                                    </label>
                                </li>
                            {/foreach}
                        </ul>
                    {else}
                         <p>{l s='Brak filtrów dla tych wyników.' mod='tvcmssearch'}</p>
                    {/if}
                </div>
            </div>
            
            {* ================================================================ *}
            {* KONIEC MODYFIKACJI *}
            {* ================================================================ *}


            {* POPRAWKA 2: Przywrócony przycisk "Pokaż produkty z tej kategorii" *}
            <div class="tvsearch-show-filtered-wrapper" style="display: none;
margin-top: 20px;">
                <button type="button" class="tvsearch-show-filtered-btn btn btn-secondary">
                    <span>{l s='Pokaż produkty z tej kategorii' mod='tvcmssearch'}</span>
                </button>
            </div>

        </div>

        {* Prawa kolumna na produkty *}
    
    <div class="tvsearch-products-column">
             <div class="tvsearch-actions-wrapper">
                <div class="tvsearch-show-all-wrapper">
                    <button type="button" class="btn btn-secondary tvsearch-show-all-for-term-btn">
                        {l s='Pokaż wszystkie produkty dla:' mod='tvcmssearch'} <strong class="tvsearch-term-display"></strong>
         
           </button>
                </div>
            </div>
        
            <div class="tvsearch-all-dropdown-wrapper">
                {if !empty($result_data.html)}
                    {$result_data.html nofilter}
  
              {else}
                    <p class="no-results">{l s='No results found.'
mod='tvcmssearch'}</p>
                {/if}
            </div>
            
            <div class="tvsearch-more-search-wrapper">
                {if $result_data.total > $products|@count}
                    <button type="button" class="tvsearch-show-all-results-btn btn btn-primary">
    
                    <span>{l s='Więcej wyników' mod='tvcmssearch'}</span>
                    </button>
                {/if}
            </div>
        </div>
    </div>

    {* Sekcja na filtry dodatkowe - USUNIĘTA STĄD I PRZENIESIONA DO LEWEJ KOLUMNY *}
    

        
        
 
                               
                 
   
            

        
</div>
{/strip}
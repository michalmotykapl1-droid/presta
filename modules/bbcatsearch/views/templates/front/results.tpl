{* BB Category Search – wyniki + filtry diety (HTML + CSS). 
   Uwaga: wstrzykiwane przez innerHTML, więc JS jest podpinany w category.tpl (delegowane zdarzenia).
*}
<div class="bbcatsearch-panel">
  <div class="bbcatsearch-panel__head">
      <span>
        {l s='Przeszukiwana kategoria' mod='bbcatsearch'}:
        <strong>{$category_name|escape:'html':'UTF-8'}</strong>
        · {if $scope_children}{l s='kategoria + podkategorie' mod='bbcatsearch'}{else}{l s='tylko ta kategoria' mod='bbcatsearch'}{/if}
        — {l s='Wyświetlam' mod='bbcatsearch'} {$shown} {l s='z' mod='bbcatsearch'} {$total_all}
        {if $searchTerm && $searchTerm|trim ne ''}
          · {l s='fraza' mod='bbcatsearch'}: "<strong>{$searchTerm|escape:'html':'UTF-8'}</strong>"
        {/if}
      </span>
      <button class="bbcatsearch-close" type="button" aria-label="Zamknij">×</button>
    </div>
  <div class="bbcatsearch-filters" id="bbcat-filters">
      <div class="bbcat-filters__title">{l s='Doprecyzuj: preferencje diety' mod='bbcatsearch'}</div>
      <div class="bbcat-filters__body">
        {foreach from=$filters item=f}
          <label class="bb-fcheck
                        {if $f.checked} bb-fcheck--on{/if}
                        {if isset($f.enabled) && not $f.enabled} bb-fcheck--disabled{/if}">
            <input class="bb-fcheck__input"
                   type="checkbox"
                   value="{$f.id_feature|intval}"
                   {if $f.checked}checked="checked"{/if}
                   {if isset($f.enabled) && not $f.enabled}disabled="disabled"{/if} />
            <span class="bb-fcheck__label">{$f.name|escape:'html':'UTF-8'}</span>
          </label>
        {/foreach}
      </div>
    </div>

    
  {if isset($bb_items) && $bb_items && count($bb_items)}
<div class="bbcatsearch-grid">
      {foreach from=$bb_items item=it}
        <div class="bbcatsearch-card">
          <a class="bbcatsearch-card__img" href="{$it.url|escape:'html':'UTF-8'}" title="{$it.name|escape:'html':'UTF-8'}">
            {if $it.img}
              <img src="{$it.img|escape:'html':'UTF-8'}" alt="{$it.name|escape:'html':'UTF-8'}" loading="lazy" />
            {else}
              <span class="bb-noimg">IMG</span>
            {/if}
            <div class="bbcatsearch-flags">
              {if $it.flags.sale}<span class="bbcatsearch-flag flag-sale">{l s='Wyprzedaż' mod='bbcatsearch'}</span>{/if}
              {if $it.flags.short_date}<span class="bbcatsearch-flag flag-shortdate">{l s='Krótka data' mod='bbcatsearch'}</span>{/if}
            </div>
          </a>
          <div class="bbcatsearch-card__name">
            <a href="{$it.url|escape:'html':'UTF-8'}">{$it.name|escape:'html':'UTF-8'}</a>
          </div>
          <div class="bb-pricewrap">
            <span class="bb-price-current">{$it.price nofilter}</span>
            {if $it.old_price}
              <span class="bb-price-old">{$it.old_price nofilter}</span>
            {/if}
          </div>
        </div>
      {/foreach}
    </div>
  </div>

  
  {else}
    <div class="bbcatsearch-empty">
      {l s='Brak wyników dla wybranych kryteriów. Odznacz wybrane filtry lub zmień frazę.' mod='bbcatsearch'}
    </div>
  {/if}
</div>

{literal}
  <style>
    .bbcatsearch-panel{position:relative;z-index:30;border:1px solid #e5e5e5;border-radius:10px;background:#fff;padding:12px 12px 16px;margin-top:8px;box-shadow:0 4px 16px rgba(0,0,0,.06)}
    .bbcatsearch-panel__head{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:6px 8px;border-bottom:1px solid #f0f0f0;margin-bottom:8px;font-size:.95rem}
    .bbcatsearch-close{background:transparent;border:0;font-size:20px;line-height:1;cursor:pointer}

    .bbcatsearch-filters{padding:6px 8px 12px;border-bottom:1px solid #f4f4f4;margin-bottom:8px}
    .bbcat-filters__title{font-weight:600;margin-bottom:6px}
    .bbcat-filters__body{display:flex;flex-wrap:wrap;gap:8px}
    .bb-fcheck{display:inline-flex;align-items:center;gap:6px;background:#fafafa;border:1px solid #eee;border-radius:999px;padding:6px 10px;cursor:pointer;user-select:none}
    .bb-fcheck__input{accent-color:#16a34a;width:16px;height:16px}
    .bb-fcheck__label{font-size:.92rem}

    .bbcatsearch-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:12px}
    @media (max-width:1200px){.bbcatsearch-grid{grid-template-columns:repeat(4,minmax(0,1fr));}}
    @media (max-width:992px){.bbcatsearch-grid{grid-template-columns:repeat(3,minmax(0,1fr));}}
    @media (max-width:768px){.bbcatsearch-grid{grid-template-columns:repeat(2,minmax(0,1fr));}}
    @media (max-width:420px){.bbcatsearch-grid{grid-template-columns:repeat(1,minmax(0,1fr));}}

    .bbcatsearch-card{position:relative;border:1px solid #eee;border-radius:10px;overflow:hidden;background:#fff;display:flex;flex-direction:column;padding:8px}
    .bbcatsearch-card__img{display:block;position:relative;aspect-ratio:1/1;border-radius:8px;overflow:hidden;background:#f8f8f8}
    .bbcatsearch-card__img img{width:100%;height:100%;object-fit:cover;display:block}
    .bbcatsearch-flags{position:absolute;top:8px;left:8px;display:flex;gap:6px;flex-wrap:wrap;z-index:2}
    .bbcatsearch-flag{display:inline-block;padding:4px 8px;border-radius:999px;color:#fff;font-weight:700;font-size:.72rem;box-shadow:0 2px 6px rgba(0,0,0,.15);text-transform:uppercase;letter-spacing:.3px}
    .bbcatsearch-flag.flag-sale{background:#e53935}
    .bbcatsearch-flag.flag-shortdate{background:#ff6d00}

    .bbcatsearch-card__name{min-height:48px;line-height:1.2;margin-top:6px}
    .bbcatsearch-card__name a{text-decoration:none;color:#222}

    .bb-pricewrap{display:flex;align-items:baseline;justify-content:center;gap:8px;margin-top:4px}
    .bb-price-current{font-weight:800;font-size:1.14rem;line-height:1}
    .bb-price-old{text-decoration:line-through;opacity:.6;font-size:.96rem}
  
    .bb-fcheck--disabled {
      opacity: .35;
      cursor: default;
    }
    .bb-fcheck--disabled .bb-fcheck__label {
      pointer-events: none;
    }
    .bb-fcheck--disabled .bb-fcheck__input {
      cursor: not-allowed;
    }
</style>
  {/literal}

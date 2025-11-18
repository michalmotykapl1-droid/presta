{**
 * 2007-2025 PrestaShop
 * Category listing template with BB Category Search on top
 *
 * @UWAGA: To jest poprawna, POŁĄCZONA wersja, która zawiera
 * kod Twojego motywu (dla podkategorii) ORAZ kod modułu bbcatsearch
 * + poprawkę na autodoładowanie produktów (infinite scroll).
 *}
{strip}
{extends file='catalog/listing/product-list.tpl'}

{block name='product_list_header'}

  {* === SEKCJA 1: Oryginalny kod motywu (Banner, Opis) === *}
  {if Configuration::get('TVCMSCAT_BANNER_STATUS')}
    <div class="block-category card card-block clearfix tv-category-block-wrapper">
      {if !empty($category.image) && $category.image.large.url}
        <div class="tv-category-cover">
          <img
            src="{$category.image.large.url}"
            width="{$category.image.large.width}"
            height="{$category.image.large.height}"
            alt="{if !empty($category.image.legend)}{$category.image.legend}{else}{$category.name}{/if}"
            class="tv-img-responsive"
            loading="lazy"
          />
        </div>
      {/if}

      {if !empty($category.image.large.url)}
        <div class="tv-all-page-main-title-wrapper">
          <div class="tv-all-page-main-title">{$category.name}</div>
        </div>
      {/if}

      {if !empty($category.description)}
        <div id="category-description" class="text-muted">{$category.description nofilter}</div>
      {/if}
    </div>
  {/if}

  {* === SEKCJA 2: Oryginalny kod motywu (Podkategorie) === *}
  {if isset($subcategories) && count($subcategories) > 0}
    {if (isset($display_subcategories) && $display_subcategories eq 1) || !isset($display_subcategories)}
      <div class='tv-category-main-div clearfix'>
        <div class="tv-sub-category-title-wrapper">
          <div class="tv-sub-category-title">{l s='Subcategory' d='Shop.Theme.Catalog'}</div>
        </div>

        <div class="tvcategory-name-image row">
          {foreach from=$subcategories item=subcategory}
            <div class="tv-sub-category-wrapper col-md-3 col-sm-4 col-xs-6">
              <div class="tv-sub-category-inner">
                <div class="tv-category-image">
                  <a
                    href="{$link->getCategoryLink($subcategory.id_category, $subcategory.link_rewrite)|escape:'html':'UTF-8'}"
                    title="{$subcategory.name|escape:'html':'UTF-8'}"
                    class="img"
                  >
                    {if $subcategory.id_category && is_numeric($subcategory.id_image)}
                      <img
                        class="replace-2x tv-img-responsive"
                        src="{$link->getCatImageLink($subcategory.link_rewrite, $subcategory.id_category, 'small_default')|escape:'html':'UTF-8'}"
                        width="{$subcategory.image.bySize.small_default.width}"
                        height="{$subcategory.image.bySize.small_default.height}"
                        alt="{$subcategory.name|escape:'html':'UTF-8'}"
                        loading="lazy"
                      />
                    {else}
                      <div class="category-text-tile">
                        <span class="category-name">{$subcategory.name|escape:'html':'UTF-8'}</span>
                      </div>
                    {/if}
                  </a>
                </div>

                {if $subcategory.id_category && is_numeric($subcategory.id_image)}
                  <div class="tvcategory-name">
                    <a class="category-name"
                       href="{$link->getCategoryLink($subcategory.id_category, $subcategory.link_rewrite)|escape:'html':'UTF-8'}">
                      {$subcategory.name|escape:'html':'UTF-8'}
                    </a>
                  </div>
                {/if}
              </div>
            </div>
          {/foreach}
        </div>
      </div>
    {/if}
  {/if}

  {* === SEKCJA 3: Kod modułu 'bb-cat-search' === *}
  <div id="bb-cat-search"
     class="bb-cat-search"
     data-category-id="{$category.id|intval}"
     data-ajax-url="{$link->getModuleLink('bbcatsearch','ajax',[], true)|escape:'html':'UTF-8'}"
     data-limit="0"
     data-with-children="1"
     data-img-type="home_default">
    <label class="bb-cat-search__label">{l s='Szukaj w tej kategorii' d='Shop.Theme.Catalog'}</label>

    <div class="bb-cat-search__box">
      <input id="bb-cat-search-input"
             class="bb-cat-search__input"
             type="search"
             placeholder="{l s='np. czekolada' d='Shop.Theme.Catalog'}"
             autocomplete="off"
             minlength="2" />
      <button id="bb-cat-search-clear" class="bb-cat-search__clear" type="button" aria-label="Wyczyść">✕</button>
    </div>

    <div id="bb-cat-search-results" class="bbcatsearch-result" style="display:none;"></div>
  </div>

{literal}
<style>
  .bbcatsearch-result{position:relative;z-index:25;margin-top:.5rem}
  .bb-loading{padding:24px;text-align:center;font-weight:600}
</style>

<!-- [BB] ANTI-INFINITE-AUTOLOAD: ukryj strażnika paginacji do pierwszej interakcji -->
<script>
(function(){
  var userActed = false;
  function markActed(){ userActed = true; enable(); }
  ['scroll','wheel','touchstart','keydown','pointerdown'].forEach(function(ev){
    window.addEventListener(ev, markActed, {passive:true, once:true});
  });

  var selectors = [
    '.js-infinite-scroll', '.js-infinite', '.js-infinite-loader',
    '.infinite-scroll', '.infinite-loader',
    'nav.pagination', '.pagination', '.tv-pagination',
    '#js-product-list-bottom', '.product_list_bottom'
  ];

  var sentinels = [];
  function hide(el){
    if(!el || el.dataset.bbHold === '1') return;
    el.dataset.bbHold = '1';
    el.style.visibility   = 'hidden';
    el.style.height       = '0';
    el.style.overflow     = 'hidden';
    el.style.pointerEvents= 'none';
    sentinels.push(el);
  }
  function findAll(){
    selectors.forEach(function(sel){
      document.querySelectorAll(sel).forEach(hide);
    });
  }
  function enable(){
    if(!userActed) return;
    sentinels.forEach(function(el){
      if(el && el.dataset && el.dataset.bbHold === '1'){
        el.style.visibility   = '';
        el.style.height       = '';
        el.style.overflow     = '';
        el.style.pointerEvents= '';
        delete el.dataset.bbHold;
      }
    });
    if(mo){ try{ mo.disconnect(); }catch(e){} }
  }

  // początkowe ukrycie + obserwacja DOM (strażnik może się pojawić później)
  findAll();
  var mo = new MutationObserver(function(){
    if(userActed) return;
    findAll();
  });
  try{
    mo.observe(document.documentElement, {childList:true, subtree:true});
  }catch(e){}
})();
</script>

<!-- [BB] GŁÓWNY SKRYPT WYSZUKIWARKI -->
<script>
(function(){
  var box = document.getElementById('bb-cat-search');
  if(!box) return;
  var DBG = !!window.localStorage && localStorage.getItem('BB_CAT_DEBUG') === '1';

  var input   = document.getElementById('bb-cat-search-input');
  var clearBt = document.getElementById('bb-cat-search-clear');
  var results = document.getElementById('bb-cat-search-results');

  var catId   = box.getAttribute('data-category-id');
  var ajaxUrl = box.getAttribute('data-ajax-url');

  var limit  = box.getAttribute('data-limit') || '0';
  var withCh = box.getAttribute('data-with-children') || '1';
  var imgType= box.getAttribute('data-img-type') || 'home_default';

  function pickContainer(){
    var cands = [
      '#js-product-list', '.products', '#products', '.product_list', '#category-products',
      '.tv-product-wrapper', '.tvcms-product-wrapper', '.tv-category-product-list',
      '.tvcms-product-list', '.tvcms-product', '.tvcmsproduct', '.tvproducts', '.tv-product-grid'
    ];
    for (var i=0;i<cands.length;i++){
      var el = document.querySelector(cands[i]);
      if(el){ return {el:el, sel:cands[i]}; }
    }
    return {el:null, sel:null};
  }
  var picked = pickContainer();
  var productList = picked.el;
  var productListSelector = picked.sel;
  var replaceList = !!productList;
  var originalHTML = productList ? productList.innerHTML : '';

  if(DBG){
    console.log('[BB_CAT] container', {replaceList:replaceList, selector:productListSelector});
  }

  // 1) STARY mechanizm – event z results.tpl (jeśli <script> w innerHTML się wykona)
  window.bbActiveFeatures = [];
  window.addEventListener('bbcat:filters', function(e){
    window.bbActiveFeatures = (e.detail && Array.isArray(e.detail.features)) ? e.detail.features : [];
    if(DBG){ console.log('[BB_CAT] filters change (customEvent)', window.bbActiveFeatures); }
    doSearch(true);
  });

  // 2) NOWY mechanizm – DELEGOWANE ZDARZENIA na dokumencie (działa zawsze przy innerHTML)
  document.addEventListener('change', function(e){
    var t = e.target;
    if(!t || !t.classList || !t.classList.contains('bb-fcheck__input')) return;
    var checked = document.querySelectorAll('#bbcat-filters .bb-fcheck__input:checked');
    var arr = [];
    checked.forEach(function(i){ var v=parseInt(i.value,10); if(!isNaN(v)) arr.push(v); });
    window.bbActiveFeatures = arr;
    if(DBG){ console.log('[BB_CAT] filters change (delegated)', window.bbActiveFeatures); }
    doSearch(true);
  }, true);

  // close panel (delegated)
  document.addEventListener('click', function(e){
    var btn = e.target.closest('.bbcatsearch-close');
    if(!btn) return;
    restoreList();
  }, true);

  function debounce(fn, ms){ var t; return function(){ clearTimeout(t); var a=arguments; t=setTimeout(function(){ fn.apply(null,a);}, ms||250); }; }

  function hasActiveQuery(){
    var q = (input && input.value) ? input.value.trim() : '';
    return q.length >= 2 || (Array.isArray(window.bbActiveFeatures) && window.bbActiveFeatures.length > 0);
  }

  function renderLoading(){
    if(replaceList && productList){
      productList.innerHTML = '<div class="bb-loading">Ładowanie…</div>';
      try{ productList.scrollIntoView({behavior:'smooth', block:'start'}); }catch(e){}
    } else {
      results.style.display='block';
      results.innerHTML = '<div class="bbcatsearch-panel"><div class="bbcatsearch-panel__body">Ładowanie…</div></div>';
    }
  }

  function renderHtml(html){
    if(replaceList && productList){
      productList.innerHTML = html || '';
      try{ productList.scrollIntoView({behavior:'smooth', block:'start'}); }catch(e){}
    } else {
      results.innerHTML = html || '';
      results.style.display = html ? 'block' : 'none';
    }
  }

  function restoreList(){
    if(productList){ productList.innerHTML = originalHTML; }
    if(results){ results.style.display='none'; results.innerHTML=''; }
    window.bbActiveFeatures = [];
  }

  function doSearch(force){
    var q = (input && input.value) ? input.value.trim() : '';
    var shouldRun = force || hasActiveQuery();
    if(DBG){ console.log('[BB_CAT] doSearch', {q:q, features:window.bbActiveFeatures, shouldRun:shouldRun}); }
    if(!shouldRun){ restoreList(); return; }

    renderLoading();

    var xhr = new XMLHttpRequest();
    var data = new FormData();
    data.append('s', q); // dopuszczamy puste q przy samych filtrach
    data.append('id_category', catId);
    data.append('limit', limit);
    data.append('with_children', withCh);
    data.append('img_type', imgType);

    if(Array.isArray(window.bbActiveFeatures)){
      // główny sposób – jako tablica
      window.bbActiveFeatures.forEach(function(fid){ data.append('features[]', fid); });
      // zapasowo – CSV
      if(window.bbActiveFeatures.length){ data.append('features', window.bbActiveFeatures.join(',')); }
    }

    xhr.open('POST', ajaxUrl, true);
    xhr.onreadystatechange = function(){
      if(xhr.readyState === 4){
        if(DBG){ console.log('[BB_CAT] xhr', xhr.status, xhr.responseText ? xhr.responseText.length : 0); }
        if(xhr.status === 200){
          renderHtml(xhr.responseText || '');
        } else {
          renderHtml('<div class="bbcatsearch-panel"><div class="bbcatsearch-panel__body">Błąd wyszukiwania.</div></div>');
        }
      }
    };
    xhr.send(data);
  }

  var run = debounce(doSearch, 300);
  if(input){
    input.addEventListener('input', run);
    input.addEventListener('keydown', function(e){
      if(e.key === 'Escape'){ restoreList(); }
    });
  }
  if(clearBt){
    clearBt.addEventListener('click', function(){
      if(input){ input.value=''; }
      window.bbActiveFeatures = [];
      restoreList();
    });
  }
  document.addEventListener('click', function(e){
    if(!box.contains(e.target) && results){ results.style.display='none'; }
  });
  if(DBG){
    console.log('[BB_CAT] Ready. Tip: localStorage.setItem("BB_CAT_DEBUG","1")');
  }
})();
</script>
{/literal}

{/block}
{/strip}

/**
 * 2007-2025 PrestaShop
 * Academic Free License 3.0
 *
 * MODIFIED for advanced AJAX/Redirect search logic
 */
$(document).ready(function() {
    // === ZMIENNE PRZECHOWUJĄCE STAN WYSZUKIWANIA ===
    let currentSearchTerm = '';
    let currentCategoryId = 0;
    let searchTimeout;

    // === SELEKTORY OBIEKTÓW jQuery ===
    const $input = $('.tvcmssearch-words');
    const $resultsContainer = $('.tvsearch-result');
    const $searchWrapper = $('.search-widget.tvcmsheader-search');
    const $mainForm = $('.tvsearch-header-display-wrappper form');

    // ================================================================
    // START MODYFIKACJI: Logika nakładki (overlay) TYLKO DLA DESKTOP
    // ================================================================
    
    // Tworzymy element nakładki i dodajemy go do body
    let $overlay = $('<div class="tvsearch-page-overlay"></div>');
    $('body').append($overlay);
    
    // Definiujemy szerokość mobilną
    var MOBILE_MAX = 768; 

    function showOverlay() {
        // Uruchom tylko na desktopie
        if (window.innerWidth > MOBILE_MAX) {
            $overlay.addClass('is-visible');
            $('body').addClass('tvsearch-scroll-lock');
        }
    }

    function hideOverlay() {
        // Uruchom tylko na desktopie
        if (window.innerWidth > MOBILE_MAX) {
            $overlay.removeClass('is-visible');
            $('body').removeClass('tvsearch-scroll-lock');
        }
    }
    
    // ================================================================
    // KONIEC MODYFIKACJI
    // ================================================================


    // === GŁÓWNA FUNKCJA WYSZUKUJĄCA (AJAX) ===
    function performSearch(searchTerm, categoryId) {
        
        // ================================================================
        // POPRAWKA: USUNIĘTO BLOKADĘ DLA MOBILKI (if window.innerWidth <= MOBILE_MAX)
        // Ta funkcja MUSI działać na mobilce, aby pobierać wyniki podczas pisania.
        // ================================================================
        
        const term = searchTerm || '';
        currentSearchTerm = term.trim();
        currentCategoryId = categoryId || 0;

        // Na mobilce, podpowiedź "min. X znaków" jest obsługiwana przez tvcmssearch.mobile.js
        // Na desktopie, jest obsługiwana przez łatki poniżej.
        // Ale jeśli łatki na desktopie zablokują wyszukiwanie (bo jest < 3),
        // musimy też ukryć overlay.
        if (currentSearchTerm.length < 3) {
            $resultsContainer.hide().empty();
            hideOverlay(); // Ta funkcja ma już w sobie warunek desktopowy
            return;
        }

        const $productsColumn = $resultsContainer.find('.tvsearch-products-column');
        if ($productsColumn.length) {
            $productsColumn.addClass('loading-overlay');
        } else {
            $resultsContainer.show().html('<div class="tvsearch-loading-spinner"></div>');
        }
        
        showOverlay(); // Ta funkcja ma już w sobie warunek desktopowy

        $.ajax({
            type: 'POST',
            url: tvcmssearch_ajax_url,
            data: {
                search_words: currentSearchTerm,
                category_id: currentCategoryId,
            },
            success: function(responseHtml) {
                $resultsContainer.html(responseHtml);
                $resultsContainer.show();
                updateVisualsAfterSearch();
                showOverlay(); // Ta funkcja ma już w sobie warunek desktopowy
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.error("Błąd AJAX:", textStatus, errorThrown);
                $resultsContainer.html('<p class="no-results">Wystąpił błąd wyszukiwania.</p>');
                hideOverlay(); // Ta funkcja ma już w sobie warunek desktopowy
            }
        });
    }

    // === FILTROWANIE PRODUKTÓW PO CECHACH (CLIENT-SIDE) ===
    function filterProductsByFeatures() {
        const $products = $resultsContainer.find('.tvsearch-dropdown-wrapper');
        const $checkedFilters = $resultsContainer.find('.tvsearch-feature-filter:checked');

        const selectedFeatureIds = $checkedFilters.map(function() {
            return $(this).data('id-feature').toString();
        }).get();
        
        // Pokaż/ukryj przycisk resetowania
        if (selectedFeatureIds.length > 0) {
            $resultsContainer.find('.tvsearch-reset-filters').show();
        } else {
             // Ukryj tylko, jeśli kategoria też nie jest wybrana
            if (currentCategoryId == 0) {
                $resultsContainer.find('.tvsearch-reset-filters').hide();
            }
        }

        if (selectedFeatureIds.length === 0) {
            $products.show();
            return;
        }

        $products.each(function() {
            const $product = $(this);
            const productFeatureIds = ($product.data('feature-values') || '').toString().split(',');

            const hasAllFeatures = selectedFeatureIds.every(function(id) {
                return productFeatureIds.includes(id);
            });

            if (hasAllFeatures) {
                $product.show();
            } else {
                $product.hide();
            }
        });
    }

    // === AKTUALIZACJA WYGLĄDU PO WYSZUKIWANIU ===
    function updateVisualsAfterSearch() {
        $resultsContainer.find('.tvsearch-category-link').removeClass('active');
        if (currentCategoryId != 0) {
            $resultsContainer.find(`.tvsearch-category-link[data-id-category="${currentCategoryId}"]`).addClass('active');
            $resultsContainer.find('.tvsearch-show-filtered-wrapper').show();
            $resultsContainer.find('.tvsearch-reset-filters').show();
        } else {
            $resultsContainer.find('.tvsearch-show-filtered-wrapper').hide();
            if ($resultsContainer.find('.tvsearch-feature-filter:checked').length === 0) {
                $resultsContainer.find('.tvsearch-reset-filters').hide();
            }
        }

        const $termDisplayWrapper = $resultsContainer.find('.tvsearch-show-all-wrapper');
        const $termDisplay = $resultsContainer.find('.tvsearch-term-display');
        if (currentSearchTerm.length > 0) {
            $termDisplay.text(` "${currentSearchTerm}"`);
            $termDisplayWrapper.show();
        } else {
            $termDisplayWrapper.hide();
        }
    }

    // === OBSŁUGA ZDARZEŃ (EVENT LISTENERS) ===
    $input.on('input', function() {
        clearTimeout(searchTimeout);
        const newSearchTerm = $(this).val();
        
        searchTimeout = setTimeout(() => {
            performSearch(newSearchTerm, 0);
        }, 300);
    });

    $(document).on('click', '.tvsearch-category-link', function(e) {
        // Na mobilce ta funkcja jest nadpisywana przez tvcmssearch.mobile.js
        // Na desktopie działa poprawnie
        e.preventDefault();
        const categoryId = $(this).data('id-category');
        if (tvcmssearch_click_mode === 'ajax') {
            performSearch(currentSearchTerm, categoryId);
        } else {
            const categoryUrl = $(this).data('category-url');
            window.location.href = `${categoryUrl}?s=${encodeURIComponent(currentSearchTerm)}`;
        }
    });

    $(document).on('change', '.tvsearch-feature-filter', function() {
        filterProductsByFeatures();
    });

    $(document).on('click', '.tvsearch-reset-filters', function(e) {
        e.preventDefault();
        performSearch(currentSearchTerm, 0);
    });

    $(document).on('click', '.tvsearch-show-filtered-btn', function() {
        const activeCategoryLink = $resultsContainer.find('.tvsearch-category-link.active');
        if (activeCategoryLink.length) {
            const categoryUrl = activeCategoryLink.data('category-url');
            window.location.href = `${categoryUrl}?s=${encodeURIComponent(currentSearchTerm)}`;
        }
    });

    $(document).on('click', '.tvsearch-show-all-results-btn, .tvsearch-show-all-for-term-btn', function() {
        $mainForm.submit();
    });

    // NOWY listener dla rozwijania sekcji filtrów
    $(document).on('click', '.tvsearch-filter-toggle', function() {
        $(this).toggleClass('is-open');
        $(this).siblings('.tvsearch-feature-grid-wrapper').slideToggle('fast');
    });
    
    $(document).on('click', function(event) {
        // MODYFIKACJA: Sprawdzamy, czy kliknięto w overlay LUB poza search-widget
        const $target = $(event.target);
        if ($target.hasClass('tvsearch-page-overlay') || !$target.closest('.search-widget').length) {
            $resultsContainer.hide().empty();
            hideOverlay(); // Ta funkcja ma już w sobie warunek desktopowy
        }
    });
    
    $(document).on('click', '.tvsearch-dropdown-close', function() {
        $resultsContainer.hide().empty();
        hideOverlay(); // Ta funkcja ma już w sobie warunek desktopowy
    });
});


// ================================================================
// START POPRAWKI: Uruchomienie łatek (patchy) tylko na desktopie
// ================================================================
(function() {
    // Sprawdzenie, czy to desktop (odwrotność logiki z tvcmssearch.mobile.js)
    var MOBILE_MAX = 768; 
    if (window.innerWidth <= MOBILE_MAX) {
        return; // Nie uruchamiaj poniższych łatek na mobilce
    }

    /* === MIN-CHARS HINT PATCH (2025-10-06): non-destructive, appended === */
    (function($){
      if (!window.jQuery) return;
      $(function(){
        var MIN_CHARS = (typeof window.tvcmssearch_min_chars !== 'undefined' ? parseInt(window.tvcmssearch_min_chars,10) : 5);
        var $root = $('.tvcmsheader-search');
        var $input = $root.find('.tvcmssearch-words');
        var $results = $root.find('.tvsearch-result');

        function hintHTML(typed){
          return ''+
            '<div class="tvsearch-hint">' +
              '<i class="material-icons" aria-hidden="true">&#xe8b6;</i>' +
              '<div class="tvsearch-hint-text">Wpisz <strong>min. '+MIN_CHARS+'</strong> znaków, aby zobaczyć produkty ' +
              '<span class="tvsearch-hint-counter">(wpisano: '+typed+'/'+MIN_CHARS+')</span></div>' +
            '</div>';
        }

        function renderHint(){
          if (!$input.length || !$results.length) return;
          var typed = ($input.val() || '').trim().length;
          if (typed >= MIN_CHARS){
            // Usuń tylko nasz hint; nie dotykamy reszty wyników
            $results.find('.tvsearch-hint').remove();
            return;
          }
          // Spróbuj wstawić do kolumny z produktami, aby zachować layout
          var $productsCol = $results.find('.tvsearch-products-column');
          if ($productsCol.length){
            $productsCol.find('.tvsearch-hint').remove();
            $productsCol.prepend(hintHTML(typed));
          } else {
            // Gdy struktura jeszcze nie powstała, pokaż prosty kontener w result boxie
            $results.html('<div class="tvsearch-hint tvsearch-hint--solo">'+hintHTML(typed).replace('<div class="tvsearch-hint">','').replace('</div>','')+'</div>');
          }
        }

        // Pokaż hint na focus / wpisywaniu i przy zbyt krótkim submit
        $root.on('focus', '.tvcmssearch-words', renderHint);
        $root.on('input', '.tvcmssearch-words', renderHint);
        $root.find('form').on('submit', function(e){
          var typed = ($input.val() || '').trim().length;
          if (typed < MIN_CHARS){
            e.preventDefault();
            renderHint();
            return false;
          }
        });
      });
    })(jQuery);
    /* === END MIN-CHARS HINT PATCH === */


    /* === TVCMSSEARCH CONSOLIDATED UX PATCH (2025-10-06)
       - MIN CHARS hint (focus/input/submit)
       - Capture-phase guard to block AJAX < MIN
       - Brand spinner on AJAX (tvcmssearch only)
       Non-destructive: appended; does not remove/override existing handlers.
    === */
    (function(){
      function onReady(fn){ if(document.readyState!=='loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }
      onReady(function(){
        var MIN = (typeof window.tvcmssearch_min_chars !== 'undefined' ? parseInt(window.tvcmssearch_min_chars,10) : 5);
        var $ = window.jQuery;
        var root = document.querySelector('.tvcmsheader-search');
        if(!root) return;
        var input = root.querySelector('.tvcmssearch-words');
        var resultsBox = root.querySelector('.tvsearch-result');

        function ensureResultsVisible(){
          if (!$ || !resultsBox) return;
          var $res = $(resultsBox);
          if (!$res.is(':visible')){ $res.show(); }
          return $res;
        }

        function renderHint(len){
          if (!$ || !resultsBox) return;
          var $res = ensureResultsVisible();
          if (!$res) return;
          var $products = $res.find('.tvsearch-products-column');
          var html = '<div class="tvsearch-hint"><i class="material-icons" aria-hidden="true">&#xe8b6;</i>' +
                     '<div class="tvsearch-hint-text">Wpisz <strong>min. '+MIN+'</strong> znaków, aby zobaczyć produkty ' +
                     '<span class="tvsearch-hint-counter">(wpisano: '+len+'/'+MIN+')</span></div></div>';
          if ($products.length){
            $products.find('.tvsearch-hint').remove();
            $products.prepend(html);
          } else {
            $res.html(html);
          }
        }

        function currentLen(){ return (input && input.value ? input.value.trim().length : 0); }

        // Show hint on focus and on input while < MIN
        function focusOrType(){
          var len = currentLen();
          if (len < MIN){ renderHint(len); }
        }
        if (input){
          input.addEventListener('focus', focusOrType, false);
          input.addEventListener('input', focusOrType, false);
        }

        // Capture-phase guard: blocks module AJAX while < MIN
        function guard(e){
          var len = currentLen();
          if (len < MIN){
            try{ e.preventDefault(); }catch(_){}
            try{ e.stopPropagation(); }catch(_){}
            try{ e.stopImmediatePropagation && e.stopImmediatePropagation(); }catch(_){} 
            renderHint(len);
            return false;
          }
        }
        if (input){
          input.addEventListener('input', guard, true); 
          input.addEventListener('keyup', guard, true); 
          var form = root.querySelector('form');
          if (form){ form.addEventListener('submit', guard, true); } 
        }

        // Spinner: toggle on our AJAX only
        if ($){
          var ajaxUrl = (typeof window.tvcmssearch_ajax_url !== 'undefined' && window.tvcmssearch_ajax_url) ? window.tvcmssearch_ajax_url : '';
          function isOur(settings){
            if (!settings || !settings.url) return false;
            if (ajaxUrl && settings.url.indexOf(ajaxUrl) !== -1) return true;
            return /module=tvcmssearch|tvcmssearch\/ajax/i.test(settings.url);
          }
          $(document).off('.tvcmssearchUXPatch'); 
          $(document).on('ajaxSend.tvcmssearchUXPatch', function(_e, _xhr, settings){
            if (isOur(settings)){ $(root).addClass('tvcmssearch--loading'); }
          });
          $(document).on('ajaxComplete.tvcmssearchUXPatch ajaxError.tvcmssearchUXPatch', function(_e, _xhr, settings){
            if (isOur(settings)){ setTimeout(function(){ $(root).removeClass('tvcmssearch--loading'); }, 60); }
          });
        }
      });
    })();
    /* === END CONSOLIDATED UX PATCH === */


    /* === TVCMSSEARCH IMMEDIATE SPINNER ON THRESHOLD (2025-10-06) === */
    (function(){
      function onReady(fn){ if(document.readyState!=='loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }
      onReady(function(){
        var MIN = (typeof window.tvcmssearch_min_chars !== 'undefined' ? parseInt(window.tvcmssearch_min_chars,10) : 5);
        var root = document.querySelector('.tvcmsheader-search');
        if(!root) return;
        var input = root.querySelector('.tvcmssearch-words');
        if(!input) return;
        var prevShort = true;

        function maybeStartSpinner(){
          // Start the spinner proactively; ajaxComplete hook will remove it afterwards
          if (!root.classList.contains('tvcmssearch--loading')){
            root.classList.add('tvcmssearch--loading');
            // Safety auto-clear in case AJAX doesn't fire for jakiegoś powodu
            var t = Date.now();
            root.setAttribute('data-tvcmssearch-spin-ts', t);
            setTimeout(function(){
              if (root.getAttribute('data-tvcmssearch-spin-ts') == String(t)){
                root.classList.remove('tvcmssearch--loading');
                root.removeAttribute('data-tvcmssearch-spin-ts');
              }
            }, 2500);
          }
        }

        input.addEventListener('input', function(){
          var len = (input.value || '').trim().length;
          var isShort = len < MIN;
          // Transition: from short -> reached threshold: show spinner immediately
          if (prevShort && !isShort){
            maybeStartSpinner();
          }
          prevShort = isShort;
        }, true); // capture-phase just before module AJAX starts
      });
    })();
    /* === END IMMEDIATE SPINNER PATCH === */


    /* === TVCMSSEARCH DOM SPINNER (explicit element) — 2025-10-06 === */
    (function(){
      function onReady(fn){ if(document.readyState!=='loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }
      onReady(function(){
        var MIN = (typeof window.tvcmssearch_min_chars !== 'undefined' ? parseInt(window.tvcmssearch_min_chars,10) : 5);
        var $ = window.jQuery;
        var root = document.querySelector('.tvcmsheader-search');
        if (!root) return;
        var input = root.querySelector('.tvcmssearch-words');
        if (!input) return;

        // Ensure inline spinner element exists and is on top
        function ensureSpinner(){
          var el = root.querySelector('.tvcmssearch-inline-spinner');
          if (!el){
            el = document.createElement('div');
            el.className = 'tvcmssearch-inline-spinner';
            root.appendChild(el);
          }
          return el;
        }
        function showSpinner(){
          var el = ensureSpinner();
          el.classList.add('is-visible');
        }
        function hideSpinner(){
          var el = root.querySelector('.tvcmssearch-inline-spinner');
          if (el){ el.classList.remove('is-visible'); }
        }

        // Show immediately when threshold reached
        var wasShort = true;
        input.addEventListener('input', function(){
          var len = (input.value||'').trim().length;
          if (wasShort && len >= MIN){ showSpinner(); }
          wasShort = (len < MIN);
        }, true);

        // Keep in sync with AJAX lifecycle (jQuery)
        if ($){
          var ajaxUrl = (typeof window.tvcmssearch_ajax_url !== 'undefined' && window.tvcmssearch_ajax_url) ? window.tvcmssearch_ajax_url : '';
          function isOur(settings){
            if (!settings || !settings.url) return false;
            if (ajaxUrl && settings.url.indexOf(ajaxUrl) !== -1) return true;
            return /module=tvcmssearch|tvcmssearch\/ajax/i.test(settings.url);
          }
          $(document).off('.tvcmssearchDomSpinner');
          $(document).on('ajaxSend.tvcmssearchDomSpinner', function(_e, _xhr, settings){
            if (isOur(settings)){ showSpinner(); }
          });
          $(document).on('ajaxComplete.tvcmssearchDomSpinner ajaxError.tvcmssearchDomSpinner', function(_e, _xhr, settings){
            if (isOur(settings)){ setTimeout(hideSpinner, 60); }
          });
        }
      });
    })();
    /* === END DOM SPINNER PATCH === */


    /* === TVCMSSEARCH: ICON-SIDE SPINNER + INSTANT LOADING PANEL (2025-10-06) === */
    (function(){
      function onReady(fn){ if(document.readyState!=='loading'){ fn(); } else { document.addEventListener('DOMContentLoaded', fn); } }
      onReady(function(){
        var MIN = (typeof window.tvcmssearch_min_chars !== 'undefined' ? parseInt(window.tvcmssearch_min_chars,10) : 5);
        var $ = window.jQuery;
        var root = document.querySelector('.tvcmsheader-search');
        if (!root) return;
        var input = root.querySelector('.tvcmssearch-words');
        var results = root.querySelector('.tvsearch-result');
        if (!input || !results) return;

        // Inline spinner (near magnifying icon)
        function ensureInlineSpinner(){
          var el = root.querySelector('.tvcmssearch-inline-spinner');
          if (!el){
            el = document.createElement('div');
            el.className = 'tvcmssearch-inline-spinner tvcmssearch-inline-spinner--near-icon';
            root.appendChild(el);
          }
          return el;
        }
        function showInlineSpinner(){ ensureInlineSpinner().classList.add('is-visible'); }
        function hideInlineSpinner(){ var el = root.querySelector('.tvcmssearch-inline-spinner'); if (el){ el.classList.remove('is-visible'); } }

        // Loading panel inside dropdown (right column)
        function ensureResultsVisible(){
          if (!$) return null;
          var $res = $(results);
          if (!$res.is(':visible')){ $res.show(); }
          return $res;
        }
        function renderLoadingPanel(term){
          var $res = ensureResultsVisible();
          if (!$res) return;
          var header = '<div class="tvsearch-header-display tvsearch-header-display-full"><span class="tvsearch-header-display-label">Pokaż Wszystkie Produkty Dla:</span> <strong>"'+$('<div>').text(term).html()+'"</strong></div>';
          var loading = ''+
            '<div class="tvsearch-loading-row">'+
              '<span class="tvsearch-loading-spinner" aria-hidden="true"></span>'+
              '<span class="tvsearch-loading-text">Ładowanie wyników…</span>'+
            '</div>';
          var html = ''+
            '<div class="tvcmssearch-dropdown">'+
              header +
              '<div class="tvsearch-results-container">'+
                '<div class="tvsearch-filter-column"><h4>Kategorie</h4><ul class="tvsearch-category-list tvsearch-category-list--empty"></ul><div class="tvsearch-show-filtered-wrapper" style="display:none"></div></div>'+
                '<div class="tvsearch-products-column">'+ loading +'</div>'+
              '</div>'+
            '</div>';
          $res.html(html);
        }

        // When reaching threshold: show both spinner and loading panel instantly
        var wasShort = true;
        input.addEventListener('input', function(){
          var len = (input.value||'').trim().length;
          if (wasShort && len >= MIN){
            showInlineSpinner();
            renderLoadingPanel((input.value||'').trim());
          }
          wasShort = (len < MIN);
        }, true); // capture-phase so we're earlier than AJAX

        // Keep spinner in sync with our AJAX calls (also serves as fallback)
        if ($){
          var ajaxUrl = (typeof window.tvcmssearch_ajax_url !== 'undefined' && window.tvcmssearch_ajax_url) ? window.tvcmssearch_ajax_url : '';
          function isOur(settings){
            if (!settings || !settings.url) return false;
            if (ajaxUrl && settings.url.indexOf(ajaxUrl) !== -1) return true;
            return /module=tvcmssearch|tvcmssearch\/ajax/i.test(settings.url);
          }
          $(document).off('.tvcmssearchIconSpinner');
          $(document).on('ajaxSend.tvcmssearchIconSpinner', function(_e,_xhr,settings){ if (isOur(settings)){ showInlineSpinner(); } });
          $(document).on('ajaxComplete.tvcmssearchIconSpinner ajaxError.tvcmssearchIconSpinner', function(_e,_xhr,settings){ if (isOur(settings)){ setTimeout(hideInlineSpinner,60); } });
        }
      });
    })();
    /* === END ICON-SIDE SPINNER + INSTANT PANEL === */

})(); // Koniec bloku "tylko na desktopie"
// ================================================================
// KONIEC POPRAWKI
// ================================================================


/* === AJAX PREFILTER v10 (2025-10-06)
   - Ten patch jest globalny, ponieważ sam sprawdza, czy jest na mobilce/desktopie
=== */
if (!window.__tvsearch_prefilter_v10){
window.__tvsearch_prefilter_v10 = 1;
(function(w, $){
  if (!w || !w.jQuery || !$ || !$.ajaxPrefilter) return;
  function isMobile(){
    try{
      if (w.matchMedia && w.matchMedia('(max-width: 991.98px)').matches) return true;
      if (document.querySelector('#tvcmssearch-mobile')) return true;
    }catch(_){}
    return false;
  }
  function toStrData(options, original){
    if (typeof options.data === 'string') return options.data;
    if (typeof original.data === 'string') return original.data;
    if (original.data && typeof original.data === 'object' && $.param) return $.param(original.data);
    return '';
  }
  function addMobileParam(str){
    if (!/(^|&)mobile=1(&|$)/.test(str)) str = str ? (str + '&mobile=1') : 'mobile=1';
    return str;
  }
  function removeMobileParam(str){
    // usuń mobile=1 i posprzątaj &
    str = str.replace(/(^|&)mobile=1(&|$)/, function(m, p1, p2){ return (p1 && p2) ? '&' : ''; });
    str = str.replace(/^&+|&+$/g,'').replace(/&&+/g,'&');
    return str;
  }
  $.ajaxPrefilter(function(options, original, jqXHR){
    try{
      var url = options.url || '';
      var target = (typeof w.tvcmssearch_ajax_url === 'string' && w.tvcmssearch_ajax_url.length) ? w.tvcmssearch_ajax_url : null;
      var isTarget = target ? (url.indexOf(target)!==-1) : (/\/module\/tvcmssearch\/ajax/.test(url));
      if (!isTarget) return;
      var dataStr = toStrData(options, original);
      if (isMobile()){ dataStr = addMobileParam(dataStr); }
      else { dataStr = removeMobileParam(dataStr); }
      options.data = dataStr;
    }catch(e){ console.warn('[tvcmssearch] prefilter v10 error', e); }
  });
})(window, window.jQuery);
}
/*! tvcmssearch.mobile.js — MOBILE (≤768px) v9
 * - Stabilna nawigacja po kliknięciu kategorii (bez location.assign) – przez tymczasowy formularz GET
 * - Brak zmasowanych wywołań Location/History
 * - CTA „Pokaż produkty z tej kategorii” nadal działa (też używa bezpiecznej nawigacji)
 */
(function(w,d){
  'use strict';
  var MOBILE_MAX = 768;
  function onReady(fn){ if(d.readyState!=='loading'){fn();} else {d.addEventListener('DOMContentLoaded', fn);} }
  function $(s,r){ return (r||d).querySelector(s); }
  function $all(s,r){ return Array.prototype.slice.call((r||d).querySelectorAll(s)); }
  function isMobile(){ return w.innerWidth <= MOBILE_MAX; }
  function debounce(fn,ms){ var t; return function(){ var c=this,a=arguments; clearTimeout(t); t=setTimeout(function(){fn.apply(c,a);},ms||200); }; }

  var MIN = (w.tvcmssearch_min_chars && parseInt(w.tvcmssearch_min_chars,10)) || 4;
  var INIT = 'data-tvsearch-mobile-init';

  function normalizeIconPlacement(){
    var box = $('.tvheader-top-search-wrapper-info-box');
    var icon = $('.tvheader-top-search-wrapper');
    if (box && icon && icon.parentNode !== box){
      try{ box.appendChild(icon); }catch(e){}
    }
  }
  function ensureSpinnerWrapper(){
    normalizeIconPlacement();
    var wrap = $('.tvheader-top-search-wrapper');
    if (!wrap) return;
    if (!$('.tvsearch-spinner', wrap)){
      var sp = d.createElement('span');
      sp.className = 'tvsearch-spinner';
      wrap.appendChild(sp);
    }
  }
  function addClearButton(input){
    var box = $('.tvheader-top-search-wrapper-info-box'); if(!box) return;
    if ($('.tvsearch-mobile-clear', box)) return;
    var btn = d.createElement('button');
    btn.type = 'button';
    btn.className = 'tvsearch-mobile-clear';
    btn.setAttribute('aria-label','Wyczyść');
    btn.innerHTML = '&times;';
    btn.addEventListener('click', function(ev){
      ev.preventDefault(); ev.stopPropagation();
      input.value = '';
      input.dispatchEvent(new Event('input',{bubbles:true}));
      input.focus();
    });
    box.appendChild(btn);
    toggleClearVisibility(input, btn);
    input.addEventListener('input', function(){ toggleClearVisibility(input, btn); });
  }
  function toggleClearVisibility(input, btn){
    if(!btn) return;
    btn.classList.toggle('is-visible', !!(input.value && input.value.length>0));
  }
  function hideLegacyAround(root){
    $all('.tvcmssearch-min-hint, .tvsearch-min-hint, .tv-min-hint, .tvsearch-legacy-hint', root.parentNode||d)
      .forEach(function(el){ el.style.display='none'; });
    var dd = d.querySelector('.tvcmssearch-dropdown') || root.parentNode || d;
    $all('.tvsearch-no-results, .no-results, .noresult, .no_result', dd)
      .forEach(function(el){ el.style.display='none'; });
  }

  function buildHint(anchor){
    var hint = d.createElement('div');
    hint.className = 'tvsearch-hint is-active';
    hint.setAttribute('data-tvsearch-hint','1');
    
    hint.innerHTML = '<i class="material-icons" aria-hidden="true">&#xe8b6;</i>' + 
                     '<div class="tvsearch-hint-text">' +
                       'Wpisz <strong>min. '+MIN+'</strong> znaki, aby zobaczyć produkty ' +
                       '<span class="muted">(wpisano: <span class="tvh-count">0</span>/'+MIN+')</span>' +
                     '</div>';

    anchor.parentNode.insertBefore(hint, anchor.nextSibling);
    return hint;
  }

  function updateHint(hint,val){
    var cnt = hint.querySelector('.tvh-count'); if(cnt) cnt.textContent=(val||'').length;
    var ok=((val||'').length>=MIN);
    hint.classList.toggle('is-active',!ok);
    hint.classList.toggle('is-hidden', ok);
    if(!ok){ hideLegacyAround($('.search-widget')||d); }
  }
  function findDropdown(){ return d.querySelector('.tvcmssearch-dropdown'); }

  function insertCatsContainer(anchor){
    var dropdown = findDropdown();
    var sec = d.createElement('section'); sec.className='tvsearch-mobile-cats is-hidden';
    var btn=d.createElement('button'); btn.type='button'; btn.className='tvsearch-cats-toggle';
    btn.setAttribute('aria-expanded','true');
    btn.innerHTML='<span>TOP Kategorie w wynikach</span><span class="arrow">▾</span>';
    var panel=d.createElement('div'); panel.className='tvsearch-cats-panel';
    var list=d.createElement('ul'); panel.appendChild(list);
    if (dropdown){
      var firstResults = dropdown.querySelector('.tvsearch-results-container, .tvsearch-result, .tvsearch-result-list, .tvsearch-result-items');
      if (firstResults){ dropdown.insertBefore(sec, firstResults); }
      else { dropdown.insertBefore(sec, dropdown.firstChild); }
    } else {
      anchor.parentNode.insertBefore(sec, anchor.nextSibling);
    }
    sec.appendChild(btn); sec.appendChild(panel);
    btn.addEventListener('click', function(){
      var exp=btn.getAttribute('aria-expanded')==='true';
      btn.setAttribute('aria-expanded', exp?'false':'true');
      if(exp){ panel.setAttribute('hidden','hidden'); } else { panel.removeAttribute('hidden'); }
    });
    return {wrap:sec, btn:btn, panel:panel, list:list};
  }

  function findCategoryUL(){
    var cand = $('.tvsearch-filter-column ul')
            || $('.tvcmssearch-dropdown .tvsearch-filter-column ul')
            || $('.tvcmssearch-dropdown .tvsearch-category-list')
            || $('.tvsearch-category-list');
    if (cand) return cand;
    var allHeads = $all('.tvcmssearch-dropdown h3, .tvcmssearch-dropdown h4, .tvcmssearch-dropdown .title, .tvcmssearch-dropdown .heading');
    for (var i=0;i<allHeads.length;i++){
      var h = allHeads[i];
      if ((h.textContent||'').trim().toLowerCase().indexOf('kategorie')>-1){
        var next = h.nextElementSibling;
        while (next && next.tagName && next.tagName.toLowerCase()!=='ul'){ next = next.nextElementSibling; }
        if (next) return next;
      }
    }
    var uls = $all('.tvcmssearch-dropdown ul');
    var score = -1, picked=null;
    uls.forEach(function(ul){
      var lis = $all('li', ul);
      var s = 0;
      lis.forEach(function(li){
        var t=(li.textContent||''); if (/\(\d+\)/.test(t)) s+=2; if (t.length>0) s+=0.2;
      });
      if (lis.length>=5) s+=1;
      if (s>score){ score=s; picked=ul; }
    });
    return picked;
  }

  var navLock = false, navTimer = null;
  function safeNavigate(href){
    if(!href || href==='#') return;
    if(navLock) return;
    navLock = true;
    try{
      var url = new URL(href, d.baseURI).href;
      var form = d.createElement('form');
      form.method = 'GET';
      form.action = url;
      form.style.display = 'none';
      d.body.appendChild(form);
      form.submit();
      navTimer = setTimeout(function(){
        try { w.top.location.href = url; } catch(e) { w.location.href = url; }
      }, 600);
    }catch(e){
      try { w.top.location.href = href; } catch(e2) { w.location.href = href; }
    }
  }

  var ctaWrap,ctaBtn, ctaHref='';
  function ensureCTA(afterEl){
    if(ctaWrap) return;
    ctaWrap=d.createElement('div'); ctaWrap.className='tvsearch-mobile-cta';
    ctaBtn=d.createElement('button'); ctaBtn.type='button'; ctaBtn.className='tvsearch-show-filtered-btn';
    ctaBtn.textContent='Pokaż produkty z tej kategorii';
    ctaWrap.appendChild(ctaBtn); afterEl.parentNode.insertBefore(ctaWrap, afterEl.nextSibling);
    ctaBtn.addEventListener('click', function(){ safeNavigate(ctaHref); });
  }
  function showCTA(href){
    ensureCTA($('.tvsearch-mobile-cats')||$('.tvsearch-hint')||$('.tvheader-top-search'));
    ctaHref = href || ctaHref;
    ctaWrap.classList.add('is-visible');
  }

  function populateCats(listEl){
    listEl.innerHTML='';
    var src = findCategoryUL();
    if(!src) return;
    $all('li',src).forEach(function(li){
      var a=$('a',li)||li; if(!a) return;
      var txt=(a.textContent||'').trim(); if(!txt) return;
      var href=a.getAttribute ? (a.getAttribute('href')||'#') : '#';
      var catId = a.getAttribute('data-id-category') || a.getAttribute('data-category-id') || '0';
      var li2=d.createElement('li'); var a2=d.createElement('a');
      a2.href=href;
      a2.textContent=txt;
      a2.rel='nofollow';
      a2.className = 'tvsearch-category-link';
      a2.setAttribute('data-id-category', catId);
      li2.appendChild(a2); listEl.appendChild(li2);
    });
  }

  function hasResults(){
    var sel = [
      '.tvcmssearch-dropdown .product-miniature',
      '.tvcmssearch-dropdown .js-product-miniature',
      '.tvcmssearch-dropdown .tvsearch-result li',
      '.tvcmssearch-dropdown [data-id-product]',
      '.tvsearch-result .product-miniature',
      '.tvsearch-result .js-product-miniature',
      '.tvsearch-results-container .product-miniature',
      '.tvsearch-results-container .js-product-miniature'
    ].join(',');
    if (d.querySelector(sel)) return true;
    var dd = d.querySelector('.tvcmssearch-dropdown');
    if (!dd) return false;
    var nodes = $all('*', dd);
    for (var i=0;i<nodes.length;i++){
      var t=(nodes[i].textContent||'').toLowerCase();
      if (t.indexOf('pokaż wszystkie produkty')>-1) return true;
    }
    return false;
  }
  function showCatsIfResults(cats){ if(!cats) return; cats.wrap.classList.toggle('is-hidden', !hasResults()); }

  var onDomChangedDebounced;
  function init(){
    if(!isMobile()) return;
    var root=$('.search-widget'); if(!root || root.getAttribute(INIT)) return;
    root.setAttribute(INIT,'1');
    ensureSpinnerWrapper();
    var input=$('.tvheader-top-search-wrapper-info-box .tvcmssearch-words') || $('.tvheader-top-search-wrapper-info-box input[name="s"]') || $('input[name="s"]');
    if(!input) return;
    addClearButton(input);
    hideLegacyAround(root);
    var anchor=$('.tvheader-top-search')||input.parentNode;
    var hint=buildHint(anchor);
    updateHint(hint, input.value||'');
    var cats=insertCatsContainer(hint);
    populateCats(cats.list);
    ensureCTA(cats.wrap);
    showCatsIfResults(cats);
    var debounced=debounce(function(){
      if(input.value.length>=MIN){ root.classList.add('is-loading'); }
      updateHint(hint, input.value||'');
      hideLegacyAround(root);
    }, 140);
    input.addEventListener('input', debounced);
    onDomChangedDebounced = debounce(function(){
      root.classList.remove('is-loading');
      populateCats(cats.list);
      showCatsIfResults(cats);
      hideLegacyAround(root);
      normalizeIconPlacement();
    }, 120);
    var mo=new MutationObserver(function(){ onDomChangedDebounced(); });
    mo.observe(d.body, {childList:true, subtree:true});
  }
  onReady(init);
  w.addEventListener('resize', debounce(function(){ if(isMobile()) init(); }, 200));
})(window, document);

/* === tvcmssearch MOBILE PATCH v12 (2025-10-06)
   - NIE RUSZA DESKTOPU. Tylko dopiski w mobile.js.
   - Klik w .tvsearch-category-link na mobile -> AJAX do tvcmssearch_ajax_url z search_words + category_id
   - Bez używania desktopowej performSearch.
   - Prefilter tylko w TYM pliku dodaje mobile=1 WYŁĄCZNIE na mobile (string-safe).
*/
if (!window.__tvsearch_mobile_v12){ window.__tvsearch_mobile_v12 = 1; (function(w,d,$){
  'use strict';
  function isMobile(){
    try{
      if (w.matchMedia && w.matchMedia('(max-width: 991.98px)').matches) return true;
      if (d.querySelector('#tvcmssearch-mobile')) return true;
    }catch(_){}
    return false;
  }
  function minChars(){
    var t = (typeof w.tvcmssearch_min_chars!=='undefined') ? parseInt(w.tvcmssearch_min_chars,10) : 3;
    return isNaN(t)?3:t;
  }
  function term(){
    var i = d.querySelector('.tvcmssearch-words');
    return (i && i.value) ? i.value.trim() : '';
  }
  function resultsBox(){
    return d.querySelector('.tvsearch-result');
  }

  function showLoadingOverlay() {
      var box = resultsBox();
      if (!box) return;
      if (w.getComputedStyle(box).position === 'static') {
          box.style.position = 'relative';
      }
      var overlay = box.querySelector('.tvsearch-mobile-loading-overlay');
      if (!overlay) {
          overlay = d.createElement('div');
          overlay.className = 'tvsearch-mobile-loading-overlay';
          overlay.innerHTML = '<div class="tvsearch-loading-spinner"></div>';
          var styles = {
              position: 'absolute', top: '0', left: '0', width: '100%', height: '100%',
              backgroundColor: 'rgba(255, 255, 255, 0.85)', zIndex: '10', display: 'flex',
              alignItems: 'center', justifyContent: 'center'
          };
          for(var rule in styles){ overlay.style[rule] = styles[rule]; }
          box.appendChild(overlay);
      }
      overlay.style.display = 'flex';
      box.style.display = 'block';
  }

  function hideLoadingOverlay() {
      var box = resultsBox();
      if (!box) return;
      var overlay = box.querySelector('.tvsearch-mobile-loading-overlay');
      if (overlay) {
          overlay.style.display = 'none';
      }
  }
  
  // USUNIĘTO funkcję 'repositionExtraFilters'

  function markActive(catId){
    var links = d.querySelectorAll('.tvsearch-category-link');
    Array.prototype.forEach.call(links, function(a){
      var cid = parseInt(a.getAttribute('data-id-category') || a.getAttribute('data-category-id') || '0', 10) || 0;
      if (cid === (parseInt(catId,10)||0)) a.classList.add('is-active');
      else a.classList.remove('is-active');
    });
  }

  if (w.jQuery && !w.__tvsearch_mobile_prefilter_v12){
    w.__tvsearch_mobile_prefilter_v12 = 1;
    (function($){
      function toStr(options, original){
        if (typeof options.data === 'string') return options.data;
        if (typeof original.data === 'string') return original.data;
        if (original.data && typeof original.data === 'object' && $.param) return $.param(original.data);
        return '';
      }
      function addMobile(str){
        if (!/(^|&)mobile=1(&|$)/.test(str)) str = str ? (str + '&mobile=1') : 'mobile=1';
        return str;
      }
      function removeMobile(str){
        str = str.replace(/(^|&)mobile=1(&|$)/, function(m, p1, p2){ return (p1 && p2) ? '&' : ''; });
        str = str.replace(/^&+|&+$/g,'').replace(/&&+/g,'&');
        return str;
      }
      $.ajaxPrefilter(function(options, original){
        try{
          var url = options.url || '';
          var ajaxUrl = (typeof w.tvcmssearch_ajax_url === 'string' && w.tvcmssearch_ajax_url.length) ? w.tvcmssearch_ajax_url : null;
          var isTarget = ajaxUrl ? (url.indexOf(ajaxUrl)!==-1) : (/\/module\/tvcmssearch\/ajax/.test(url));
          if (!isTarget) return;
          var dataStr = toStr(options, original);
          if (isMobile()) dataStr = addMobile(dataStr);
          else dataStr = removeMobile(dataStr);
          options.data = dataStr;
        }catch(e){ console.warn('[tvcmssearch] mobile prefilter v12 error', e); }
      });
    })(w.jQuery);
  }

  d.addEventListener('click', function(e){
    var a = e.target.closest('.tvsearch-category-link');
    if (!a) return;
    if (!isMobile()) return;
    var t = term();
    if (!t || t.length < minChars()) return;
    if (typeof w.tvcmssearch_ajax_url !== 'string' || !w.tvcmssearch_ajax_url.length) return;

    var cid = a.getAttribute('data-id-category') || a.getAttribute('data-category-id') || '0';
    cid = parseInt(cid,10) || 0;

    e.preventDefault();
    markActive(cid);
    showLoadingOverlay();

    var payload = 'search_words=' + encodeURIComponent(t) + '&category_id=' + encodeURIComponent(cid);

    if (w.jQuery && w.jQuery.ajax){
      w.jQuery.ajax({
        type: 'POST',
        url: w.tvcmssearch_ajax_url,
        data: payload,
      }).done(function(html){
        var box = resultsBox();
        if (box){ box.innerHTML = html; box.style.display='block'; }
        // USUNIĘTO wywołanie funkcji 'repositionExtraFilters()'
      }).fail(function(){
        var box = resultsBox();
        if (box){ box.innerHTML = '<div class="tvsearch-error">Błąd wczytywania wyników.</div>'; }
      }).always(function() {
        hideLoadingOverlay();
      });
    } else if (w.fetch) {
      fetch(w.tvcmssearch_ajax_url, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        credentials: 'same-origin',
        body: payload
      })
      .then(function(r){ if(!r.ok) throw new Error('Network response was not ok'); return r.text(); })
      .then(function(html){ 
        var box = resultsBox();
        if (box){ box.innerHTML = html; box.style.display='block'; }
        // USUNIĘTO wywołanie funkcji 'repositionExtraFilters()'
      })
      .catch(function(){ 
        var box = resultsBox();
        if (box){ box.innerHTML = '<div class="tvsearch-error">Błąd wczytywania wyników.</div>'; }
      })
      .finally(function() {
        hideLoadingOverlay();
      });
    }
  }, true);

})(window, document, window.jQuery); }
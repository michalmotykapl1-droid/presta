{* Zmodyfikowana treść całego pliku category_matcher.tpl *}

<div class="row">
<div class="col-lg-12">

<div class="panel">
  <div class="panel-heading"><i class="icon-random"></i> Asystent powiązań kategorii (Allegro PL)</div>
  <div class="form-horizontal">
    <div class="form-group">
      <label class="control-label col-lg-2">Kategoria PS</label>
      <div class="col-lg-6">
        <div class="input-group">
          <input type="text" class="form-control" id="catmatch_ps_search" placeholder="Wpisz nazwę kategorii...">
          <span class="input-group-btn">
            <button class="btn btn-default" type="button" id="catmatch_clear"><i class="icon-remove"></i></button>
          </span>
        </div>
        <input type="hidden" id="catmatch_id_category" value="">
        <p class="help-block" id="catmatch_selected_info" style="margin-top:6px;color:#2e5;">—</p>
      </div>
      <div class="col-lg-4">
        <label class="checkbox-inline" style="margin-right:10px; white-space:nowrap;">
          <input type="checkbox" id="catmatch_use_products" checked> Uwzględnij produkty (tytuły)
        </label>
        <label class="checkbox-inline" style="margin-right:10px; white-space:nowrap;">
          <input type="checkbox" id="catmatch_use_ean" checked> Użyj EAN produktów <input type="number" id="catmatch_ean_limit" min="1" max="200" value="5" style="width:80px; margin-left:8px;" title="Ilość EAN do sprawdzenia">
        </label>
        <label class="checkbox-inline" style="margin-right:10px; white-space:nowrap;">
          <input type="checkbox" id="catmatch_debug"> tryb debugowania
        </label>
        <button id="catmatch_run_all" class="btn btn-primary"><i class="icon-search"></i> ZAPROPONUJ</button>
      </div>
    </div>

    <div id="catmatch_results_ps" class="panel" style="display:none; max-height:220px; overflow:auto; margin:6px 0 12px 16.6667%;">
      <table class="table"><tbody id="catmatch_results_ps_body"></tbody></table>
    </div>

    <div class="form-group">
      <label class="control-label col-lg-2">Drzewo kategorii</label>
      <div class="col-lg-10">
        <div class="clearfix" style="margin-bottom:8px;">
          <label class="checkbox-inline"><input type="checkbox" id="catmatch_only_unmapped"> Pokaż tylko niepowiązane</label>
          <button class="btn btn-default btn-xs" id="catmatch_expand_all"><i class="icon-plus"></i> Rozwiń</button>
          <button class="btn btn-default btn-xs" id="catmatch_collapse_all"><i class="icon-minus"></i> Zwiń</button>

          {* --- DODANO --- Dodajemy przycisk do aktualizacji kategorii Allegro *}
          <button class="btn btn-info btn-xs" id="catmatch_update_allegro_cats" style="margin-left: 15px;"><i class="icon-download"></i> Pobierz/Odśwież kategorie Allegro</button>
          <span id="catmatch_update_spinner" style="display:none; margin-left:10px;"><i class="icon-spinner icon-spin"></i> Trwa pobieranie...</span>
          {* --- KONIEC DODAWANIA --- *}

        </div>
        <div id="catmatch_tree" style="max-height:360px; overflow:auto; border:1px solid #ddd; padding:8px;"></div>
      </div>
    </div>

    <div id="catmatch_results"></div>
  </div>
</div>

</div>
</div>

{literal}
<script>
(function(){
  var url = {/literal}'{$admin_link|escape:'javascript'}'{literal};

  // ... bez zmian w kodzie wyszukiwarki i drzewa ...
  // -------------- WYSZUKIWARKA PO NAZWIE --------------
  var input = document.getElementById('catmatch_ps_search');
  var hiddenId = document.getElementById('catmatch_id_category');
  var clearBtn = document.getElementById('catmatch_clear');
  var listBox = document.getElementById('catmatch_results_ps');
  var listBody = document.getElementById('catmatch_results_ps_body');
  var selectedInfo = document.getElementById('catmatch_selected_info');

  function doSearch() {
    var q = input.value.trim();
    if (q.length < 2) { listBox.style.display='none'; listBody.innerHTML=''; return; }
    var fd = new FormData();
    fd.append('ajax','1'); fd.append('action','SearchPsCategories'); fd.append('q', q);
    fetch(url, {method:'POST', body: fd, credentials:'same-origin'})
      .then(function(r){return r.text();})
      .then(function(t){ try{ return JSON.parse(t);}catch(e){ alert('Błąd (Search):\\n'+t.substring(0,400)); throw e; } })
      .then(function(resp){
        if(!resp.success){ listBox.style.display='none'; listBody.innerHTML=''; return; }
        var rows = resp.items || [];
        var html = '';
        rows.forEach(function(it){
          html += '<tr><td><a href="#" data-id="'+it.id+'" class="catmatch_pick">'+it.path+'</a></td></tr>';
        });
        listBody.innerHTML = html || '<tr><td><em>Brak wyników…</em></td></tr>';
        listBox.style.display = 'block';
        Array.prototype.forEach.call(listBody.querySelectorAll('.catmatch_pick'), function(a){
          a.addEventListener('click', function(ev){
            ev.preventDefault();
            hiddenId.value = this.getAttribute('data-id');
            input.value = this.textContent;
            selectedInfo.textContent = 'Wybrano: ' + this.textContent + ' (ID: ' + hiddenId.value + ')';
            listBox.style.display='none'; listBody.innerHTML='';
          });
        });
      }).catch(function(){});
  }
  var tmr = null;
  input.addEventListener('input', function(){ clearTimeout(tmr); tmr=setTimeout(doSearch, 300); });
  clearBtn.addEventListener('click', function(){ input.value=''; hiddenId.value=''; selectedInfo.textContent='—'; listBox.style.display='none'; listBody.innerHTML=''; });
  // -------------- DRZEWO KATEGORII --------------
  var treeBox = document.getElementById('catmatch_tree');
  var onlyUnmapped = document.getElementById('catmatch_only_unmapped');
  function fetchTree(){
    var fd=new FormData();
    fd.append('ajax','1'); fd.append('action','GetPsCategoryTree');
    fetch(url, {method:'POST', body: fd, credentials:'same-origin'})
      .then(function(r){return r.text();})
      .then(function(t){ try{ return JSON.parse(t);}catch(e){ alert('Błąd (Tree):\\n'+t.substring(0,400)); throw e; } })
      .then(function(resp){
        if(!resp.success){ treeBox.innerHTML='<div class="alert alert-danger">'+(resp.message||'Błąd')+'</div>'; return; }
        treeBox.innerHTML = buildTree(resp.tree || []);
        bindTreeActions();
      }).catch(function(e){
      });
  }
  function buildTree(nodes){
    function nodeHtml(n){
      var badge = n.mapped ?
        '<span class="label label-success" style="margin-left:6px;">powiązana</span>' :
        '<span class="label label-default" style="margin-left:6px;">brak</span>';
      var row = '<li data-id="'+n.id+'" data-mapped="'+(n.mapped?1:0)+'">'
              + '<label class="checkbox-inline"><input type="checkbox" class="catmatch_ck"> '+escapeHtml(n.name)+'</label>' + badge;
      if(n.children && n.children.length){
          row += '<ul>';
          n.children.forEach(function(c){ row += nodeHtml(c); });
          row += '</ul>';
      }
      row += '</li>';
      return row;
    }
    var html='<ul class="catmatch_tree_ul">';
    nodes.forEach(function(n){ html += nodeHtml(n); });
    html+='</ul>';
    return html;
  }
  function bindTreeActions(){ filterTree();
  }
  function filterTree(){
    var showOnly = onlyUnmapped.checked;
    var lis = treeBox.querySelectorAll('li[data-id]');
    lis.forEach(function(li){
      var mapped = li.getAttribute('data-mapped') === '1';
      li.style.display = (showOnly && mapped) ? 'none' : '';
    });
  }
  onlyUnmapped.addEventListener('change', filterTree);

  document.getElementById('catmatch_expand_all').addEventListener('click', function(){
    var uls = treeBox.querySelectorAll('ul'); uls.forEach(function(ul){ ul.style.display='block'; });
  });
  document.getElementById('catmatch_collapse_all').addEventListener('click', function(){
    var uls = treeBox.querySelectorAll('ul'); uls.forEach(function(ul){ ul.style.display='none'; });
  });

  function escapeHtml(s){ return (s||'').replace(/[&<>"]/g, function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[c];});
  }

  // -------------- PRZYCISK "ZAPROPONUJ" (JEDEN) --------------
  document.getElementById('catmatch_run_all').addEventListener('click', function(){
    var ids = [];
    if (hiddenId.value.trim() !== '') { ids.push(hiddenId.value.trim()); }
    var checks = treeBox.querySelectorAll('.catmatch_ck:checked');
    checks.forEach(function(ck){ ids.push( ck.closest('li').getAttribute('data-id') ); });
    ids = Array.from(new Set(ids));
    if (!ids.length) { alert('Wybierz kategorię z wyszukiwarki albo zaznacz w drzewie.'); return; }
    runSuggest(ids);
  });
  function runSuggest(ids){
    var usep = document.getElementById('catmatch_use_products').checked ? 1 : 0;
    var usee = document.getElementById('catmatch_use_ean').checked ? 1 : 0;
    var box=document.getElementById('catmatch_results');
    box.innerHTML='';

    function one(i){
      if(i>=ids.length){ return; }
      var idc = ids[i];
      var fd=new FormData();
      fd.append('ajax','1'); fd.append('action','SuggestAllegroCategories');
      fd.append('id_category', idc); fd.append('use_products', usep);
      fd.append('use_ean', usee); var eanL = document.getElementById('catmatch_ean_limit') ? parseInt(document.getElementById('catmatch_ean_limit').value||'5',10) : 5; fd.append('ean_limit', eanL);
      fd.append('debug', document.getElementById('catmatch_debug') && document.getElementById('catmatch_debug').checked ? 1 : 0);
      fetch(url, {method:'POST', body: fd, credentials:'same-origin'})
        .then(function(r){ return r.text(); })
        .then(function(t){ try{ return JSON.parse(t);}catch(e){ alert('Błąd (Suggest '+idc+'):\\n'+t.substring(0,400)); throw e; } })
        .then(function(resp){
          var html='<div class="panel" style="margin-top:10px;"><div class="panel-heading">Kategoria PS ID '+idc+'</div>';
          if(!resp.success){ html+='<div class="alert alert-danger">'+(resp.message||'Błąd')+'</div>'; }
          else {
            html+='<table class="table"><thead><tr><th>Propozycja (Allegro PL)</th><th>Score</th><th>Źródło</th><th>Przykłady</th><th></th></tr></thead><tbody>';
            (resp.suggestions||[]).forEach(function(s){
              var ex=(s.examples||[]).join('<br>');
              html+='<tr><td>'+s.path+'<br><small>ID: '+s.id+'</small></td><td><b>'+(Math.round(s.score*100)/100)+'%</b></td><td>'+s.source+'</td><td style="white-space:normal;">'+ex+'</td>';
              html+='<td><button class="btn btn-primary btn-xs" data-save="1" data-idc="'+idc+'" data-alg="'+s.id+'" data-conf="'+(Math.round(s.score*100)/100)+'" data-src="'+s.source+'" data-alg-path="'+escape(s.path)+'">Wybierz</button></td></tr>';
            });
            html+='</tbody></table>';
            if (resp.debug) {
              try {
                var dbg = resp.debug;
                var dbgHtml = '<div class="small text-muted" style="padding:6px 8px; border-top:1px dashed #ccc;">'
                  + '<b>Debug:</b> api_init='+(dbg.api_init ? 'true' : 'false')
                  + ', ean_count='+(dbg.ean_count||0)
                  + ', phase='+(Array.isArray(dbg.phase)?dbg.phase.join(' ▸ '):'')
                  + ', provider_calls='+(Array.isArray(dbg.provider_calls)?dbg.provider_calls.join(', '):'')
                  + '</div>';
                html += dbgHtml;
              } catch(e){}
            }
          }
          html+='</div>';
          var cont=document.createElement('div'); cont.innerHTML=html; box.appendChild(cont);
          
          Array.prototype.forEach.call(cont.querySelectorAll('[data-save]'), function(btn){
            btn.addEventListener('click', function(){
              var fd2=new FormData();
              fd2.append('ajax','1'); fd2.append('action','SaveCategoryMapping');
              fd2.append('id_category', this.getAttribute('data-idc'));
              fd2.append('allegro_category_id', this.getAttribute('data-alg'));
              fd2.append('confidence', this.getAttribute('data-conf'));
              fd2.append('source', this.getAttribute('data-src'));
              fd2.append('allegro_category_path', unescape(this.getAttribute('data-alg-path') || ''));
            
              fetch(url, {method:'POST', body: fd2, credentials:'same-origin'})
                .then(function(r){return r.text();})
                .then(function(t){ try{ return JSON.parse(t);}catch(e){ alert('Błąd (Save):\\n'+t.substring(0,400)); throw e; } })
                .then(function(rr){ 
                  if(rr.success){ 
                    alert('Zapisano.'); 
                    location.reload();
                  } else { 
                    alert('Błąd zapisu: '+(rr.message||'')); 
                  } 
                });
            });
          });
          one(i+1);
        })
        .catch(function(e){ one(i+1); });
    }
    one(0);
  }

  // --- DODANO --- Logika dla nowego przycisku aktualizacji
  document.getElementById('catmatch_update_allegro_cats').addEventListener('click', function() {
      if (!confirm('Pobieranie aktualnego drzewa kategorii z Allegro może potrwać kilkadziesiąt sekund. Kontynuować?')) {
          return;
      }
      
      var btn = this;
      var spinner = document.getElementById('catmatch_update_spinner');
      btn.disabled = true;
      spinner.style.display = 'inline-block';

      var fd = new FormData();
      fd.append('ajax', '1');
      fd.append('action', 'UpdateAllegroCategories');

      fetch(url, {method: 'POST', body: fd, credentials: 'same-origin'})
          .then(function(r) { return r.text(); })
          .then(function(t) { try { return JSON.parse(t); } catch(e) { alert('Błąd (Update):\\n'+t.substring(0,400)); throw e; } })
          .then(function(resp) {
              if (resp.success) {
                  alert('Kategorie Allegro zostały pomyślnie zaktualizowane. Strona zostanie teraz automatycznie odświeżona, aby załadować nowe dane.');
                  location.reload();
              } else {
                  alert('Wystąpił błąd podczas aktualizacji: ' + (resp.message || 'Nieznany błąd'));
                  btn.disabled = false;
                  spinner.style.display = 'none';
              }
          })
          .catch(function(e) {
              alert('Błąd krytyczny podczas komunikacji z serwerem: ' + e.message);
              btn.disabled = false;
              spinner.style.display = 'none';
          });
  });
  // --- KONIEC DODAWANIA ---

  // start
  fetchTree();
})();
</script>
{/literal}


{literal}
<script>
(function(){
  // locate "ZAPROPONUJ" button by text
  function findProposeBtn(){
    var candidates = document.querySelectorAll('button, a.btn, .btn');
    for (var i=0;i<candidates.length;i++){
      var t = (candidates[i].textContent || '').trim().toUpperCase();
      if (t === 'ZAPROPONUJ' || t.indexOf('ZAPROPONUJ') >= 0) {
        return candidates[i];
      }
    }
    return null;
  }

  function findTreeContainers(){
    var arr = [];
    var sels = ['.category-tree','#categories-tree','.cattree','.tree','[data-role="category-tree"]'];
    for (var i=0;i<sels.length;i++){
      var nodes = document.querySelectorAll(sels[i]);
      for (var j=0;j<nodes.length;j++){ arr.push(nodes[j]); }
    }
    return arr;
  }

  function isNum(x){ return /^\d+$/.test(String(x||'')); }
  function toNum(x){ return isNum(x)?parseInt(x,10):null; }

  function extractIdFromNode(node){
    if (!node) return null;
    if (isNum(node.value)) return toNum(node.value);
    var ds = node.dataset||{};
    var c = ds.categoryId || ds.id || ds.category || ds.idCategory;
    if (isNum(c)) return toNum(c);
    var p=node;
    for (var i=0;i<5 && p;i++,p=p.parentElement){
      if (!p) break;
      var d=p.dataset||{};
      var c2 = d.categoryId || d.id || d.category || d.idCategory;
      if (isNum(c2)) return toNum(c2);
      var attrs=['data-id','data-id-category','data-category-id','data-category'];
      for (var k=0;k<attrs.length;k++){
        var v=p.getAttribute(attrs[k]);
        if (isNum(v)) return toNum(v);
      }
    }
    return null;
  }

  function getSelectedCategoryId(){
    var containers = findTreeContainers();
    for (var t=0; t<containers.length; t++){
      var c = containers[t];
      var inputs = c.querySelectorAll('input[type=checkbox]:checked, input[type=radio]:checked');
      for (var i=inputs.length-1;i>=0;i--){
        var id = extractIdFromNode(inputs[i]);
        if (id && id !== 1) return id;
      }
    }
    var hid = document.querySelector('input[name="id_category"], input[name="id_category_default"]');
    if (hid && isNum(hid.value) && parseInt(hid.value,10)!==1) return parseInt(hid.value,10);
    return null;
  }

  function ajax(url, data, cb){
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
    xhr.onreadystatechange = function(){
      if (xhr.readyState === 4){
        var json=null;
        try{ json = JSON.parse(xhr.responseText); }catch(e){}
        cb(null, json);
      }
    };
    var body=[]; for (var k in data){ body.push(encodeURIComponent(k)+'='+encodeURIComponent(data[k])); }
    xhr.send(body.join('&'));
  }

  function ensureInlineSlot(btn){
    var slot = document.getElementById('cm_inline_out');
    if (!slot){
      slot = document.createElement('span');
      slot.id = 'cm_inline_out';
      slot.style.marginLeft = '8px';
      btn.parentNode.insertBefore(slot, btn.nextSibling);
    }
    return slot;
  }

  function hook(){
    var btn = findProposeBtn();
    if (!btn) return;
    var hooked = btn.getAttribute('data-cm-hooked');
    if (hooked) return;
    btn.setAttribute('data-cm-hooked','1');
    btn.addEventListener('click', function(e){
      e.preventDefault();
      e.stopImmediatePropagation(); // nie wywołuj starej logiki
      var out = ensureInlineSlot(btn);
      var idc = getSelectedCategoryId();
      if (!idc){
        out.innerHTML = '<span class="text-danger">Zaznacz kategorię w drzewku.</span>';
        return;
      }
      out.innerHTML = '<i class="icon-refresh icon-spin"></i> Szukam…';

      var qs = window.location.search;
      var token = (qs.match(/token=([^&]+)/)||[])[1] || '';
      var ctrl = (qs.match(/controller=([^&]+)/)||[])[1] || 'AdminXAllegroAssoc';
      var url = 'index.php?controller='+ctrl+'&ajax=1&action=autoMapCategory'+(token ? '&token='+token : '');

      ajax(url, {ldelim}id_category: idc, save: 1{rdelim}, function(err, json){
        if (!json){ out.innerHTML = '<span class="text-danger">Błąd połączenia.</span>'; return; }
        if (!json.success){
          out.innerHTML = '<span class="text-warning">'+ (json.message || 'Nie udało się dopasować') +'</span>';
          return;
        }
        out.innerHTML = '<span class="label label-success">ID: '+json.allegroCategoryId+'</span> <strong>'+json.allegroCategoryPath+'</strong> <em>['+json.method+']</em>';
      });
    }, true);
  }

  // initial + DOM changes
  hook();
  var mo = new MutationObserver(hook);
  mo.observe(document.body, {childList:true, subtree:true});
})();
</script>
{/literal}

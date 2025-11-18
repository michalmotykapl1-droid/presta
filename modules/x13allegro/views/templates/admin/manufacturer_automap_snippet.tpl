{* Snippet do ekranu producentów *}
<div class="panel">
  <div class="panel-heading"><i class="icon-magic"></i> Automatyczne dopasowanie producentów (Allegro)</div>
  <div class="form-inline" style="padding:10px 15px;">
    <label>Próg akceptacji:</label>
    <input type="number" step="0.01" min="0" max="1" id="brand_threshold" class="form-control" value="0.92" style="width:100px;margin:0 10px;">
    <label><input type="checkbox" id="brand_save"> Zapisz od razu</label>
    <button type="button" id="btn_automap_brands" class="btn btn-primary" style="margin-left:10px;">
      <i class="icon-bolt"></i> Auto-mapuj producentów
    </button>
    <span id="brand_out" style="margin-left:10px;"></span>
  </div>
</div>
{literal}
<script>
(function(){
  var btn = document.getElementById('btn_automap_brands');
  var out = document.getElementById('brand_out');
  if (!btn) return;
  function ajax(url, data, cb){
    var xhr = new XMLHttpRequest();
    xhr.open('POST', url, true);
    xhr.setRequestHeader('Content-Type','application/x-www-form-urlencoded; charset=UTF-8');
    xhr.onreadystatechange=function(){ if(xhr.readyState===4){ try{ cb(null, JSON.parse(xhr.responseText)); }catch(e){ cb(e); } } };
    var body=[]; for(var k in data){ body.push(encodeURIComponent(k)+'='+encodeURIComponent(data[k])); }
    xhr.send(body.join('&'));
  }
  function token(){ var m=location.search.match(/token=([^&]+)/); return m?m[1]:''; }
  btn.addEventListener('click', function(){
    out.innerHTML = '<i class="icon-refresh icon-spin"></i> Szukam...';
    var url = 'index.php?controller=AdminXAllegroAssoc&ajax=1&action=autoMapManufacturers'+(token()?'&token='+token():'');
    var thr = parseFloat(document.getElementById('brand_threshold').value||'0.92')||0.92;
    var sav = document.getElementById('brand_save').checked ? 1 : 0;
    ajax(url, {threshold:thr, save:sav, limit:500}, function(err, json){
      if (err || !json || !json.success){ out.innerHTML='Błąd'; return; }
      out.innerHTML = 'Zapisano: '+json.auto_saved+' / Propozycji: '+(json.proposals?json.proposals.length:0);
      console.log(json);
    });
  });
})();
</script>
{/literal}

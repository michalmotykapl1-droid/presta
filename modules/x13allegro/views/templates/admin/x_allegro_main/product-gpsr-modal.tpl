{* Pełna ścieżka: /modules/x13allegro/views/templates/admin/x_allegro_main/product-gpsr-modal.tpl *}
<div class="modal" id="product_gpsr_modal_{$index}" x-name="product_gpsr" x-index="{$index}" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header x13allegro-modal-header">
                <button type="button" class="close x13allegro-modal-close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="x13allegro-modal-title">{l s='Zgodność z GPSR' mod='x13allegro'}</h4>
                <h6 class="x13allegro-modal-title-small">dla produktu: <span>{$product.name}{if $product.name_attribute} - {$product.name_attribute}{/if}</span></h6>

                <span class="xproductization-product-label">
                    {if $productization_show_reference && !empty($product.reference)}<strong>Ref:</strong> {$product.reference}{/if}
                    {if $productization_show_gtin}
                        {if !empty({$product.ean13})}<strong>EAN13:</strong> {$product.ean13}{/if}
                        {if !empty({$product.isbn})}<strong>ISBN:</strong> {$product.isbn}{/if}
                        {if !empty({$product.upc})}<strong>UPC:</strong> {$product.upc}{/if}
                    {/if}
                    {if $productization_show_mpn && !empty($product.mpn)}<strong>MPN:</strong> {$product.mpn}{/if}
                </span>
            </div>
            <div class="modal-body x13allegro-modal-body">
                {if !$x13gpsrInstalled && !$x13gpsrInfoHide}
                    <div class="row x13gpsr-info-allegro">
                        <div class="col-md-12">
                            <a href="#" class="x13gpsr-info-allegro-close"><i class="icon-times"></i></a>

                            <div class="row">
                                <div class="col-xs-3">
                                    <div class="x13gpsr-info-allegro-img">
                                        <a href="https://x13.pl/moduly-prestashop/gpsr-rozporzadzenie-o-ogolnym-bezpieczenstwie-produktow.html?&utm_campaign=x13allegro_gpsr" target="_blank">
                                            <img alt="" src="../modules/x13allegro/img/x13gpsr.jpg"><br/>
                                            Sprawdź moduł
                                        </a>
                                    </div>
                                </div>
                                <div class="col-xs-9">
                                    <h4>Chcesz wystawiać oferty jeszcze szybciej?</h4>
                                    <p>
                                        Nie trać czasu na ręczne wybieranie danych!<br/>
                                        Skorzystaj z naszego modułu <a href="https://x13.pl/moduly-prestashop/gpsr-rozporzadzenie-o-ogolnym-bezpieczenstwie-produktow.html?&utm_campaign=x13allegro_gpsr" target="_blank"><strong>GPSR - Rozporządzenie o Ogólnym Bezpieczeństwie Produktów</strong></a> i usprawnij cały proces: <br/>
                                    </p>
                                    <ul>
                                        <li>Błyskawicznie przypisz <strong>osoby odpowiedzialne</strong> i <strong>producentów</strong> do swoich produktów w sklepie.</li>
                                        <li>Bezpośrednio w module <strong>utworzysz i zsynchronizujesz</strong> dane z Allegro.</li>
                                        <li>Podczas wystawiania ofert – dane GPSR <strong>wczytają się automatycznie</strong>!</li>
                                    </ul>
                                    <p>Zwiększ efektywność, oszczędzaj czas i spełniaj wymogi prawne bez zbędnych formalności.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                {/if}

                <div class="row">
                    <div class="col-lg-offset-2 col-md-8">
                        <div class="form-group">
                            <h4 style="font-size: 18px; font-weight: 600; margin: 0 0 10px 0;">Produkt wprowadzony do obrotu na terenie Unii Europejskiej przed&nbsp;13&nbsp;grudnia&nbsp;2024&nbsp;r.</h4>
                            <span class="switch prestashop-switch fixed-width-lg">
                                <input type="radio" id="item[{$index}][marketed_before_gpsr_obligation]_on" name="item[{$index}][marketed_before_gpsr_obligation]" x-name="marketed_before_gpsr_obligation" value="1">
                                <label for="item[{$index}][marketed_before_gpsr_obligation]_on">Tak</label>

                                <input type="radio" id="item[{$index}][marketed_before_gpsr_obligation]_off" name="item[{$index}][marketed_before_gpsr_obligation]" x-name="marketed_before_gpsr_obligation" value="0" checked="checked">
                                <label for="item[{$index}][marketed_before_gpsr_obligation]_off">Nie</label>

                                <a class="slide-button"></a>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-offset-2 col-md-8">
                        <div class="form-group">
                            <h4 style="font-size: 18px; font-weight: 600; margin: 20px 0 10px 0;">Dane producenta</h4>
                            <div class="alert alert-info">
                                Jeśli producent jest <b>spoza Unii Europejskiej</b>, musisz też wskazać osobę odpowiedzialną.
                            </div>
                            <label for="item_{$index}_responsible_producer" class="control-label">
                                Dane producenta - GPSR<span class="xproductization-gpsr-optional" style="display: none;"> (opcjonalnie)</span>
                            </label>
                            <select id="item_{$index}_responsible_producer"
                                    name="item[{$index}][responsible_producer]"
                                    x-name="responsible_producer"
                                    class="form-control">
                                <option value="">-- Wybierz --</option>
                                {foreach $responsibleProducers as $responsibleProducer}
                                    <option value="{$responsibleProducer->id}">{$responsibleProducer->name}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-offset-2 col-md-8">
                        <div class="form-group">
                            <h4 style="font-size: 18px; font-weight: 600; margin: 20px 0 0 0;">Osoba odpowiedzialna</h4>
                            <label for="item_{$index}_responsible_person" class="control-label">
                                Osoba odpowiedzialna za zgodność produktu - GPSR (opcjonalnie)
                            </label>
                            <select id="item_{$index}_responsible_person"
                                    name="item[{$index}][responsible_person]"
                                    x-name="responsible_person"
                                    class="form-control">
                                <option value="">-- Wybierz --</option>
                                {foreach $responsiblePersons as $responsiblePerson}
                                    <option value="{$responsiblePerson->id}">{$responsiblePerson->name}</option>
                                {/foreach}
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-offset-2 col-md-8">
                        <div class="form-group">
                            <h4 style="font-size: 18px; font-weight: 600; margin: 20px 0 10px 0;">Bezpieczeństwo produktu</h4>
                            <div class="alert alert-info">
                                Jeśli produkt zawiera informacje o bezpieczeństwie, muszą być one dostępne <b>w&nbsp;językach wszystkich rynków</b>, na których oferujesz produkt.
                            </div>
                            <label for="item[{$index}][safety_information_type]" class="control-label required xproductization-gpsr-required">Informacje o bezpieczeństwie produktu<span class="xproductization-gpsr-optional" style="display: none;"> (opcjonalnie)</span></label>
                            <select id="item[{$index}][safety_information_type]" name="item[{$index}][safety_information_type]" x-name="safety_information_type">
                                <option value="">-- Wybierz --</option>
                                {foreach $safetyInformationTypes as $safetyInformationType}
                                    <option value="{$safetyInformationType.id}">{$safetyInformationType.name}</option>
                                {/foreach}
                            </select>

                            <div class="gpsr-safety-information-text-wrapper" style="display: none;">
                                <textarea name="item[{$index}][safety_information_text]" x-name="safety_information_text"></textarea>
                                <p class="help-block counter-wrapper" data-max="{$safetyInformationTextMax}">
                                    <span class="counter-error" style="display: none;">Tekst jest za długi!</span>
                                    <span class="counter"><span class="count">0</span>/{$safetyInformationTextMax}</span>
                                </p>
                            </div>

                            {include file="./gpsr-safety-information-attachment-wrapper.tpl" productAttachments=$product.attachments index=$index}
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-footer x13allegro-modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">{l s='Zapisz' mod='x13allegro'}</button>
            </div>
        </div>
    </div>
</div>

{* --- TWÓJ DZIAŁAJĄCY SKRYPT + pasek postępu (niczego innego nie zmienia) --- *}
{literal}
<script>
(function($){
  if (window.__GPSR_MASS_BOUND__) return;
  window.__GPSR_MASS_BOUND__ = true;

  function fetchJSON(params){
    var url = (typeof currentIndex!=='undefined'?currentIndex:'index.php');
    var qs = 'ajax=1&token=' + encodeURIComponent(typeof token!=='undefined'?token:'') + '&' +
             Object.keys(params).map(function(k){return encodeURIComponent(k)+'='+encodeURIComponent(params[k]);}).join('&');
    return fetch(url + (url.indexOf('?')>-1?'&':'?') + qs, {credentials:'same-origin'}).then(function(r){
      return r.text().then(function(t){ try { return JSON.parse(t); } catch(e){ return {ok:false,error:'bad_json',raw:t}; } });
    });
  }

  function getSelectedItems(){
    var list = [];
    var sel = Array.prototype.slice.call(document.querySelectorAll('input[type="checkbox"][name^="item["][name$="[selected]"]:checked'));
    sel.forEach(function(cb){
      var m = (cb.name||'').match(/^item\[(\d+)\]\[selected\]$/);
      if (!m) return;
      var idx = m[1];
      var pid = (document.querySelector('input[name="item['+idx+'][id_product]"]')||{}).value || '';
      if (pid) list.push({idx:idx, pid:pid});
    });
    if (!list.length){
      var all = Array.prototype.slice.call(document.querySelectorAll('input[name^="item["][name$="[id_product]"]'));
      all.forEach(function(inp){
        var m = (inp.name||'').match(/^item\[(\d+)\]\[id_product\]$/);
        if (m) list.push({idx:m[1], pid:inp.value});
      });
    }
    return list;
  }

  function ensureHidden(idx, name){
    var sel = 'input[name="item['+idx+']['+name+']"], textarea[name="item['+idx+']['+name+']"], select[name="item['+idx+']['+name+']"]';
    var node = document.querySelector(sel);
    if (!node){
      node = document.createElement(name==='safety_information_text'?'textarea':'input');
      node.type = (name==='safety_information_text') ? 'text' : 'hidden';
      node.name = 'item['+idx+']['+name+']';
      var ref = document.querySelector('input[name="item['+idx+'][id_product]"]');
      (ref && ref.parentNode ? ref.parentNode : document.body).appendChild(node);
    }
    return node;
  }

  function setProducerUI(idx, producerId, displayName){
    var $sel = $('select[name="item['+idx+'][responsible_producer]"], #item_'+idx+'_responsible_producer');
    if ($sel.length){
      $sel.val(producerId);
      try { $sel.trigger('change').trigger('chosen:updated'); } catch(e){}
    } else {
      var $text = $('input[name^="item['+idx+'][gpsr_producer_name"]').first();
      if ($text.length) { $text.val(displayName || ''); }
    }
    var hid = ensureHidden(idx, 'responsible_producer');
    hid.value = producerId || '';
  }

  function textOptionValue($sel){
    var o = $sel.find('option[value="TEXT"]'); if (o.length) return 'TEXT';
    var v = null;
    $sel.find('option').each(function(){
      var t = ($(this).text()||'').toLowerCase();
      if (t.indexOf('opis')>-1 || t.indexOf('tekst')>-1) { v = $(this).val(); return false; }
    });
    return v;
  }

  function setSafetyUI(idx, text){
    var $sel = $('select[name="item['+idx+'][safety_information_type]"], select#item\\['+idx+'\\]\\[safety_information_type\\]');
    if ($sel.length){
      var v = textOptionValue($sel);
      if (v){ $sel.val(v); try { $sel.trigger('change').trigger('chosen:updated'); } catch(e){} }
    }
    setTimeout(function(){
      var $txt = $('textarea[name="item['+idx+'][safety_information_text]"], textarea[x-name="safety_information_text"]').first();
      if ($txt.length){ $txt.val(text||''); }
      var hid = ensureHidden(idx, 'safety_information_type'); hid.value = 'TEXT';
      var txth = ensureHidden(idx, 'safety_information_text'); txth.value = text||'';
    }, 150);
  }

  // --- PASEK POSTĘPU (tylko wyświetlanie) ---
  function ensureStatus(){
    var btn = document.getElementById('bulk_fill_gpsr');
    if (!btn) return null;
    var s = document.getElementById('gpsr_progress_line');
    if (!s){
      s = document.createElement('div');
      s.id = 'gpsr_progress_line';
      s.style.margin = '8px 0';
      s.style.fontSize = '12px';
      s.style.opacity = '0.85';
      btn.parentNode.insertBefore(s, btn.nextSibling);
    }
    return s;
  }

  async function runMass(){
    var btn = document.getElementById('bulk_fill_gpsr');
    var status = ensureStatus();
    if (btn){ btn.disabled = true; btn.dataset.originalText = btn.textContent; btn.textContent = 'Uzupełniam…'; }
    try {
      var items = getSelectedItems();
      if (!items.length){ if (status) status.textContent = 'GPSR: brak pozycji do przetworzenia.'; return; }
      var ok=0, err=0;
      if (status) status.textContent = 'GPSR: start (0/'+items.length+')';

      for (var i=0;i<items.length;i++){
        var it = items[i];
        try{
          if (status) status.textContent = 'GPSR: ['+(i+1)+'/'+items.length+'] pobieram markę…';
          var br = await fetchJSON({action:'GpsrGetBrand', id_product: it.pid});
          var brand = (br && br.ok) ? (br.brand||'') : '';

          if (status) status.textContent = 'GPSR: ['+(i+1)+'/'+items.length+'] rozwiązywanie producenta…';
          var pr  = brand ? await fetchJSON({action:'GpsrResolveProducer', name: brand}) : {ok:false};

          if (status) status.textContent = 'GPSR: ['+(i+1)+'/'+items.length+'] generuję tekst bezpieczeństwa…';
          var st  = await fetchJSON({action:'GpsrBuildSafetyText', id_product: it.pid});

          if (pr && pr.error==='bad_json') { console.warn('[GPSR] ResolveProducer JSON', pr.raw); }
          if (st && st.error==='bad_json') { console.warn('[GPSR] BuildSafetyText JSON', st.raw); }

          if (pr && pr.ok && pr.id){ setProducerUI(it.idx, pr.id, brand); }
          if (st && st.ok){ setSafetyUI(it.idx, st.text||''); }
          ok++;
        } catch(e){ err++; console.warn('[GPSR] error for idx', it.idx, e); }
        if (status) status.textContent = 'GPSR: '+(i+1)+'/'+items.length+' (OK: '+ok+', błędy: '+err+')';
      }

      if (status) status.textContent = 'GPSR: gotowe (OK: '+ok+', błędy: '+err+').';
      alert('Uzupełniono dane GPSR dla '+items.length+' pozycji. Teraz kliknij Zapisz/Wystaw.');
    } finally {
      if (btn){ btn.disabled = false; btn.textContent = btn.dataset.originalText || btn.textContent; }
    }
  }

  // podpinamy się TYLKO raz do Twojego przycisku
  function bind(){
    var btn = document.getElementById('bulk_fill_gpsr');
    if (btn && !btn.dataset.gpsrBound){
      btn.dataset.gpsrBound = '1';
      btn.addEventListener('click', function(e){ e.preventDefault(); e.stopPropagation(); runMass(); }, false);
    }
  }
  if (document.readyState === 'loading'){ document.addEventListener('DOMContentLoaded', bind); } else { bind(); }
})(jQuery);
</script>
{/literal}

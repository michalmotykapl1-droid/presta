(function(){
  if (typeof window.jQuery === 'undefined') return;
  var $ = window.jQuery;
  var DEBUG = false;

  // Only on 'Wystaw nowe oferty' controller
  if (!/[?&]controller=AdminXAllegroMain/i.test(location.search)) return;

  var cache = {}; // key 'id:<accountId>' -> [{id,name}, ...]

  function log(){ if (DEBUG && console && console.log) console.log.apply(console, arguments); }

  function getToken() {
    if (typeof token !== 'undefined' && token) return token;
    if (typeof static_token !== 'undefined' && static_token) return static_token;
    var m = location.search.match(/[?&]token=([^&]+)/i);
    return m ? decodeURIComponent(m[1]) : '';
  }

  function apiAssoc(params) {
    params = params || {};
    params.ajax = 1;
    params.token = getToken();
    return $.getJSON('index.php', $.extend({ controller: 'AdminXAllegroAssocManufacturers' }, params));
  }

  function getCurrentAccountId() {
    var v = $('input[name="id_xallegro_account"]').first().val();
    return v ? String(v) : '';
  }

  function fetchProducersByAccountId(accountId) {
    var key = 'id:'+String(accountId||'');
    var d = $.Deferred();
    if (!accountId) { d.reject('no-account'); return d.promise(); }
    if (cache[key]) { d.resolve(cache[key]); return d.promise(); }

    apiAssoc({ action: 'SyncMissingProducers', account_id: accountId, dry: 1, ping: 1 }).done(function(resp){
      try {
        var rows = (resp && resp.rows) ? resp.rows : [];
        var seen = {};
        var items = [];
        for (var i=0;i<rows.length;i++) {
          var r = rows[i] || {};
          var name = String(r.m || r.message || '').trim();
          var id   = r.id || '';
          if (!name || !id) continue;
          if (seen[id]) continue;
          seen[id] = 1;
          items.push({ id: id, name: name });
        }
        // Sort A->Z (case-insensitive, Polish-friendly basic)
        items.sort(function(a,b){
          return a.name.toLocaleLowerCase().localeCompare(b.name.toLocaleLowerCase());
        });
        cache[key] = items;
        log('gpsr-fallback: fetched', items.length, 'items for account', accountId);
        d.resolve(items);
      } catch(e) {
        d.reject('parse');
      }
    }).fail(function(){ d.reject('http'); });

    return d.promise();
  }

  function findGpsrSelects($modal) {
    // Try strict attr first
    var $s = $modal.find('select[x-name="responsible_producer"]');
    if ($s.length) return $s;
    // Then any select whose name contains [responsible_producer]
    $s = $modal.find('select[name*="[responsible_producer]"]');
    if ($s.length) return $s;
    // As a last resort: the first select inside the modal (not ideal, but better than nothing)
    return $modal.find('.modal-content select').first();
  }

  function alreadyFilled($sel) {
    return ($sel.find('option').length > 1);
  }

  function fillSelect($sel, items) {
    if (!$sel || !$sel.length || !items || !items.length) return;
    if (alreadyFilled($sel)) return;

    var frag = document.createDocumentFragment();
    for (var i=0;i<items.length;i++) {
      var it = items[i];
      var opt = document.createElement('option');
      opt.value = it.id;
      opt.textContent = it.name;
      frag.appendChild(opt);
    }
    $sel.append(frag);
    try { $sel.trigger('chosen:updated'); } catch(e) {}
    try { $sel.trigger('change'); } catch(e) {}
  }

  function onGpsrModalShown($modal) {
    var $sels = findGpsrSelects($modal);
    if (!$sels || !$sels.length) { log('gpsr-fallback: no select found'); return; }
    // If already filled by provider, skip
    var need = false;
    $sels.each(function(){ if (!alreadyFilled($(this))) need = true; });
    if (!need) return;

    var accId = getCurrentAccountId();
    if (!accId) { log('gpsr-fallback: no account id'); return; }

    fetchProducersByAccountId(accId).done(function(items){
      if (items && items.length) {
        $sels.each(function(){ fillSelect($(this), items); });
      }
    });
  }

  // Hook Bootstrap modal open
  $(document).on('shown.bs.modal', function (e) {
    var $m = $(e.target);
    // Single and bulk modals: detect by title or by presence of our target selects
    var title = $m.find('.x13allegro-modal-title, .modal-title').first().text() || '';
    if (/Zgodno\u015b\u0107 z GPSR|Zgodność z GPSR|Dzia\u0142ania masowe/i.test(title) || $m.find('select[x-name="responsible_producer"], select[name*="[responsible_producer]"]').length) {
      onGpsrModalShown($m);
    }
  });
})();
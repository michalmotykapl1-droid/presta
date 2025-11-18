(function () {
  if (typeof window.jQuery === 'undefined') return;
  var $ = window.jQuery;

  // Only on Allegro > Powiązania producentów (controller)
  if (!/[?&]controller=AdminXAllegroAssocManufacturers/i.test(location.search)) return;

  // detect sub-view: "Dodaj nowy" (add/update) or list
  var isFormView = /[?&](add|update)x_allegro_assoc_manufacturer/i.test(location.search);

  // ---------------- helpers --------------------------------------------------
  function getToken() {
    if (typeof token !== 'undefined' && token) return token;
    if (typeof static_token !== 'undefined' && static_token) return static_token;
    var m = location.search.match(/[?&]token=([^&]+)/i);
    return m ? decodeURIComponent(m[1]) : '';
  }

  function api(params) {
    params = params || {};
    params.ajax = 1;
    params.token = getToken();
    return $.getJSON('index.php', $.extend({ controller: 'AdminXAllegroAssocManufacturers' }, params));
  }

  function esc(s){ return String(s==null?'':s).replace(/[&<>"']/g,function(m){return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])}); }

  // ---------------- modal ----------------------------------------------------
  function SummaryModal() {
    var id = 'x13-sync-modal';
    var $modal = $('#' + id);
    if ($modal.length) return $modal;

    var html = ''
      + '<div id="'+id+'" class="bootstrap" style="position:fixed;left:0;top:0;right:0;bottom:0;z-index:99999;background:rgba(0,0,0,.35);display:none;">'
      + '  <div class="modal in" style="display:block;">'
      + '    <div class="modal-dialog" style="max-width:980px;width:auto;">'
      + '      <div class="modal-content">'
      + '        <div class="modal-header">'
      + '          <h4 class="modal-title"><i class="icon-refresh"></i> Synchronizacja producentów – podsumowanie</h4>'
      + '        </div>'
      + '        <div class="modal-body" style="max-height:68vh;overflow:auto;">'
      + '           <div id="x13-sync-intro" class="well" style="margin-bottom:12px;"></div>'
      + '           <div id="x13-sync-status" class="alert alert-info" style="margin-bottom:12px;">'
      + '             <span class="icon icon-time"></span> Status: <strong>inicjalizacja…</strong>'
      + '           </div>'
      + '           <div id="x13-sync-summary" class="panel panel-default" style="display:none;">'
      + '             <div class="panel-heading"><strong>Podsumowanie</strong></div>'
      + '             <div class="panel-body"><ul id="x13-summary-list" style="margin:0;padding-left:18px;"></ul></div>'
      + '           </div>'
      + '           <div id="x13-sync-log" class="panel panel-default" style="display:none;">'
      + '             <div class="panel-heading"><strong>Szczegóły</strong> <small>(ostatnie 500 wierszy)</small></div>'
      + '             <div class="panel-body"><pre style="white-space:pre-wrap;margin:0;" id="x13-log-pre"></pre></div>'
      + '           </div>'
      + '        </div>'
      + '        <div class="modal-footer">'
      + '          <button type="button" class="btn btn-default" id="x13-copy-report"><i class="icon-copy"></i> Kopiuj raport</button>'
      + '          <button type="button" class="btn btn-primary" id="x13-close-modal">OK</button>'
      + '        </div>'
      + '      </div>'
      + '    </div>'
      + '  </div>'
      + '</div>'
      + '<style>'
      + ' @keyframes x13spin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }'
      + ' .x13-spinner{display:inline-block;width:16px;height:16px;border:2px solid #999;border-top-color:transparent;border-radius:50%;margin-right:6px;animation:x13spin .8s linear infinite;vertical-align:-2px;}'
      + '</style>';

    $modal = $(html);
    $modal.find('#x13-close-modal').on('click', function(){ $modal.hide(); });
    $modal.find('#x13-copy-report').on('click', function(){
      var txt = $modal.find('#x13-sync-intro').text() + '\n'
              + $modal.find('#x13-summary-list').text() + '\n\n'
              + $modal.find('#x13-log-pre').text();
      try { navigator.clipboard.writeText(txt); $(this).text('Skopiowano ✔'); } catch(e) { alert('Kopiowanie nie powiodło się'); }
    });
    $('body').append($modal);
    return $modal;
  }

  function openModal() { var $m = SummaryModal(); $m.show(); return $m; }

  function setIntro($m, data) {
    var text = '<b>Konto:</b> ' + esc(data.label || data.key) + (data.is_sandbox ? ' <span class="label label-warning">SANDBOX</span>' : '')
             + ' &nbsp; <b>Tryb:</b> Normalny'
             + ' &nbsp; <b>Start:</b> ' + esc(data.startHuman || '—');
    $m.find('#x13-sync-intro').html(text);
  }

  function setStatus($m, type, msg) {
    var $s = $m.find('#x13-sync-status');
    $s.removeClass('alert-info alert-success alert-danger').addClass(
      type === 'ok' ? 'alert-success' : (type === 'err' ? 'alert-danger' : 'alert-info')
    );
    var icon = (type === 'ok') ? '<i class="icon-check"></i>' : (type === 'err') ? '<i class="icon-remove"></i>' : '<span class="x13-spinner"></span>';
    $s.html(icon + ' Status: <strong>' + esc(msg) + '</strong>');
  }

  function showSummary($m, s) {
    var items = [];
    for (var k in s) if (s.hasOwnProperty(k)) items.push('<li><b>'+esc(k)+':</b> '+esc(s[k])+'</li>');
    $m.find('#x13-summary-list').html(items.join(''));
    $m.find('#x13-sync-summary').show();
  }

  function showLog($m, rows) {
    var out = '';
    var max = 500;
    for (var i=0;i<rows.length && i<max;i++) {
      var r = rows[i] || {};
      var id = r.id ? (' → ' + r.id) : '';
      var msg = r.m || r.message || '';
      out += '[' + (r.action || 'INFO') + '] ' + msg + id + '\n';
    }
    if (rows.length > max) out += '… (' + (rows.length - max) + ' więcej)\n';
    $m.find('#x13-log-pre').text(out);
    $m.find('#x13-sync-log').show();
  }

  // ---------------- UI (toolbar) --------------------------------------------
  var accountsCache = {}; // key -> {label,is_sandbox,id}

  function placeToolbar($wrap) {
    // Prefer bottom placement to avoid overlapping the header/fields
    var placed = false;
    if (isFormView) {
      // after last form panel (most robust on add/update page)
      var $container = $('#content .panel').last();
      if ($container.length) { $container.after($wrap); placed = true; }
      else { $('#content').append($wrap); placed = true; }
    }
    if (!placed) {
      // Fallback: header action area
      var $slot = $('#content .panel-heading .panel-heading-action').first();
      if (!$slot.length) $slot = $('#content h2, #content .page-head').first();
      if (!$slot.length) $slot = $('#content');
      $slot.append($wrap);
    }
  }

  function buildUI() {
    var $wrap = $('<div id="x13-producers-toolbar" class="panel" style="margin-top:12px;"><div class="panel-body" style="display:flex;gap:10px;align-items:center;flex-wrap:wrap;"></div></div>');
    var $body = $wrap.find('.panel-body');
    var $label = $('<label style="margin:0 6px 0 0;">Konto Allegro:</label>');
    var $accSel = $('<select id="x13-acc" class="form-control" style="display:inline-block;width:auto;min-width:260px;"></select>');
    var $runBtn = $('<a class="btn btn-primary" id="x13-run"><i class="icon-refresh"></i> ZBUDUJ BAZĘ PRODUCENTÓW (ALLEGRO)</a>');

    $body.append($label).append($accSel).append($runBtn);
    placeToolbar($wrap);

    function loadAccounts(done) {
      if ($accSel.data('loaded')) { if (done) done(); return; }
      api({ action: 'ListAccounts' }).done(function (resp) {
        if (!resp || !resp.success || !resp.accounts || !resp.accounts.length) {
          alert('Nie znaleziono aktywnych kont Allegro w module x13.');
          return;
        }
        resp.accounts.forEach(function (a) {
          var label = a.label || a.username || a.account_key || ('Konto #' + (a.id || ''));
          var key   = a.account_key || a.username || a.id || '';
          $('<option>').val(key).text(label + (a.is_sandbox ? ' (SANDBOX)' : '')).appendTo($accSel);
          accountsCache[key] = {label: label, is_sandbox: !!a.is_sandbox, id: a.id};
        });
        $accSel.data('loaded', true);
        if (done) done();
      }).fail(function(){ alert('Błąd podczas ładowania listy kont Allegro.'); });
    }

    function testConnection(accountKey) {
      return api({ action: 'SyncMissingProducers', account_key: accountKey, dry: 1, ping: 1 });
    }
    function runSync(accountKey) {
      return api({ action: 'SyncMissingProducers', account_key: accountKey, dry: 0 });
    }

    $runBtn.on('click', function (e) {
      e.preventDefault();
      var $b = $(this);
      $b.prop('disabled', true);

      loadAccounts(function () {
        var accKey = String($accSel.val() || '');
        var acc    = accountsCache[accKey] || {};
        if (!accKey) { alert('Wybierz konto Allegro.'); $b.prop('disabled', false); return; }

        var started = new Date();
        var t0 = Date.now();
        var $m = openModal();
        setIntro($m, { key: accKey, label: acc.label, is_sandbox: acc.is_sandbox, startHuman: started.toLocaleString() });
        setStatus($m, 'info', 'Nawiązywanie połączenia (PING)…');

        testConnection(accKey).done(function (resp) {
          if (resp && resp.success) {
            setStatus($m, 'ok', 'Połączenie OK. Trwa synchronizacja…');
            var t1 = Date.now();
            runSync(accKey).done(function (resp2) {
              var t2 = Date.now();
              var rows = (resp2 && resp2.rows) ? resp2.rows : [];
              var counts = { 'Łącznie pozycji': rows.length, 'Utworzono (CREATED)': 0, 'Istniało (EXISTS)': 0, 'Zaktualizowano (UPDATED)': 0, 'Pominięto (SKIPPED)': 0, 'Błędy (ERROR/FAILED)': 0, 'Inne/INFO': 0 };
              for (var i=0;i<rows.length;i++) {
                var a = String(rows[i].action||'').toUpperCase();
                if (a==='CREATED') counts['Utworzono (CREATED)']++;
                else if (a==='EXISTS') counts['Istniało (EXISTS)']++;
                else if (a==='UPDATED') counts['Zaktualizowano (UPDATED)']++;
                else if (a==='SKIPPED') counts['Pominięto (SKIPPED)']++;
                else if (a==='ERROR' || a==='FAILED') counts['Błędy (ERROR/FAILED)']++;
                else counts['Inne/INFO']++;
              }
              var totalMs = (t2 - t0);
              var pingMs  = (t1 - t0);
              var syncMs  = (t2 - t1);

              showSummary($m, {
                'Konto': acc.label + (acc.is_sandbox ? ' (SANDBOX)' : ''),
                'Tryb': 'Normalny',
                'Czas – razem': (totalMs/1000).toFixed(2) + ' s',
                'Czas – PING': (pingMs/1000).toFixed(2) + ' s',
                'Czas – synchronizacja': (syncMs/1000).toFixed(2) + ' s',
                'Łącznie pozycji': counts['Łącznie pozycji'],
                'Utworzono': counts['Utworzono (CREATED)'],
                'Istniało': counts['Istniało (EXISTS)'],
                'Zaktualizowano': counts['Zaktualizowano (UPDATED)'],
                'Pominięto': counts['Pominięto (SKIPPED)'],
                'Błędy': counts['Błędy (ERROR/FAILED)'],
                'Inne/INFO': counts['Inne/INFO']
              });
              showLog($m, rows);
              setStatus($m, 'ok', 'Zakończono. Raport poniżej. Zamknij oknem „OK”.');
            }).fail(function () {
              setStatus($m, 'err', 'Błąd HTTP podczas właściwego uruchomienia – sprawdź token/konto.');
            }).always(function(){ $b.prop('disabled', false); });
          } else {
            setStatus($m, 'err', 'PING nie powiódł się – brak połączenia z Allegro API.');
            $b.prop('disabled', false);
          }
        }).fail(function () {
          setStatus($m, 'err', 'PING nie powiódł się (HTTP) – sprawdź token/konto.');
          $b.prop('disabled', false);
        });
      });
    });

    // init
    loadAccounts();
  }

  $(function () { buildUI(); });
})();
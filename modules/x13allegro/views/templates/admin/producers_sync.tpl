{extends file="helpers/view/view.tpl"}
{block name="view"}
<div class="panel">
  <h3>Producenci (X13) → Allegro (bulk)</h3>
  <p>To narzędzie <b>bez udziału modułu GPSR</b> tworzy/uzupełnia w Allegro listę <b>Responsible Producers</b> dla wszystkich producentów z PrestaShop i zapisuje mapowania w tabeli powiązań X13 (autodetekcja).</p>

  {if empty($accounts)}
    <div class="alert alert-warning">
      Nie wykryto kont Allegro. Otwórz moduł X13 Allegro (główna strona), aby odświeżyć token – potem wróć tutaj.
    </div>
  {else}

    <div class="form-inline" id="x13-bulk-sync-bar">
      <label>Konto Allegro:&nbsp;</label>

      {* ZAWSZE widoczny selektor konta — wspieramy dwa widoki: #acc (ten plik) i #x13-acc (drugi ekran) *}
      <select id="acc" class="form-control" style="display:inline-block;width:auto;margin-right:8px;">
        {foreach from=$accounts item=a}
          <option value="{$a.account_key|escape:'htmlall':'UTF-8'}"
                  {if isset($current_account_key) && $current_account_key == $a.account_key}selected="selected"{/if}>
            {$a.label|escape:'htmlall':'UTF-8'} ({$a.is_sandbox ? 'SANDBOX' : 'PROD'})
          </option>
        {/foreach}
      </select>

      <label class="checkbox" style="margin-left:10px;margin-right:8px;">
        <input type="checkbox" id="dry" /> Dry-run (pokaż plan, bez wysyłania do Allegro)
      </label>

      {* W tym widoku zwykle jest #run; na drugim ekranie bywa #x13-run – poniższy JS podepnie oba *}
      <button id="run" class="btn btn-primary" type="button">
        <i class="icon-refresh"></i> Zbuduj bazę producentów (ALLEGRO)
      </button>
    </div>

    <pre id="log" style="margin-top:15px; max-height:420px; overflow:auto"></pre>

    {* Wymuś widoczność selektora także dla alternatywnego ID (#x13-acc) – gdyby inny widok go ukrywał *}
    <style>
      #acc, #x13-acc { display:inline-block !important; width:auto !important; margin-right:8px !important; }
    </style>

    <script>
      (function(){
        if (window.__X13_PRODUCERS_SYNC_BOUND__) return;
        window.__X13_PRODUCERS_SYNC_BOUND__ = true;

        var $log = $('#log');

        function append(line){
          $log.append((line||'') + '\n');
          $log.scrollTop($log[0].scrollHeight);
        }

        function initAccountSelectSync(){
          var $accMain  = $('#acc');      // ten selektor z tego szablonu
          var $accAlias = $('#x13-acc');  // alternatywny selektor w innym widoku
          if ($accAlias.length && !$accMain.length){
            // jeśli mamy tylko #x13-acc – zadbaj o widoczność
            $accAlias.css({display:'inline-block'});
          } else if ($accAlias.length && $accMain.length){
            // trzymamy obie wartości w sync (na wypadek gdy oba istnieją)
            $accMain.on('change', function(){ $accAlias.val($accMain.val()); });
            $accAlias.on('change', function(){ $accMain.val($accAlias.val()); });
          }
        }

        function getSelectedAccountKey(){
          var v = ($('#acc').val() || $('#x13-acc').val() || '').toString();
          return v;
        }

        function bindRun($btn){
          if (!$btn.length || $btn.data('bound')) return;
          $btn.data('bound', true);

          $btn.on('click', function(e){
            e.preventDefault(); e.stopPropagation();

            var accountKey = getSelectedAccountKey();
            var isDry = $('#dry').is(':checked') ? 1 : 0;
            var selfLink = '{$self_link|escape:'javascript'}';

            $btn.prop('disabled', true);
            $log.text('Start (konto: ' + (accountKey || '?') + (isDry ? ', DRY-RUN' : '') + ')…\n');

            $.ajax({
              url: selfLink,
              method: 'POST',
              dataType: 'json',
              data: { ajax:1, action:'sync', account_key: accountKey, dry: isDry }
            }).done(function(resp){
              if (!resp || !resp.success) {
                append('Błąd: ' + (resp && resp.error ? resp.error : 'unknown'));
                return;
              }
              var r = resp.result || {}, rows = r.rows || [];
              append('Tabela mapowań: ' + (r.table ? (r.table.table || r.table) : '?'));
              rows.forEach(function(x){
                var line = '[' + String(x.action||'').toUpperCase() + '] ' + (x.m || x.message || '');
                if (x.id) line += ' → ' + x.id;
                if (x.msg) line += ' ('+x.msg+')';
                append(line);
              });
              append('Zakończono.');
            }).fail(function(xhr){
              append('Błąd HTTP ' + xhr.status);
            }).always(function(){
              $btn.prop('disabled', false);
            });
          });
        }

        $(document).ready(function(){
          initAccountSelectSync();
          // Podepnij dowolny z przycisków, który jest obecny na stronie
          bindRun($('#run'));     // ten plik
          bindRun($('#x13-run')); // drugi widok (pokazany na Twoim zrzucie)
        });
      })();
    </script>
  {/if}
</div>
{/block}

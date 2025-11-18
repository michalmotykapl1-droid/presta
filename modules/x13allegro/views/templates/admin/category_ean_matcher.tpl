{**
 * Template snippet: Category Matcher (EAN probe)
 * Include in your admin "Powiązania kategorii" page.
 * Requires: controller AdminXAllegroCategorySuggest, JS below, and tokens.
 *}
<div id="x13-ean-matcher" class="panel">
  <div class="panel-heading">
    <i class="icon-sitemap"></i> Asystent powiązań kategorii — skan EAN
  </div>

  <div class="form-inline" style="margin-bottom:10px">
    <label class="checkbox-inline">
      <input type="checkbox" id="x13-use-ean" checked="checked" /> Użyj EAN produktów
    </label>
    <label class="control-label" style="margin-left:15px">Ilość EAN do sprawdzenia</label>
    <input type="number" id="x13-ean-limit" class="form-control" value="5" min="1" max="50" style="width:90px;margin-left:8px" />
    <label class="checkbox-inline" style="margin-left:15px">
      <input type="checkbox" id="x13-debug" /> tryb debugowania
    </label>
    <button type="button" id="x13-btn-suggest" class="btn btn-primary" style="margin-left:12px">
      <i class="icon-search"></i> ZAPROPONUJ
    </button>
  </div>

  <div id="x13-suggest-summary" class="well" style="display:none"></div>
  <div id="x13-suggest-per-ean" style="display:none"></div>
</div>

<script type="text/javascript">
  (function () {
    function getSelectedCategoryId() {
      // expects standard Presta category tree with checkboxes
      var $checked = $('input[type=checkbox][name^="categoryBox"]:checked');
      if ($checked.length) {
        return parseInt($checked.first().val(), 10) || 0;
      }
      // fallback hidden field
      var $alt = $('input[name="id_category"]').first();
      return $alt.length ? (parseInt($alt.val(), 10) || 0) : 0;
    }

    function renderSummary(list) {
      if (!list || !list.length) {
        $('#x13-suggest-summary').html('<em>Brak wyników.</em>').show();
        return;
      }
      var html = '<table class="table"><thead><tr><th>Kategoria Allegro</th><th>Ilość trafień</th><th>Akcja</th></tr></thead><tbody>';
      list.forEach(function (row) {
        html += '<tr>' +
          '<td>' + $('<div/>').text(row.path).html() + ' <small>(' + row.categoryId + ')</small></td>' +
          '<td><span class="badge">' + row.count + '</span></td>' +
          '<td><button class="btn btn-default btn-sm x13-map" data-cid="' + row.categoryId + '" data-path="' + $('<div/>').text(row.path).html() + '">Wybierz</button></td>' +
        '</tr>';
      });
      html += '</tbody></table>';
      $('#x13-suggest-summary').html(html).show();
    }

    function renderPerEan(map) {
      var keys = Object.keys(map || {});
      if (!keys.length) {
        $('#x13-suggest-per-ean').hide();
        return;
      }
      var html = '';
      keys.forEach(function (ean) {
        html += '<div class="panel panel-default">' +
                  '<div class="panel-heading"><strong>EAN:</strong> ' + ean + '</div>' +
                  '<div class="panel-body"><ul>';
        (map[ean] || []).forEach(function (ex) {
          var url = ex.sampleUrl ? (' <a href="' + ex.sampleUrl + '" target="_blank">podgląd</a>') : '';
          html += '<li>' + $('<div/>').text(ex.path).html() + url + '</li>';
        });
        html +=    '</ul></div></div>';
      });
      $('#x13-suggest-per-ean').html(html).show();
    }

    $('#x13-btn-suggest').on('click', function () {
      var idCategory = getSelectedCategoryId();
      var useEan = $('#x13-use-ean').is(':checked') ? 1 : 0;
      var eanLimit = parseInt($('#x13-ean-limit').val(), 10) || 5;
      var debug = $('#x13-debug').is(':checked') ? 1 : 0;

      if (!idCategory) {
        alert('Zaznacz kategorię w drzewie.');
        return;
      }

      $('#x13-suggest-summary').hide().html('');
      $('#x13-suggest-per-ean').hide().html('');

      $.ajax({
        url: $('#content').data('x13-suggest-endpoint'),
        method: 'POST',
        dataType: 'json',
        data: {
          ajax: 1,
          action: 'probe',
          mode: 'tree',
          id_category: idCategory,
          use_ean: useEan,
          ean_limit: eanLimit,
          debug: debug
        }
      }).done(function (resp) {
        if (!resp || !resp.ok) {
          alert('Błąd: ' + (resp && resp.error ? resp.error : 'nieznany'));
          return;
        }
        renderSummary(resp.data.summary);
        renderPerEan(resp.data.perEan);
      }).fail(function () {
        alert('Nie udało się pobrać propozycji.');
      });
    });

    // Map action (left to integrate with your existing "Dodaj powiązanie kategorii")
    $(document).on('click', '.x13-map', function () {
      var cid = $(this).data('cid');
      var path = $(this).data('path');
      // Emit custom event for your module to handle saving the mapping.
      $(document).trigger('x13:category-picked', [{categoryId: cid, path: path}]);
      $.growl && $.growl.notice({ title: 'Wybrano', message: path + ' (' + cid + ')' });
    });
  })();
</script>

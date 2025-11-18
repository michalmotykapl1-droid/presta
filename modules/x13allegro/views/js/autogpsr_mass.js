(function () {
  if (typeof window.jQuery === 'undefined') {
    return;
  }
  var $ = window.jQuery;

  // Działa tylko na ekranie "Wystaw nowe oferty"
  if (!/[?&]controller=AdminXAllegroMain\b/i.test(window.location.search)) {
    return;
  }

  function buildProducersIndex() {
    var index = {};

    // Najpierw lista z modala masowego (zazwyczaj pełna)
    var $bulk = $('select[x-name="bulk_responsible_producer"]');
    var $source = $bulk.length ? $bulk : $('select[x-name="responsible_producer"]').first();

    if (!$source || !$source.length) {
      return index;
    }

    $source.find('option').each(function () {
      var val = $(this).attr('value');
      var txt = $.trim($(this).text() || '');
      if (!val || !txt) {
        return;
      }
      var key = txt.toLocaleLowerCase();
      if (!index[key]) {
        index[key] = val;
      }
    });

    return index;
  }

  function autoFillRow($row, producersIndex) {
    if (!$row || !$row.length) {
      return;
    }

    // 1) kategoria musi podlegać GPSR
    var catGpsr = parseInt($row.find('input[x-name="category_gpsr"]').val() || '0', 10);
    if (!catGpsr) {
      return;
    }

    // 2) select producenta GPSR
    var $select = $row.find('select[x-name="responsible_producer"]');
    if (!$select.length) {
      return;
    }

    // już ustawiony -> nic nie ruszamy
    if ($select.val()) {
      return;
    }

    // 3) nazwa producenta ze sklepu
    var manufacturer = $.trim($row.find('input[x-name="manufacturer"]').val() || '');
    if (!manufacturer) {
      return;
    }

    var key = manufacturer.toLocaleLowerCase();
    var id = producersIndex[key];

    // jeśli nie znaleziono po kluczu, spróbuj po tekście option
    if (!id) {
      $select.find('option').each(function () {
        var txt = $.trim($(this).text() || '').toLocaleLowerCase();
        if (txt && txt === key) {
          id = $(this).attr('value');
          return false; // break
        }
      });
      if (id) {
        producersIndex[key] = id;
      }
    }

    if (!id) {
      return;
    }

    $select.val(id).trigger('chosen:updated');
  }

  function autoFillAll() {
    var producersIndex = buildProducersIndex();
    if (!producersIndex || !Object.keys(producersIndex).length) {
      return;
    }

    $('tr[x-name="product"]').each(function () {
      autoFillRow($(this), producersIndex);
    });
  }

  $(function () {
    var ranOnce = false;
    var tries = 0;

    function tryRun() {
      if (ranOnce) {
        return;
      }

      // Muszą być wiersze produktów
      if (!$('tr[x-name="product"]').length) {
        return;
      }

      // I selecty z producentami (zwykłe lub masowe)
      if (
        !$('select[x-name="responsible_producer"]').length &&
        !$('select[x-name="bulk_responsible_producer"]').length
      ) {
        return;
      }

      ranOnce = true;
      autoFillAll();
    }

    // Próbujemy do 30 razy co 2 sekundy (max ~60s)
    var interval = window.setInterval(function () {
      tries++;
      tryRun();
      if (ranOnce || tries > 30) {
        window.clearInterval(interval);
      }
    }, 2000);

    // Ręczny przycisk "Uzupełnij dane GPSR dla zaznaczonych"
    $(document).on('click', '#bulk_fill_gpsr', function (e) {
      e.preventDefault();
      autoFillAll();
    });
  });
})();

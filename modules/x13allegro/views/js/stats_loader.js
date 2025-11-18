/* modules/x13allegro/views/js/stats_loader.js */
(function ($) {
  'use strict';
  $(function () {
    // Nadpisanie: zbiera zaznaczone oferty również, gdy wiersz nie ma widocznej akcji [x-name=action_*]
    window.getSelectedAuctions = function (action) {
      var $checked = $('[name="xallegro_auctionBox[]"]:checked');
      var items = [];
      $checked.each(function () {
        var $row = $(this).closest('tr');
        var $actionEl = $row.find('[x-name=' + action + ']');
        var id = $actionEl.data('id');
        var title = $actionEl.data('title');

        if (!id) { // fallback dla niepowiązanych / bez linku akcji
          var raw = String($(this).val() || '');
          if (raw) id = raw.split('|')[0]; // AUCTION_ID|...
          if (!title) title = $.trim($row.find('td').eq(1).text()) || '';
        }
        if (id) items.push({ id: String(id), title: title });
      });
      return items;
    };
  });
})(jQuery);
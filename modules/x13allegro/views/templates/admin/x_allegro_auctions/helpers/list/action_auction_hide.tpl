{* Poprawiony kod linku akcji 'ukryj' *}
<a href="{$href|escape:'html':'UTF-8'}"
   title="{$action|escape:'html':'UTF-8'}"
   class="hide-auction"
   data-id="{$data_id|intval}"
   data-title="{$data_title|escape:'html':'UTF-8'}"
   onclick="
       // Wyświetla natywne okno przeglądarki z prośbą o potwierdzenie.
       if (confirm('{l s='Czy na pewno chcesz na stałe ukryć tę ofertę? Nie będzie ona widoczna w module, dopóki nie usuniesz jej z listy ignorowanych.' mod='x13allegro'}')) {
           // Jeśli użytkownik kliknie 'OK', zwracamy true, co pozwala na wykonanie domyślnej akcji linku (przejście do href).
           return true;
       } else {
           // Jeśli użytkownik kliknie 'Anuluj', blokujemy standardowe zachowanie linku.
           event.stopPropagation(); // Zatrzymuje propagację zdarzenia w drzewie DOM.
           event.preventDefault();  // Anuluje domyślną akcję elementu, czyli przejście pod adres URL z atrybutu href.
       }
   "
>
    <i class="icon-eye-close"></i> {$action|escape:'html':'UTF-8'}
</a>
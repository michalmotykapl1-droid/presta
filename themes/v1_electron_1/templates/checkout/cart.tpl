{**
 * 2007-2025 PrestaShop
 * ... (licencja) ...
 *}
{strip}
{extends file=$layout}

{block name='content'}

  <div id="main">
    <div class="cart-grid row">

      <div class="cart-grid-body col-xs-12 col-lg-8">

        <div class="card cart-container">
          <div class="card-block">
            <h1 class="h1">{l s='Shopping Cart' d='Shop.Theme.Checkout'}</h1>
           </div>
          <hr class="separator">
          {block name='cart_overview'}
            {include file='checkout/_partials/cart-detailed.tpl' cart=$cart}
          {/block}
        </div>

        <div class="card cart-cross-selling-wrapper">
          
          <h2 class="h2 cross-selling-custom-title">Kupujący te produkty, wybrali również</h2>
          
          <div class="cross-selling-hook-content">
            {hook h='displayCrossSellingShoppingCart'}
          </div>

        </div>

        {block name='continue_shopping'}
          <a class="tv-continue-shopping-btn tvall-inner-btn" href="{$urls.pages.index}">
            <i class="material-icons">chevron_left</i>
             <span>{l s='Continue shopping' d='Shop.Theme.Actions'}</span>
          </a>
        {/block}

 
         {block name='hook_shopping_cart_footer'}
          {hook h='displayShoppingCartFooter'}
        {/block}
      </div>

      <div class="cart-grid-right col-xs-12 col-lg-4">

        {block name='cart_summary'}
          <div class="card cart-summary">

            {block name='hook_shopping_cart'}
             {block name='bb_free_shipping_bar'}
          
 
              {capture name='bb_free_ship_cfg'}{Configuration::get('PS_SHIPPING_FREE_PRICE')}{/capture}
              {assign var='free_shipping_threshold' value=$smarty.capture.bb_free_ship_cfg}
      
              <div id="js-cart-page-shipping-bar" class="bb-free-shipping-bar" data-free-shipping-threshold="{$free_shipping_threshold}">
               
                {if $free_shipping_threshold <= 0}
                  <p class="bb-free-shipping-text">
                    Konfiguracja darmowej wysyłki nie jest ustawiona (PS_SHIPPING_FREE_PRICE = 0).
                  </p>
                {else}
                  {assign var='current_total' value=$cart.subtotals.products.amount}
                  {math equation="x - y" x=$free_shipping_threshold y=$current_total assign="remaining"}
                  {if $remaining < 0}
                     {assign var='remaining' value=0}
                  {/if}
                  {math equation="(x * 100) / y" x=$current_total y=$free_shipping_threshold assign="percent"}
                  {if $percent > 100}
                    {assign var='percent' value=100}
                   {/if}
                  {if $percent < 0}
                    {assign var='percent' value=0}
                  {/if}
                  {if $remaining > 0}
                     <div class="bb-free-shipping-header">
                      <span class="bb-free-shipping-icon"><i class="material-icons">local_shipping</i></span>
                      <span class="bb-free-shipping-title">Darmowa dostawa</span>
                    </div>
                    <p class="bb-free-shipping-text">
                      Brakuje Ci jeszcze&nbsp;
<strong class="bb-free-shipping-amount">
                        {$remaining|number_format:2:',':' '} zł
                      </strong>&nbsp;
aby skorzystać z darmowej dostawy.
                    </p>
                  {else}
                    <div class="bb-free-shipping-header">
                      <span class="bb-free-shipping-icon"><i class="material-icons">local_shipping</i></span>
                      <span class="bb-free-shipping-title">Darmowa dostawa</span>
                     </div>
                    <p class="bb-free-shipping-text bb-free-shipping-done">
                      Gratulacje!
Twoje zamówienie kwalifikuje się do darmowej dostawy.
                    </p>
                  {/if}
                  <div class="bb-free-shipping-track">
                    <div class="bb-free-shipping-fill" style="width:{$percent|intval}%"></div>
                  </div>
                {/if}
              </div>
            {/block}

              {hook h='displayShoppingCart'} {* <-- TO JEST TWÓJ PROGRAM LOJALNOŚCIOWY *}
            {/block}

            {* === POCZĄTEK POPRAWKI: Blok kodu rabatowego przeniesiony TUTAJ === *}
            {block name='cart_voucher'}
               {include file='checkout/_partials/cart-voucher.tpl'}
            {/block}
            {* === KONIEC POPRAWKI === *}

            {block name='cart_totals'}
              {include file='checkout/_partials/cart-detailed-totals.tpl' cart=$cart} {* <-- TO JEST "WARTOŚĆ ZAMÓWIENIA" *}
            {/block}

            
 {* === UWAGA: Blok 'cart_voucher' został STĄD USUNIĘTY === *}
            
            {block name='cart_actions'}
               {include file='checkout/_partials/cart-detailed-actions.tpl' cart=$cart}
            {/block}

          </div>
        {/block}

        {block name='hook_reassurance'}
           {hook h='displayReassurance'}
        {/block}

        {* --- START MODYFIKACJI (Usunięcie tytułu "AKCEPTOWANE PŁATNOŚCI") --- *}
        {block name='payment_icons'}
        <div class="payment-icons-block block-reassurance-item">
            <span class="reassurance-icon">
                <i class="material-icons">credit_card</i>
            </span>
            <div class="reassurance-text">
                {* Usunięto <span> z tytułem *}
                <span class="payment-icons-list-text">BLIK, Przelewy24, Visa, Mastercard</span>
            </div>
        </div>
        {/block}
        {* --- KONIEC MODYFIKACJI --- *}

      </div>

    </div>
  </div>

  <script>
    (function () {
      
       /**
       * Funkcja synchronizująca pasek na stronie koszyka (target)
       * z paskiem w wysuwanym koszyku (source), który jest poprawnie aktualizowany.
 */
      function syncShippingBars() {
        try {
          // Źródło (działający pasek w wysuwanym koszyku)
          var sourceBar = document.querySelector('#_desktop_cart .bb-free-shipping-bar');
 // Cel (pasek na stronie koszyka, któremu nadaliśmy ID)
          var targetBar = document.getElementById('js-cart-page-shipping-bar');
 if (!sourceBar || !targetBar) {
            // Jeśli któryś nie istnieje, przerwij
            return;
 }

          // Znajdź elementy do skopiowania (tekst i pasek postępu)
          var sourceText = sourceBar.querySelector('.bb-free-shipping-text');
 var sourceFill = sourceBar.querySelector('.bb-free-shipping-fill');
          
          var targetText = targetBar.querySelector('.bb-free-shipping-text');
          var targetFill = targetBar.querySelector('.bb-free-shipping-fill');
 // Kopiuj zawartość HTML tekstu i jego klasy (np. 'bb-free-shipping-done')
          if (sourceText && targetText) {
            targetText.innerHTML = sourceText.innerHTML;
 targetText.className = sourceText.className;
          }

          // Kopiuj styl 'width' paska postępu
          if (sourceFill && targetFill) {
            targetFill.style.width = sourceFill.style.width;
 }

        } catch (e) {
          console.error('BB Free Shipping bar sync error', e);
 }
      }

      // Nasłuchuj zdarzenia 'updateCart'
      if (typeof prestashop !== 'undefined' && prestashop.on) {
        prestashop.on('updateCart', function () {
          // Poczekaj 500ms. To da czas PrestaShop na podmianę HTML
          // wysuwanego koszyka (naszego "źródła prawdy").
          setTimeout(syncShippingBars, 500);
        });
 }

      // Uruchom też przy ładowaniu strony, dla bezpieczeństwa
      document.addEventListener('DOMContentLoaded', function () {
        // Poczekaj chwilę, aż wszystko się załaduje
        setTimeout(syncShippingBars, 500);
      });
 })();
  </script>
  {* --- KONIEC MODYFIKACJI --- *}

{/block}
{/strip}
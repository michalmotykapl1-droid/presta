{**
 * 2007-2025 PrestaShop
 * ... (licencja) ...
 *}
{strip}
{block name='cart_detailed_totals'}
<div class="cart-detailed-totals">

  <div class="card-block">
    {foreach from=$cart.subtotals item="subtotal"}
      {if isset($subtotal.value) && $subtotal.type !== 'tax'}
        <div class="cart-summary-line" id="cart-subtotal-{$subtotal.type}">
          <span class="label{if 'products' === $subtotal.type} js-subtotal{/if}">
            {* --- POCZĄTEK MODYFIKACJI --- *}
            {if 'products' == $subtotal.type}
               Wartość zamówienia
            {elseif 'discount' == $subtotal.type}
               Rabat
            {else}
               {$subtotal.label|default:''}
            {/if}
            {* --- KONIEC MODYFIKACJI --- *}
          </span>
          <span class="value">{$subtotal.value|default:''}</span>
          {if $subtotal.type === 'shipping'}
            <div><small class="value">{hook h='displayCheckoutSubtotalDetails' subtotal=$subtotal}</small></div>
           {/if}
        </div>
      {/if}
    {/foreach}
  </div>

  {* === POCZĄTEK POPRAWKI: Przeniesiono blok cart_voucher === *}
  {* Ten blok został wycięty i przeniesiony do cart.tpl *}
  {* === KONIEC POPRAWKI === *}

  <hr class="separator">

  <div class="card-block">
    <div class="cart-summary-line cart-total">
      <span class="label">{$cart.totals.total.label|default:''} {$cart.labels.tax_short|default:''}</span>
      <span class="value">{$cart.totals.total.value|default:''}</span>
    </div>

    <div class="cart-summary-line">
      <small class="label">w tym VAT</small>
      <small class="value">{$cart.subtotals.tax.value|default:''}</small>
    </div>
  </div>

  <hr class="separator">
</div>
{/block}
{/strip}
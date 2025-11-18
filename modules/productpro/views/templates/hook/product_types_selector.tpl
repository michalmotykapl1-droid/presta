
{if isset($type_variants) && $type_variants|@count}
<div class="product-variant-selector variant-tile productpro-types">
  <label class="variant-label" for="pp_type_toggle">
    {l s='Inne rodzaje:' d='Shop.Theme.Catalog'}
  </label>

  <div class="variant-control">
    <div class="pp-type-dropdown js-bb-type-dropdown">
      <button
        type="button"
        id="pp_type_toggle"
        class="btn btn-default btn-block pp-type-toggle js-bb-type-toggle"
        aria-haspopup="true"
        aria-expanded="false"
      >
        <span class="pp-type-current">
          {$currentName|escape:'html':'UTF-8'}
        </span>
      </button>

      <ul class="pp-type-list">
        {foreach from=$type_variants item=tv}
          <li class="pp-type-item{if $tv.is_sale} pp-type-item--sale{/if}">
            <a href="{$tv.link}" class="pp-type-link">
              {if isset($tv.image_url) && $tv.image_url}
                <span class="pp-type-thumb">
                  <img src="{$tv.image_url}"
                       alt="{$tv.display_name|escape:'html':'UTF-8'}"
                       loading="lazy" />
                </span>
              {/if}

              <span class="pp-type-title">
                {$tv.display_name|escape:'html':'UTF-8'}
              </span>

              <span class="pp-type-price-block">
                {if $tv.is_sale}
                  <span class="pp-type-badge">
                    {l s='Wyprzedaż' d='Shop.Theme.Catalog'}
                  </span>
                {/if}
                {if $tv.has_discount}
                  <span class="pp-type-price-new">{$tv.price}</span>
                  <span class="pp-type-price-old">{$tv.price_without_reduction}</span>
                {else}
                  <span class="pp-type-price-new">{$tv.price}</span>
                {/if}
              </span>
            </a>
          </li>
        {/foreach}
      </ul>
    </div>
  </div>
</div>

{* Prosty JS do rozwijania / zwijania listy "Inne rodzaje" *}
<script>
document.addEventListener('DOMContentLoaded', function () {
  var dropdown = document.querySelector('.product-variant-selector.variant-tile.productpro-types .js-bb-type-dropdown');
  if (!dropdown) { return; }

  var toggle = dropdown.querySelector('.js-bb-type-toggle');
  var list   = dropdown.querySelector('.pp-type-list');
  if (!toggle || !list) { return; }

  toggle.addEventListener('click', function (e) {
    e.preventDefault();
    var isOpen = dropdown.classList.toggle('is-open');
    toggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
  });

  document.addEventListener('click', function (e) {
    if (!dropdown.contains(e.target)) {
      dropdown.classList.remove('is-open');
      toggle.setAttribute('aria-expanded', 'false');
    }
  });
});
</script>
{/if}

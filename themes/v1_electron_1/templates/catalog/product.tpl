{**
* 2007-2025 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License 3.0 (AFL-3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* https://opensource.org/licenses/AFL-3.0
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future.
* If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
* @author PrestaShop SA <contact@prestashop.com>
* @copyright 2007-2025 PrestaShop SA
* @license https://opensource.org/licenses/AFL-3.0 Academic Free License 3.0 (AFL-3.0)
* International Registered Trademark & Property of PrestaShop SA
*}
{strip}
    {extends file=$layout}
    {block name='head_seo' prepend}
    <link rel="canonical" href="{$product.canonical_url}">
    {/block}
    {block name='head' append}
    <meta content="width=device-width, initial-scale=1" name="viewport">
    <meta property="og:type" content="product">
    <meta property="og:url" content="{$urls.current_url}">
    <meta property="og:title" content="{$page.meta.title}">
    <meta property="og:site_name" content="{$shop.name}">
    <meta property="og:description" content="{$page.meta.description}">
    <meta property="og:image" content="{$product.cover.large.url}">
    <meta property="product:pretax_price:amount" content="{$product.price_tax_exc}">
    <meta property="product:pretax_price:currency" content="{$currency.iso_code}">
    <meta property="product:price:amount" content="{$product.price_amount}">
    <meta property="product:price:currency" content="{$currency.iso_code}">
    {if isset($product.weight) && ($product.weight != 0)}
    <meta property="product:weight:value" content="{$product.weight}">
    <meta property="product:weight:units" content="{$product.weight_unit}">
    {/if}
    {/block}
    {block name='head_microdata_special'}
        {include file='_partials/microdata/product-jsonld.tpl'}
    {/block}
    {block name='content'}
    <div id="main" itemscope itemtype="https://schema.org/Product">
        <meta itemprop="url" content="{$product.url}">
        <div class="tvproduct-page-wrapper">
            {assign var="prod_layout" value="../catalog/tv-{$TVCMSPRODUCTCUSTOM_LAYOUT}.tpl"}
            {if isset($product.id_category_list)}
              {assign var=catlist value=$product.id_category_list}
            {else}
              {assign var=catlist value=[$product.id_category_default]}
            {/if}

            {if isset($catlist)}
              <div class="product-label-inline" style="display:inline-block;margin-left:10px;">
                {if in_array(180, $catlist)}
                  <span class="label-shortdate" style="background-color:#ff6f61;color:white;padding:4px 8px;border-radius:4px;font-size:13px;">KRÓTKA DATA</span>
                {elseif in_array(45, $catlist)}
                  <span class="label-sale" style="background-color:#f39c12;color:white;padding:4px 8px;border-radius:4px;font-size:13px;">WYPRZEDAŻ</span>
                {/if}
              </div>
            {/if}
            {include file="$prod_layout"}

            {**
             * KOMENTARZ:
             * Na Twoim serwerze (jak widać na zrzucie ekranu) DOKŁADNIE W TYM MIEJSCU,
             * (pomiędzy powyższym {include} a poniższym {block})
             * znajduje się błędny kod:
             * <div class="testStickySlider" ...></div>
             *
             * Poniższa wersja pliku jest go pozbawiona. Wgranie jej na serwer
             * i wyczyszczenie cache naprawi problem.
            *}

            {block name='product_tabs'}
            <div class="tabs tvproduct-description-tab clearfix">
                <ul class="nav nav-tabs" role="tablist">
                    {if $product.description}
                    <li class="nav-item" role="presentation">
                        <a class="nav-link{if $product.description} active{/if}" data-toggle="tab" href="#description" role="tab" aria-controls="description" {if $product.description} aria-selected="true" {/if}> {l s='Description' d='Shop.Theme.Catalog' } </a>
                    </li>
                    {/if}
                    <li class="nav-item" role="presentation">
                        <a class="nav-link{if !$product.description} active{/if}" data-toggle="tab" href="#product-details" role="tab" aria-controls="product-details" {if !$product.description} aria-selected="true" {/if}> {l s='Product Details' d='Shop.Theme.Catalog' } </a>
                    </li>
                    {if $product.attachments}
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-toggle="tab" href="#attachments" role="tab" aria-controls="attachments">
                            {l s='Attachments' d='Shop.Theme.Catalog'}
                        </a>
                    </li>
                    {/if}
                    {foreach from=$product.extraContent item=extra key=extraKey}
                    <li class="nav-item" role="presentation">
                        <a class="nav-link" data-toggle="tab" href="#extra-{$extraKey}" role="tab" aria-controls="extra-{$extraKey}">
                            {$extra.title}
                        </a>
                    </li>
                    {/foreach}
                    {* start product comment tab hook *}
                    {hook h='displayProductListReviewsTab'}
                    {* End product comment tab hook *}
                </ul>
                <div class="tab-content clearfix" id="tab-content">

                    {* Sekcja "Parametry dietetyczne" w opisie produktu *}
<div class="product-diet-info">
  <div class="diet-params-header">
    <h3 class="diet-params-title">Parametry dietetyczne</h3>
  </div>
  <ul class="diet-features-list">
    {if isset($product.features)}
      {foreach from=$product.features item=feature}
        {* Mapowanie cech na klasy CSS i tekst ikony. Tutaj musisz podać DOKŁADNE nazwy cech z PrestaShop. *}
        {assign var="icon_data" value=''}
        {if $feature.name == 'Dieta: Wegańska'}
          {assign var="icon_data" value=['class' => 'veg-desc', 'text' => 'VEG']}
        {elseif $feature.name == 'Dieta: Bez glutenu'}
          {assign var="icon_data" value=['class' => 'gf-desc', 'text' => 'GF']}
        {elseif $feature.name == 'Dieta: Keto / Low-Carb'}
          {assign var="icon_data" value=['class' => 'keto-desc', 'text' => 'KETO']}
        {elseif $feature.name == 'Certyfikat: BIO'}
          {assign var="icon_data" value=['class' => 'bio-desc', 'text' => 'BIO']}
        {elseif $feature.name == 'Bez: Laktozy'}
          {assign var="icon_data" value=['class' => 'nl-desc', 'text' => 'NL']}
        {elseif $feature.name == 'Bez: Cukru'}
          {assign var="icon_data" value=['class' => 'ns-desc', 'text' => 'NS']}
        {elseif $feature.name == 'Dieta: Wegetariańska'}
          {assign var="icon_data" value=['class' => 'vege-desc', 'text' => 'VEGE']}
        {elseif $feature.name == 'Dieta: Niski Indeks Glikemiczny'}
          {assign var="icon_data" value=['class' => 'ig-desc', 'text' => 'IG']}
        {* Dodaj więcej elseif dla innych cech, jeśli potrzebujesz *}
        {/if}

        {* Wyświetlamy cechę, jeśli jest w mapowaniu i nie jest "Rodzaj produktu" *}
        {if $icon_data && $feature.name != 'Rodzaj produktu'}
          <li class="diet-feature-item">
            <span class="diet-badge-desc {$icon_data.class|escape:'htmlall':'UTF-8'}">{$icon_data.text|escape:'htmlall':'UTF-8'}</span>
            <span class="diet-feature-text">{$feature.name}</span>
          </li>
        {/if}
      {/foreach}
    {/if}
    {* Możesz usunąć poniższy blok, jeśli masz pewność, że cechy zawsze są w $product.features *}
    {* Poniżej kod dla $product.product_specific_references, jeśli jest używany *}
    {if isset($product.product_specific_references)}
      {foreach from=$product.product_specific_references item=reference}
        {assign var="icon_data_ref" value=''}
        {if $reference.name == 'Dieta: Wegańska'}
          {assign var="icon_data_ref" value=['class' => 'veg-desc', 'text' => 'VEG']}
        {elseif $reference.name == 'Dieta: Bez glutenu'}
          {assign var="icon_data_ref" value=['class' => 'gf-desc', 'text' => 'GF']}
        {elseif $reference.name == 'Dieta: Keto / Low-Carb'}
          {assign var="icon_data_ref" value=['class' => 'keto-desc', 'text' => 'KETO']}
        {elseif $reference.name == 'Certyfikat: BIO'}
          {assign var="icon_data_ref" value=['class' => 'bio-desc', 'text' => 'BIO']}
        {elseif $reference.name == 'Bez: Laktozy'}
          {assign var="icon_data_ref" value=['class' => 'nl-desc', 'text' => 'NL']}
        {elseif $reference.name == 'Bez: Cukru'}
          {assign var="icon_data_ref" value=['class' => 'ns-desc', 'text' => 'NS']}
        {elseif $reference.name == 'Dieta: Wegetariańska'}
          {assign var="icon_data_ref" value=['class' => 'vege-desc', 'text' => 'VEGE']}
        {elseif $reference.name == 'Dieta: Niski Indeks Glikemiczny'}
          {assign var="icon_data_ref" value=['class' => 'ig-desc', 'text' => 'IG']}
        {/if}

        {if $icon_data_ref && $reference.name != 'Rodzaj produktu'}
          <li class="diet-feature-item">
            <span class="diet-badge-desc {$icon_data_ref.class|escape:'htmlall':'UTF-8'}">{$icon_data_ref.text|escape:'htmlall':'UTF-8'}</span>
            <span class="diet-feature-text">{$reference.name}</span>
          </li>
        {/if}
      {/foreach}
    {/if}
  </ul>
  <button type="button"
          class="diet-info-trigger diet-info-trigger-link"
          aria-label="Co oznaczają parametry dietetyczne?"
          title="Co oznaczają parametry dietetyczne?">
    Co oznaczają parametry dietetyczne?
  </button>
</div>

<div id="diet-info-modal" class="diet-modal" aria-hidden="true">
  <div class="diet-modal__backdrop"></div>
  <div class="diet-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="diet-modal-title">
    <button type="button" class="diet-modal__close" aria-label="Zamknij">&times;</button>

    <h3 id="diet-modal-title">Co oznaczają „Parametry dietetyczne”?</h3>

    <p>Znaczki przy parametrach dietetycznych nadajemy na podstawie składu produktu oraz deklaracji producenta.
    Poniżej znajdziesz krótkie wyjaśnienie każdej z diet.</p>

    <h4><span class="diet-badge-desc bio-desc">BIO</span> Certyfikat: BIO</h4>
    <p>Ten znaczek dostaje produkt, który ma od producenta oznaczenie ekologiczne (np. logo liścia UE) i w opisie znajduje się informacja o certyfikacie BIO/EKO.
    Nie nadajemy tego oznaczenia samodzielnie – opieramy się na deklaracji producenta i certyfikacji.</p>

    <h4><span class="diet-badge-desc veg-desc">VEG</span> Dieta: Wegańska</h4>
    <p>Oznaczamy tak produkty, w których składzie nie ma żadnych składników pochodzenia zwierzęcego (mięsa, ryb, nabiału, jaj, miodu, żelatyny itp.).
    Jeśli pojawia się choć jeden składnik odzwierzęcy, produkt nie dostaje znacznika „wegańska”.</p>

    <h4><span class="diet-badge-desc vege-desc">VEGE</span> Dieta: Wegetariańska</h4>
    <p>Ten znaczek pojawia się przy produktach bez mięsa, ryb i owoców morza.
    Dopuszczalne są składniki takie jak mleko, sery, jajka czy miód – jeśli występują wyłącznie takie produkty odzwierzęce, oznaczamy produkt jako „wegetariański”.</p>

    <h4><span class="diet-badge-desc ns-desc">NS</span> Bez cukru</h4>
    <p>Tak oznaczamy produkty, w których w składzie nie ma dodanych cukrów (np. cukru, syropów, glukozy, fruktozy, miodu), a w tabeli wartości odżywczych zawartość cukrów jest bardzo niska zgodnie z deklaracją producenta.
    Jeśli producent dodaje cukier lub słodzące syropy – produkt nie dostanie znacznika „bez cukru”.</p>

    <h4><span class="diet-badge-desc nl-desc">NL</span> Bez laktozy</h4>
    <p>Znacznik „bez laktozy” dostają produkty, które są naturalnie bez nabiału lub są oznaczone przez producenta jako „bez laktozy” / „lactose free”.
    Nie nadajemy tego oznaczenia samodzielnie produktom mlecznym, jeśli producent nie deklaruje wyraźnie braku laktozy.</p>

    <h4><span class="diet-badge-desc gf-desc">GF</span> Bez glutenu</h4>
    <p>Oznaczamy tak produkty, które mają od producenta wyraźną informację „bez glutenu” lub są wytworzone z surowców naturalnie bezglutenowych i nie zawierają w składzie pszenicy, żyta, jęczmienia, orkiszu itp.
    Przy produktach z możliwym śladem glutenu znaczek może się nie pojawić.</p>

    <h4><span class="diet-badge-desc keto-desc">KETO</span> Dieta: Keto / Low-Carb</h4>
    <p>Ten znacznik dostają produkty, które według składu i tabeli wartości odżywczych mają obniżoną zawartość węglowodanów i pasują do diety ketogenicznej lub niskowęglowodanowej (duży udział tłuszczu i/lub białka, mało cukrów).
    Oznaczenie opiera się na deklaracji producenta i analizie makroskładników.</p>

    <h4><span class="diet-badge-desc ig-desc">IG</span> Dieta: Niski indeks glikemiczny</h4>
    <p>Tak oznaczamy produkty oparte na węglowodanach złożonych i/lub bez dodatku cukrów prostych, które z założenia powinny wolniej podnosić poziom glukozy we krwi.
    Bazujemy na rodzaju użytych składników, zawartości cukrów oraz deklaracjach producenta.</p>

    <p class="diet-modal__disclaimer"><strong>Uwaga:</strong> parametry dietetyczne mają charakter informacyjny i są nadawane na podstawie danych producenta oraz automatycznej klasyfikacji.
    Mimo zachowania najwyższej staranności mogą zdarzyć się błędne przypisania lub zmiany składu produktu po stronie producenta.
    Przed spożyciem zawsze dokładnie przeczytaj etykietę na opakowaniu – w szczególności skład, alergeny i informacje o wartościach odżywczych.</p>
  </div>
</div>


                    <div class="tab-pane fade in {if $product.description} active {/if}" id="description" role="tabpanel">
                        {block name='product_description'}
                        <div class="product-description cms-description">{$product.description nofilter}</div>
                        {/block}
                    </div>
                    {block name='product_details'}
                    {include file='catalog/_partials/product-details.tpl'}
                    {/block}
                    {block name='product_attachments'}
                    {if $product.attachments}
                    <div class="tab-pane fade in" id="attachments" role="tabpanel">
                        <div class="product-attachments">
                            <p class="h5 text-uppercase">{l s='Download' d='Shop.Theme.Actions'}</p>
                            {foreach from=$product.attachments item=attachment}
                            <div class="attachment">
                                <h4><a href="{url entity='attachment' params=['id_attachment' => $attachment.id_attachment]}">{$attachment.name}</a></h4>
                                <p>{$attachment.description}</p>
                                <a href="{url entity='attachment' params=['id_attachment' => $attachment.id_attachment]}">
                                    {l s='Download' d='Shop.Theme.Actions'} ({$attachment.file_size_formatted})
                                </a>
                            </div>
                            {/foreach}
                        </div>
                    </div>
                    {/if}
                    {/block}
                    {foreach from=$product.extraContent item=extra key=extraKey}
                    <div class="tab-pane fade in {$extra.attr.class}" id="extra-{$extraKey}" role="tabpanel" {foreach $extra.attr as $key=> $val} {$key}="{$val}"{/foreach}>
                        {$extra.content nofilter}
                    </div>
                    {/foreach}
                    {* start product comment tab content hook *}
                    {hook h='displayProductListReviewsTabContent' product=$product}
                    {* End product comment tab content hook *}
                </div>
            </div>
            {/block}
        </div>
        {block name='product_accessories'}
        {if $accessories}
        <div class="tvcmslike-product container-fluid">
            <div class='tvlike-product-wrapper-box container'>
                <div class='tvcmsmain-title-wrapper'>
                    <div class="tvcms-main-title">
                        <div class='tvmain-title'>
                            <h2>{l s='You might also like' d='Shop.Theme.Catalog'}</h2>
                        </div>
                    </div>
                </div>
                <div class="tvlike-product">
                    <div class="products owl-theme owl-carousel tvlike-product-wrapper tvproduct-wrapper-content-box">
                        {foreach $accessories as $product}
                        {include file="catalog/_partials/miniatures/product.tpl" product=$product tv_product_type="like_product"}
                        {/foreach}
                    </div>
                </div>
                <div class='tvlike-pagination-wrapper tv-pagination-wrapper'>
                    <div class="tvcmslike-next-pre-btn tvcms-next-pre-btn">
                        <div class="tvcmslike-prev tvcmsprev-btn" data-parent="tvcmslike-product"><i class='material-icons'>&#xe317;</i></div>
                        <div class="tvcmslike-next tvcmsnext-btn" data-parent="tvcmslike-product"><i class='material-icons'>&#xe317;</i></div>
                    </div>
                </div>
            </div>
        </div>
        {/if}
        {/block}
        {block name='product_footer'}
        {hook h='displayFooterProduct' product=$product category=$category}
        {/block}
        {block name='product_images_modal'}
        {include file='catalog/_partials/product-images-modal.tpl'}
        {/block}
        {block name='page_footer_container'}
        {if Configuration::get('TVCMSCUSTOMSETTING_PRODUCT_PAGE_BOTTOM_STICKY_STATUS')}
        <div class="tvfooter-product-sticky-bottom">
            <div class="container">
                <div class="tvflex-items">
                    <div class="tvproduct-image-title-price">
                        {if $product.cover}
                        <div class="product-image">
                            <img src="{$product.cover.bySize.large_default.url}" alt="{$product.cover.legend}" title="{$product.cover.legend}" itemprop="image" width="{$product.cover.bySize.large_default.width}" height="{$product.cover.bySize.large_default.height}" loading="lazy">
                        </div>
                        <div class="tvtitle-price">
                            {block name='page_header'}
                            <h1 class="h1" itemprop="name">{block name='page_title'}{$product.name}{/block}</h1>
                            {/block}
                            {block name='product_prices'}
                            {include file='catalog/_partials/product-prices.tpl'}
                            {/block}
                        </div>
                        {/if}
                    </div>
                    <div>
                        <div class="product-actions" id="bottom_sticky_data"></div>
                    </div>
                </div>
            </div>
        </div>
        {/if}
        <footer class="page-footer">
            {block name='page_footer'}
            {/block}
        {literal}
<script>
document.addEventListener('DOMContentLoaded', function () {
  var trigger = document.querySelector('.diet-info-trigger');
  var modal   = document.getElementById('diet-info-modal');
  if (!trigger || !modal) return;

  var backdrop = modal.querySelector('.diet-modal__backdrop');
  var closeBtn = modal.querySelector('.diet-modal__close');
  function openModal() {
    modal.classList.add('is-open');
    document.body.classList.add('diet-modal-open');
  }

  function closeModal() {
    modal.classList.remove('is-open');
    document.body.classList.remove('diet-modal-open');
  }

  trigger.addEventListener('click', openModal);
  trigger.addEventListener('keydown', function (e) {
    if (e.key === 'Enter' || e.key === ' ') {
      e.preventDefault();
      openModal();
    }
  });
  [backdrop, closeBtn].forEach(function (el) {
    if (!el) return;
    el.addEventListener('click', closeModal);
  });
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
      closeModal();
    }
  });
});
</script>
{/literal}

        </footer>
        {/block}
    </div>
    {/block}
{/strip}
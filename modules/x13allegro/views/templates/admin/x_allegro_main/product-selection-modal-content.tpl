<div class="row xproductization-allegro-products">
    {foreach $products_from_allegro as $allegro_product}
        <div class="col-xs-12 col-md-4">
            <div class="thumbnail xproductization-allegro-products-wrap">
                <div class="row">
                    <div class="col-xs-4 col-lg-3">
                        <div class="thumbnail-img">
                            {if !empty($allegro_product->images)}
                                <img style="max-height: 130px; margin: auto;" class="img-responsive" src="{$allegro_product->images[0]->url|regex_replace:'/original/':'s128'}" alt="{$allegro_product->name|escape:html}">
                            {/if}
                        </div>
                    </div>

                    <div class="col-xs-8 col-lg-9">
                        <div class="caption">
                            <a href="{$products_url}/produkt/{$allegro_product->id}" target="_blank" rel="nofollow">
                                <h4 class="xproductization-product-name">{$allegro_product->name}</h4>
                            </a>
                            <p>
                                <em>{l s='Kategoria:' mod='x13allegro'} {$products_categories[$allegro_product->id]} ({$allegro_product->category->id})</em>
                            </p>
                            <ul class="list-group" style="max-height: 145px; overflow: auto;">
                                {foreach $allegro_product->parameters as $parameter}
                                    <li class="list-group-item">{$parameter->name}: <strong>{if !empty($parameter->valuesLabels)}{', '|implode:$parameter->valuesLabels}{else}n/a{/if}</strong></li>
                                {/foreach}
                            </ul>

                            <div class="xproductization-bottom">
                                <div>
                                    <a href="{$products_url}/moje-allegro/sprzedaz/zglos-blad-w-produkcie/{$allegro_product->id}" target="_blank" rel="nofollow" class="xproductization-report-an-error">
                                        <span>Zgłość błąd w produktyzacji</span>
                                    </a>
                                </div>

                                <div class="text-right">
                                    <div class="btn-group dropdown">
                                        <button class="btn btn-default dropdown-toggle" type="button" data-toggle="dropdown">
                                            <i class="icon-search"></i>
                                        </button>
                                        {if isset($allegro_product->description) || !empty($allegro_product->images)}
                                            <ul class="dropdown-menu">
                                                {if isset($allegro_product->description)}<li><a href="#" class="xproductization-product-preview-description-button">{l s='Podgląd opisu' mod='x13allegro'}</a></li>{/if}
                                                {if !empty($allegro_product->images)}<li><a href="#" class="xproductization-product-preview-images-button">{l s='Podgląd zdjęć' mod='x13allegro'}</a></li>{/if}
                                            </ul>
                                        {/if}
                                    </div>

                                    <a data-allegro-product="{$allegro_product->id}" href="#" class="btn btn-primary" role="button">
                                        <i class="icon-plus"></i><span>{l s='Wybierz' mod='x13allegro'}</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="xproductization-product-preview-description" style="display: none;">
                        <div class="allegro-description">
                            {if isset($allegro_product->description)}
                                {foreach $allegro_product->description->sections as $section}
                                    <section class="section">
                                        {foreach $section->items as $item}
                                            <div class="item {if $section->items|@count > 1}item-6{else}item-12{/if}">
                                                <section class="{if $item->type == 'TEXT'}text{else}image{/if}-item">
                                                    {if $item->type == 'TEXT'}
                                                        {$item->content}
                                                    {else}
                                                        <img src="{$item->url|regex_replace:'/original/':'s512'}" alt="">
                                                    {/if}
                                                </section>
                                            </div>
                                        {/foreach}
                                    </section>
                                {/foreach}
                            {/if}
                        </div>
                    </div>

                    <div class="xproductization-product-preview-images" style="display: none;">
                        {foreach $allegro_product->images as $image}
                            <img src="{$image->url|regex_replace:'/original/':'s256'}" alt="" style="width: auto; max-height: 250px; margin: 0 10px 10px 0; border: 1px solid #ccc;">
                        {/foreach}
                    </div>
                </div>
            </div>
        </div>
    {/foreach}
</div>

<div class="form-group clearfix">
    <label for="xallegro_auction_title" class="control-label form-control-label col-lg-3">
        <span>{l s='Tytuł oferty' mod='x13allegro'}</span>
    </label>

    <div class="col-lg-9">
        <input id="xallegro_auction_title" type="text" name="xallegro_auction_title" class="form-control" value="{$titlePattern|escape}">
    </div>

    <div class="col-lg-9 col-lg-offset-3">
        <div id="xallegro_title_count" class="help-block form-text" {if $titleCount === false}style="display: none;"{/if}>
            <p>{l s='Ilość znaków' mod='x13allegro'}: <strong><span class="xallegro_title_counter">{$titleCount|intval}</span>/{$titleMaxSize|intval}</strong></p>
        </div>
        <div class="help-block form-text">
            {l s='Domyślny tytuł oferty' mod='x13allegro'}: <b>{$titlePatternDefault}</b><br>
        </div>
        <div class="help-block form-text">
            <a href="#" id="xallegro_show_title_pattens">{l s='Pokaż dostępne znaczniki' mod='x13allegro'}</a>
        </div>
        <div id="xallegro_title_patterns" class="help-block form-text" style="display: none;">
            <span class="x13allegro_black" style="color:#222">{l s='{product_id}' mod='x13allegro'}</span> - ID produktu<br>
            <span class="x13allegro_black" style="color:#222">{l s='{product_name}' mod='x13allegro'}</span> - Nazwa produktu<br>
            <span class="x13allegro_black" style="color:#222">{l s='{product_name_attribute}' mod='x13allegro'}</span> - Nazwa atrybutu<br>
            <span class="x13allegro_black" style="color:#222">{l s='{product_short_desc}' mod='x13allegro'}</span> - Krótki opis<br>
            <span class="x13allegro_black" style="color:#222">{l s='{product_reference}' mod='x13allegro'}</span> - Kod referencyjny (indeks) produktu<br>
            <span class="x13allegro_black" style="color:#222">{l s='{product_ean13}' mod='x13allegro'}</span> - Kod EAN13<br>
            <span class="x13allegro_black" style="color:#222">{l s='{product_weight}' mod='x13allegro'}</span> - Waga produktu<br>
            <span class="x13allegro_black" style="color:#222">{l s='{product_price}' mod='x13allegro'}</span> - Cena produktu<br>
            <span class="x13allegro_black" style="color:#222">{l s='{manufacturer_name}' mod='x13allegro'}</span> - Nazwa producenta<br>
            <span class="x13allegro_black" style="color:#222">{l s='{attribute_group_X}' mod='x13allegro'}</span> - Nazwa i wartość grupy atrybutów X (grupa atrybutów musi być przypisana do kombinacji produktu)<br>
            <span class="x13allegro_black" style="color:#222">{l s='{attribute_group_value_X}' mod='x13allegro'}</span> - Wartość grupy atrybutów X (grupa atrybutów musi być przypisana do kombinacji produktu)<br>
            <span class="x13allegro_black" style="color:#222">{l s='{feature_X}' mod='x13allegro'}</span> - Nazwa i wartość cechy X (cecha musi być przypisana do produktu)<br>
            <span class="x13allegro_black" style="color:#222">{l s='{feature_value_X}' mod='x13allegro'}</span> - Wartość cechy X (cecha musi być przypisana do produktu)<br>
        </div>
    </div>
</div>
